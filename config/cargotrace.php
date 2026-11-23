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
];
