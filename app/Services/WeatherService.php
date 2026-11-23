<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;
    protected int $cacheTtl;
    protected string $geocodingBaseUrl;
    protected int $geocodingTimeout;

    public function __construct()
    {
        $this->baseUrl = (string) config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');
        $this->apiKey = config('cargotrace.weather.api_key');
        $this->timeout = (int) config('cargotrace.weather.timeout', 5);
        $this->cacheTtl = (int) config('cargotrace.weather.cache_ttl', 900);

        $this->geocodingBaseUrl = (string) config('cargotrace.geocoding.base_url', 'https://geocoding-api.open-meteo.com/v1/search');
        $this->geocodingTimeout = (int) config('cargotrace.geocoding.timeout', 5);
    }

    /**
     * Retrieve live operational weather context for a shipment.
     * Evaluates coordinates from latest valid location event, falling back to origin location node.
     *
     * @param Shipment $shipment
     * @return array{available: bool, message?: string, data?: array|null, ...}
     */
    public function getWeatherForShipment(Shipment $shipment): array
    {
        $targetLat = null;
        $targetLng = null;
        $locationLabel = null;

        // 1. Prioritize active location telemetry from latest tracking event
        $latestLocationEv = $shipment->latestLocationEvent();
        if ($latestLocationEv && $latestLocationEv->hasCoordinates()) {
            $targetLat = (float) $latestLocationEv->latitude;
            $targetLng = (float) $latestLocationEv->longitude;
            $locationLabel = $latestLocationEv->location?->name ?? "Waypoint [{$targetLat}, {$targetLng}]";
        } elseif ($shipment->originLocation && $shipment->originLocation->latitude && $shipment->originLocation->longitude) {
            // 2. Fall back to origin location node coordinates
            $targetLat = (float) $shipment->originLocation->latitude;
            $targetLng = (float) $shipment->originLocation->longitude;
            $locationLabel = $shipment->originLocation->name;
        }

        if ($targetLat === null || $targetLng === null) {
            return $this->fallbackResponse(
                'Koordinat geografis belum tersedia untuk memuat telemetri cuaca rute.',
                [
                    'shipment_number' => $shipment->shipment_number,
                    'reason' => 'No active GPS or location coordinates found for this consignment.',
                ]
            );
        }

        return $this->getWeatherForCoordinates($targetLat, $targetLng, $locationLabel);
    }

    /**
     * Fetch weather metrics for specific coordinates with caching and graceful fault tolerance.
     *
     * @param float $latitude
     * @param float $longitude
     * @param string|null $locationLabel
     * @return array
     */
    public function getWeatherForCoordinates(float $latitude, float $longitude, ?string $locationLabel = null): array
    {
        // Validate coordinate bounds
        if ($latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
            return $this->fallbackResponse('Nilai koordinat di luar batas geografis yang valid.');
        }

        $roundedLat = round($latitude, 2);
        $roundedLng = round($longitude, 2);
        $cacheKey = "cargotrace_weather_{$roundedLat}_{$roundedLng}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $locationLabel) {
            return $this->fetchFromApi($latitude, $longitude, $locationLabel);
        });
    }

    /**
     * Execute external HTTP request to weather provider with timeout and fault tolerance.
     *
     * @param float $latitude
     * @param float $longitude
     * @param string|null $locationLabel
     * @return array
     */
    protected function fetchFromApi(float $latitude, float $longitude, ?string $locationLabel): array
    {
        try {
            $isOpenWeather = str_contains(strtolower($this->baseUrl), 'openweathermap');

            if ($isOpenWeather) {
                $params = [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ];
            } else {
                // Default: Open-Meteo
                $params = [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,wind_direction_10m,precipitation',
                    'timezone' => 'auto',
                ];
                if ($this->apiKey) {
                    $params['apikey'] = $this->apiKey;
                }
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($isOpenWeather) {
                    return $this->parseOpenWeatherResponse($data, $latitude, $longitude, $locationLabel);
                }
                return $this->parseOpenMeteoResponse($data, $latitude, $longitude, $locationLabel);
            }

            Log::warning("Weather API returned non-200 HTTP status", [
                'status' => $response->status(),
                'url' => $this->baseUrl,
            ]);

            return $this->fallbackResponse(
                "Layanan data cuaca operasional eksternal sementara tidak dapat dihubungi (HTTP {$response->status()}). Operasional logistik tetap berjalan normal."
            );

        } catch (ConnectionException $e) {
            Log::warning("Weather API connection timeout", [
                'message' => $e->getMessage(),
                'lat' => $latitude,
                'lng' => $longitude,
            ]);

            return $this->fallbackResponse(
                'Permintaan data cuaca eksternal mengalami batas waktu (timeout). Informasi cuaca dilewati secara aman.'
            );

        } catch (Throwable $e) {
            Log::error("Weather API unexpected error", [
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackResponse(
                'Layanan data cuaca operasional eksternal saat ini tidak dapat diakses. Operasional pelacakan kargo tetap berjalan normal.'
            );
        }
    }

    /**
     * Parse and structure Open-Meteo response payload.
     */
    protected function parseOpenMeteoResponse(array $payload, float $latitude, float $longitude, ?string $locationLabel): array
    {
        $current = $payload['current'] ?? null;
        if (!$current) {
            return $this->fallbackResponse('Data telemetri cuaca kosong dari penyedia eksternal.');
        }

        $temp = isset($current['temperature_2m']) ? (float) $current['temperature_2m'] : null;
        $humidity = isset($current['relative_humidity_2m']) ? (int) $current['relative_humidity_2m'] : null;
        $weatherCode = isset($current['weather_code']) ? (int) $current['weather_code'] : 0;
        $windSpeed = isset($current['wind_speed_10m']) ? (float) $current['wind_speed_10m'] : 0.0;
        $windDir = isset($current['wind_direction_10m']) ? (int) $current['wind_direction_10m'] : null;
        $precipitation = isset($current['precipitation']) ? (float) $current['precipitation'] : 0.0;

        $condition = $this->interpretWmoWeatherCode($weatherCode);
        $advisory = $this->generateOperationalAdvisory($weatherCode, $windSpeed, $precipitation);

        return [
            'available' => true,
            'source' => 'Open-Meteo Real-Time Telemetry',
            'location_label' => $locationLabel,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'temperature' => $temp,
            'temperature_unit' => '°C',
            'humidity' => $humidity,
            'wind_speed' => $windSpeed,
            'wind_unit' => 'km/h',
            'wind_direction' => $windDir,
            'weather_code' => $weatherCode,
            'condition_name' => $condition['name'],
            'condition_icon' => $condition['icon'],
            'advisory_level' => $advisory['level'],
            'advisory_badge' => $advisory['badge'],
            'advisory_text' => $advisory['text'],
            'operational_note' => 'Informasi kondisi cuaca riil dari Open-Meteo disajikan sebagai konteks operasional lingkungan navigasi (bukan prediksi keterlambatan AI/ML).',
            'fetched_at' => now()->format('M d, Y H:i'),
        ];
    }

    /**
     * Parse and structure OpenWeatherMap response payload.
     */
    protected function parseOpenWeatherResponse(array $payload, float $latitude, float $longitude, ?string $locationLabel): array
    {
        $main = $payload['main'] ?? null;
        $weather = $payload['weather'][0] ?? null;
        $wind = $payload['wind'] ?? null;

        if (!$main) {
            return $this->fallbackResponse('Data telemetri OpenWeatherMap kosong.');
        }

        $temp = isset($main['temp']) ? (float) $main['temp'] : null;
        $humidity = isset($main['humidity']) ? (int) $main['humidity'] : null;
        $windSpeed = isset($wind['speed']) ? ((float) $wind['speed'] * 3.6) : 0.0; // convert m/s to km/h
        $conditionName = $weather['description'] ?? 'Clear';

        return [
            'available' => true,
            'source' => 'OpenWeatherMap Telemetry',
            'location_label' => $locationLabel,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'temperature' => $temp,
            'temperature_unit' => '°C',
            'humidity' => $humidity,
            'wind_speed' => round($windSpeed, 1),
            'wind_unit' => 'km/h',
            'wind_direction' => $wind['deg'] ?? null,
            'weather_code' => $weather['id'] ?? null,
            'condition_name' => ucwords($conditionName),
            'condition_icon' => 'sun',
            'advisory_level' => 'NORMAL',
            'advisory_badge' => 'bg-emerald-950 text-emerald-400 border border-emerald-800',
            'advisory_text' => 'Kondisi cuaca terpantau normal di sekitar titik lokasi pelacakan.',
            'operational_note' => 'Informasi kondisi cuaca riil dari OpenWeatherMap disajikan sebagai konteks operasional lingkungan navigasi (bukan prediksi keterlambatan AI/ML).',
            'fetched_at' => now()->format('M d, Y H:i'),
        ];
    }

    /**
     * Geocoding lookup by place / port name.
     *
     * @param string $query
     * @param int $count
     * @return array
     */
    public function geocode(string $query, int $count = 5): array
    {
        $trimmed = trim($query);
        if ($trimmed === '') {
            return ['results' => []];
        }

        $cacheKey = 'cargotrace_geocode_' . md5(strtolower($trimmed));

        return Cache::remember($cacheKey, 86400, function () use ($trimmed, $count) {
            try {
                $response = Http::timeout($this->geocodingTimeout)
                    ->acceptJson()
                    ->get($this->geocodingBaseUrl, [
                        'name' => $trimmed,
                        'count' => $count,
                        'language' => 'en',
                        'format' => 'json',
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                return ['results' => []];
            } catch (Throwable $e) {
                Log::warning("Geocoding lookup failed", ['error' => $e->getMessage()]);
                return ['results' => []];
            }
        });
    }

    /**
     * Map WMO weather codes to human-readable labels and visual icons.
     */
    protected function interpretWmoWeatherCode(int $code): array
    {
        return match (true) {
            $code === 0 => [
                'name' => 'Cerah (Clear Sky)',
                'icon' => 'sun',
            ],
            $code === 1 => [
                'name' => 'Sebagian Besar Cerah (Mainly Clear)',
                'icon' => 'sun',
            ],
            $code === 2 => [
                'name' => 'Berawan Sebagian (Partly Cloudy)',
                'icon' => 'cloud-sun',
            ],
            $code === 3 => [
                'name' => 'Mendung / Berawan Tebal (Overcast)',
                'icon' => 'cloud',
            ],
            in_array($code, [45, 48], true) => [
                'name' => 'Kabut Jarak Pandang Terbatas (Fog / Mist)',
                'icon' => 'cloud-fog',
            ],
            in_array($code, [51, 53, 55], true) => [
                'name' => 'Gerimis Ringan (Drizzle)',
                'icon' => 'cloud-drizzle',
            ],
            in_array($code, [61, 63], true) => [
                'name' => 'Hujan Sedang (Moderate Rain)',
                'icon' => 'cloud-rain',
            ],
            $code === 65 => [
                'name' => 'Hujan Lebat (Heavy Rain)',
                'icon' => 'cloud-rain',
            ],
            in_array($code, [71, 73, 75, 77], true) => [
                'name' => 'Salju / Butiran Es (Snowfall)',
                'icon' => 'snowflake',
            ],
            in_array($code, [80, 81, 82], true) => [
                'name' => 'Hujan Deras / Showers',
                'icon' => 'cloud-rain',
            ],
            in_array($code, [95, 96, 99], true) => [
                'name' => 'Badai Petir & Kilat (Thunderstorm)',
                'icon' => 'cloud-lightning',
            ],
            default => [
                'name' => "Kondisi Atmosferik (Kode WMO {$code})",
                'icon' => 'cloud',
            ],
        };
    }

    /**
     * Generate practical operational context advisory.
     */
    protected function generateOperationalAdvisory(int $weatherCode, float $windSpeed, float $precipitation): array
    {
        // High severity weather: Thunderstorms or violent winds
        if (in_array($weatherCode, [95, 96, 99], true) || $windSpeed > 55.0) {
            return [
                'level' => 'WARNING',
                'badge' => 'bg-rose-950 text-rose-300 border border-rose-800',
                'text' => "Peringatan Cuaca Ekstrem: Kecepatan angin mencapai {$windSpeed} km/h atau terdeteksi badai konvektif di koridor. Disarankan memantau perkembangan navigasi dan stabilitas kargo khusus.",
            ];
        }

        // Moderate severity weather: Fog, heavy rain, or moderate winds
        if (in_array($weatherCode, [45, 48, 65, 82], true) || $windSpeed > 35.0 || $precipitation > 10.0) {
            return [
                'level' => 'CAUTION',
                'badge' => 'bg-amber-950 text-amber-300 border border-amber-800',
                'text' => "Perhatian Operasional: Jarak pandang menurun atau angin laut bertiup {$windSpeed} km/h. Terminal handling dan waktu transit feeder mungkin mengalami kelonggaran wajar.",
            ];
        }

        // Normal maritime/terrestrial passage
        return [
            'level' => 'FAVORABLE',
            'badge' => 'bg-emerald-950 text-emerald-400 border border-emerald-800',
            'text' => "Kondisi Meteorologis Aman: Angin berhembus {$windSpeed} km/h dan atmosfer stabil, mendukung kelancaran transit jadwal pengiriman.",
        ];
    }

    /**
     * Return safe fallback array when weather data cannot be retrieved.
     */
    protected function fallbackResponse(string $message, array $extra = []): array
    {
        return array_merge([
            'available' => false,
            'source' => 'CargoTrace Telemetry Layer',
            'message' => $message,
            'operational_note' => 'Konteks operasional cuaca bersifat opsional. Ketiadaan data eksternal tidak mempengaruhi kalkulasi jadwal SLA atau pelacakan kargo.',
        ], $extra);
    }
}
