<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherAndMapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;
    protected Shipment $shipment;
    protected WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weatherService = app(WeatherService::class);

        $this->operator = User::create([
            'name' => 'Logistics Operator',
            'email' => 'operator@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->customer = User::create([
            'name' => 'Cargo Customer',
            'email' => 'customer@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->carrier = Carrier::create([
            'name' => 'Maersk Line',
            'code' => 'MAEU',
            'status' => 'active',
        ]);

        $this->origin = Location::create([
            'name' => 'Port of Singapore',
            'code' => 'SGSIN',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'latitude' => 1.290270,
            'longitude' => 103.851959,
        ]);

        $this->destination = Location::create([
            'name' => 'Port of Rotterdam',
            'code' => 'NLRTM',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.924420,
            'longitude' => 4.477733,
        ]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-MAP-TEST-01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Robotics Components',
            'weight' => 2500,
            'volume' => 12,
            'expected_departure' => Carbon::now()->subDays(2),
            'expected_arrival' => Carbon::now()->addDays(5),
            'current_status' => 'in_transit',
        ]);
    }

    public function test_weather_service_fetches_real_time_metrics_successfully(): void
    {
        $baseUrl = config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        Http::fake([
            $baseUrl . '*' => Http::response([
                'latitude' => 1.29,
                'longitude' => 103.85,
                'current' => [
                    'time' => '2026-11-23T10:00',
                    'temperature_2m' => 30.5,
                    'relative_humidity_2m' => 74,
                    'weather_code' => 2, // Partly cloudy
                    'wind_speed_10m' => 15.2,
                    'wind_direction_10m' => 90,
                    'precipitation' => 0.0,
                ],
            ], 200),
        ]);

        $result = $this->weatherService->getWeatherForCoordinates(1.29027, 103.85195, 'Port of Singapore');

        $this->assertTrue($result['available']);
        $this->assertEquals(30.5, $result['temperature']);
        $this->assertEquals(74, $result['humidity']);
        $this->assertEquals(15.2, $result['wind_speed']);
        $this->assertStringContainsString('Partly Cloudy', $result['condition_name']);
        $this->assertStringContainsString('bukan prediksi keterlambatan AI/ML', $result['operational_note']);
    }

    public function test_weather_service_handles_http_error_gracefully_with_fallback(): void
    {
        $baseUrl = config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        Http::fake([
            $baseUrl . '*' => Http::response([
                'error' => true,
                'reason' => 'Rate limit exceeded or upstream error',
            ], 500),
        ]);

        $result = $this->weatherService->getWeatherForCoordinates(1.29, 103.85);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('HTTP 500', $result['message']);
        $this->assertStringContainsString('Operasional logistik tetap berjalan normal', $result['message']);
    }

    public function test_weather_service_handles_connection_timeout_gracefully(): void
    {
        $baseUrl = config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        Http::fake([
            $baseUrl . '*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 5000 milliseconds');
            },
        ]);

        $result = $this->weatherService->getWeatherForCoordinates(1.29, 103.85);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('timeout', strtolower($result['message']));
    }

    public function test_weather_service_resolves_shipment_coordinates_from_tracking_event(): void
    {
        // Add tracking event with specific telemetry coordinates
        TrackingEvent::create([
            'shipment_id' => $this->shipment->id,
            'status' => 'in_transit',
            'event_type' => 'waypoint_telemetry',
            'event_time' => Carbon::now()->subHours(2),
            'latitude' => 5.923,
            'longitude' => 80.512,
            'description' => 'Offshore Galle maritime lane waypoint.',
            'created_by' => $this->operator->id,
        ]);

        $baseUrl = config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        Http::fake([
            $baseUrl . '*' => Http::response([
                'current' => [
                    'temperature_2m' => 28.0,
                    'relative_humidity_2m' => 80,
                    'weather_code' => 61,
                    'wind_speed_10m' => 22.0,
                    'wind_direction_10m' => 180,
                    'precipitation' => 2.5,
                ],
            ], 200),
        ]);

        $result = $this->weatherService->getWeatherForShipment($this->shipment);

        $this->assertTrue($result['available']);
        $this->assertEquals(5.923, $result['latitude']);
        $this->assertEquals(80.512, $result['longitude']);
    }

    public function test_shipment_show_page_renders_map_and_weather_telemetry(): void
    {
        $baseUrl = config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        Http::fake([
            $baseUrl . '*' => Http::response([
                'current' => [
                    'temperature_2m' => 29.0,
                    'relative_humidity_2m' => 70,
                    'weather_code' => 0, // Clear
                    'wind_speed_10m' => 10.0,
                    'wind_direction_10m' => 45,
                    'precipitation' => 0.0,
                ],
            ], 200),
        ]);

        // Add a tracking event
        TrackingEvent::create([
            'shipment_id' => $this->shipment->id,
            'status' => 'in_transit',
            'event_type' => 'checkpoint',
            'event_time' => Carbon::now()->subHours(1),
            'latitude' => 2.5,
            'longitude' => 102.1,
            'description' => 'Transiting Malacca Strait.',
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->customer)->get(route('shipments.show', $this->shipment));

        $response->assertStatus(200);

        // Check OpenStreetMap attribution
        $response->assertSee('OpenStreetMap');
        $response->assertSee('https://www.openstreetmap.org/copyright', false);

        // Check Map Container
        $response->assertSee('id="shipmentMap"', false);

        // Check Weather Telemetry Card
        $response->assertSee('Maritime Weather Telemetry');
        $response->assertSee('29 °C');
        $response->assertSee('70%');
        $response->assertSee('10 km/h');
        $response->assertSee('bukan prediksi keterlambatan AI/ML');

        // Check Coordinates plotted in JavaScript points
        $response->assertSee('1.29027');
        $response->assertSee('103.851959');
        $response->assertSee('51.92442');
        $response->assertSee('4.477733');
    }

    public function test_shipment_show_page_handles_external_weather_api_failure_gracefully(): void
    {
        $baseUrl = config('cargotrace.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        Http::fake([
            $baseUrl . '*' => Http::response(['error' => 'Service Unavailable'], 503),
        ]);

        $response = $this->actingAs($this->operator)->get(route('shipments.show', $this->shipment));

        // The page must still return 200 OK without 500 error
        $response->assertStatus(200);

        // Graceful fallback message is displayed
        $response->assertSee('Operational Weather Telemetry');
        $response->assertSee('Offline / Standby');
        $response->assertSee('HTTP 503');

        // Map is still fully rendered
        $response->assertSee('id="shipmentMap"', false);
        $response->assertSee('OpenStreetMap');
    }
}
