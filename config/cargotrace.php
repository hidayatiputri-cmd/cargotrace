<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Delay Threshold (Hours)
    |--------------------------------------------------------------------------
    |
    | The threshold in hours beyond expected_arrival before a shipment is
    | classified as delayed. Configurable per group scenario via .env.
    |
    */
    'delay_threshold_hours' => (int) env('DELAY_THRESHOLD_HOURS', 12),

    /*
    |--------------------------------------------------------------------------
    | Supported Exception Types
    |--------------------------------------------------------------------------
    |
    | The canonical list of operational exceptions supported by CargoTrace.
    |
    */
    'exception_types' => [
        'CUSTOMS_HOLD' => 'Customs Hold / Regulatory Inspection',
        'WEATHER_DELAY' => 'Weather Delay (Storm / High Swells)',
        'PORT_CONGESTION' => 'Port Terminal & Berth Congestion',
        'DAMAGED' => 'Cargo or Container Packaging Damage',
        'MISROUTED' => 'Course Deviation / Misrouted Transshipment',
        'DOCUMENTATION_ISSUE' => 'Manifest / HS Code / Paperwork Issue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Status Workflow
    |--------------------------------------------------------------------------
    |
    | OPEN -> INVESTIGATING -> RESOLVED
    |
    */
    'exception_statuses' => [
        'OPEN' => 'Open Incident',
        'INVESTIGATING' => 'Investigation in Progress',
        'RESOLVED' => 'Resolved & Cleared',
    ],

    /*
    |--------------------------------------------------------------------------
    | External Weather & Geocoding API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external weather telemetry (Open-Meteo or OpenWeatherMap)
    | using Laravel HTTP Client. Values are loaded strictly from .env.
    |
    */
    'weather' => [
        'base_url' => env('WEATHER_API_BASE_URL', 'https://api.open-meteo.com/v1/forecast'),
        'api_key' => env('WEATHER_API_KEY', null),
        'timeout' => (int) env('WEATHER_API_TIMEOUT', 5), // in seconds
        'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 900), // in seconds (15 min)
    ],

    'geocoding' => [
        'base_url' => env('GEOCODING_API_BASE_URL', 'https://geocoding-api.open-meteo.com/v1/search'),
        'timeout' => (int) env('GEOCODING_API_TIMEOUT', 5),
    ],
];
