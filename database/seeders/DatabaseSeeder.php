<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Demo Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'Administrator CargoTrace',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $operator = User::firstOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Logistics Operator',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR,
                'email_verified_at' => now(),
            ]
        );

        $customer1 = User::firstOrCreate(
            ['email' => 'customer@cargotrace.test'],
            [
                'name' => 'Astra Global Tech Logistik',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'email_verified_at' => now(),
            ]
        );

        $customer2 = User::firstOrCreate(
            ['email' => 'customer2@cargotrace.test'],
            [
                'name' => 'EuroNordic Trading BV',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Carriers
        $carriers = [
            [
                'name' => 'Maersk Line',
                'code' => 'MAEU',
                'contact_info' => 'cs-apac@maersk.com | +65 6323 8888',
                'status' => 'active',
            ],
            [
                'name' => 'DHL Global Forwarding',
                'code' => 'DHLG',
                'contact_info' => 'freight-desk@dhl.com | +49 228 1820',
                'status' => 'active',
            ],
            [
                'name' => 'MSC Mediterranean Shipping Company',
                'code' => 'MSCU',
                'contact_info' => 'support@msc.com | +41 22 703 8888',
                'status' => 'active',
            ],
            [
                'name' => 'FedEx Trade Networks',
                'code' => 'FDXN',
                'contact_info' => 'intl-tracking@fedex.com | +1 800 247 4747',
                'status' => 'active',
            ],
            [
                'name' => 'Hapag-Lloyd',
                'code' => 'HPLU',
                'contact_info' => 'hamburg-ops@hlag.com | +49 40 30010',
                'status' => 'active',
            ],
            [
                'name' => 'CMA CGM Group',
                'code' => 'CMAC',
                'contact_info' => 'marseille.desk@cma-cgm.com | +33 4 88 91 90 00',
                'status' => 'active',
            ],
        ];

        $carrierModels = [];
        foreach ($carriers as $c) {
            $carrierModels[$c['code']] = Carrier::firstOrCreate(['code' => $c['code']], $c);
        }

        // 3. Create Locations
        $locations = [
            [
                'name' => 'Port of Singapore (PSA Jurong / Tanjong Pagar)',
                'code' => 'SGSIN',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.290270,
                'longitude' => 103.851959,
            ],
            [
                'name' => 'Port of Rotterdam (Maasvlakte 2)',
                'code' => 'NLRTM',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'latitude' => 51.924420,
                'longitude' => 4.477733,
            ],
            [
                'name' => 'Tanjung Priok International Container Port',
                'code' => 'IDJKT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.101700,
                'longitude' => 106.883300,
            ],
            [
                'name' => 'Port of Shanghai (Yangshan Deep-Water Port)',
                'code' => 'CNSHA',
                'city' => 'Shanghai',
                'country' => 'China',
                'latitude' => 31.230416,
                'longitude' => 121.473701,
            ],
            [
                'name' => 'Port of Los Angeles (San Pedro Bay)',
                'code' => 'USLAX',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'latitude' => 33.743180,
                'longitude' => -118.267250,
            ],
            [
                'name' => 'Port of Hamburg (Altenwerder Hub)',
                'code' => 'DEHAM',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'latitude' => 53.535300,
                'longitude' => 9.987200,
            ],
            [
                'name' => 'Port of Jebel Ali (Terminal 3)',
                'code' => 'AEDXB',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.008900,
                'longitude' => 55.060300,
            ],
            [
                'name' => 'Busan New Port (PNIT Container Terminal)',
                'code' => 'KRPUS',
                'city' => 'Busan',
                'country' => 'South Korea',
                'latitude' => 35.102800,
                'longitude' => 129.040300,
            ],
        ];

        $locationModels = [];
        foreach ($locations as $loc) {
            $locationModels[$loc['code']] = Location::firstOrCreate(['code' => $loc['code']], $loc);
        }

        // 4. Sample Realistic Shipments

        // --- SHIPMENT 1: Singapore to Rotterdam (In Transit, On Time) ---
        $sh1 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-SG-RT01'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carrierModels['MAEU']->id,
                'origin_location_id' => $locationModels['SGSIN']->id,
                'destination_location_id' => $locationModels['NLRTM']->id,
                'cargo_type' => 'High-Tech Semiconductor Wafers & Sensors',
                'weight' => 14500.00,
                'volume' => 68.50,
                'expected_departure' => Carbon::now()->subDays(5)->setHour(8)->setMinute(0),
                'expected_arrival' => Carbon::now()->addDays(9)->setHour(18)->setMinute(0),
                'estimated_arrival' => Carbon::now()->addDays(9)->setHour(18)->setMinute(0),
                'current_status' => 'in_transit',
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'none',
            ]
        );

        $sh1->items()->delete();
        $sh1->items()->createMany([
            ['item_name' => 'Photolithography Optics Module', 'quantity' => 4, 'weight' => 3200.00, 'description' => 'Precision EUV optical components (Class 10 Cleanroom packed)'],
            ['item_name' => 'Silicon Microprocessor Trays', 'quantity' => 180, 'weight' => 7800.00, 'description' => '3nm FinFET wafer dies in sealed inert argon containers'],
            ['item_name' => 'Cryogenic Thermal Sensor Kits', 'quantity' => 60, 'weight' => 3500.00, 'description' => 'Industrial temperature feedback units'],
        ]);

        $sh1->trackingEvents()->delete();
        $sh1->trackingEvents()->createMany([
            [
                'status' => 'booked',
                'event_type' => 'booking_created',
                'event_time' => Carbon::now()->subDays(7)->setHour(10)->setMinute(30),
                'location_id' => $locationModels['SGSIN']->id,
                'latitude' => 1.290270,
                'longitude' => 103.851959,
                'description' => 'Booking confirmed with Maersk Line. Container seals verified (#MS-998124).',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'departed',
                'event_type' => 'departure',
                'event_time' => Carbon::now()->subDays(5)->setHour(8)->setMinute(15),
                'location_id' => $locationModels['SGSIN']->id,
                'latitude' => 1.290270,
                'longitude' => 103.851959,
                'description' => 'Vessel "Maersk Mc-Kinney Moller" unmoored from PSA Tanjong Pagar berth 4.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'in_transit',
                'event_type' => 'waypoint_cleared',
                'event_time' => Carbon::now()->subDays(3)->setHour(14)->setMinute(0),
                'location_id' => null,
                'latitude' => 5.923000,
                'longitude' => 80.512000,
                'description' => 'Passed Galle Maritime Corridor (South of Sri Lanka). Telemetry and reefer temperatures optimal (-20°C).',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'in_transit',
                'event_type' => 'telemetry_ping',
                'event_time' => Carbon::now()->subHours(6),
                'location_id' => null,
                'latitude' => 12.540000,
                'longitude' => 54.010000,
                'description' => 'Entering Gulf of Aden corridor. Speed: 19.8 knots. AIS tracking active.',
                'created_by' => $operator->id,
            ],
        ]);

        // --- SHIPMENT 2: Shanghai to Los Angeles (Delayed - Weather Exception) ---
        $sh2 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-SH-LA02'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carrierModels['MSCU']->id,
                'origin_location_id' => $locationModels['CNSHA']->id,
                'destination_location_id' => $locationModels['USLAX']->id,
                'cargo_type' => 'Lithium-ion Battery Modules & Energy Storage Packs',
                'weight' => 26800.00,
                'volume' => 84.00,
                'expected_departure' => Carbon::now()->subDays(8)->setHour(9)->setMinute(0),
                'expected_arrival' => Carbon::now()->addDays(3)->setHour(12)->setMinute(0),
                'estimated_arrival' => Carbon::now()->addDays(5)->setHour(12)->setMinute(0), // +48 hours
                'current_status' => 'exception',
                'is_delayed' => true,
                'delay_hours' => 48,
                'delay_severity' => 'high',
            ]
        );

        $sh2->items()->delete();
        $sh2->items()->createMany([
            ['item_name' => 'Prismatic LiFePO4 Cell Racks', 'quantity' => 12, 'weight' => 18400.00, 'description' => 'UN 3480 Class 9 Dangerous Goods certified container'],
            ['item_name' => 'Smart BMS Inverter Controllers', 'quantity' => 48, 'weight' => 5400.00, 'description' => 'Automotive grade power management micro-units'],
            ['item_name' => 'Fire Retardant Aerogel Spacers', 'quantity' => 120, 'weight' => 3000.00, 'description' => 'Thermal barrier packing materials'],
        ]);

        $sh2->trackingEvents()->delete();
        $sh2->trackingEvents()->createMany([
            [
                'status' => 'departed',
                'event_type' => 'departure',
                'event_time' => Carbon::now()->subDays(8)->setHour(11)->setMinute(20),
                'location_id' => $locationModels['CNSHA']->id,
                'latitude' => 31.230416,
                'longitude' => 121.473701,
                'description' => 'Cleared customs at Yangshan and unmoored from berth A-2.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'in_transit',
                'event_type' => 'checkpoint',
                'event_time' => Carbon::now()->subDays(5)->setHour(16)->setMinute(0),
                'location_id' => null,
                'latitude' => 32.880000,
                'longitude' => 138.920000,
                'description' => 'Transiting South of Tokyo Bay. Sea conditions moderate.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'delayed',
                'event_type' => 'weather_alert',
                'event_time' => Carbon::now()->subDays(2)->setHour(10)->setMinute(0),
                'location_id' => null,
                'latitude' => 36.450000,
                'longitude' => 165.120000,
                'description' => 'Severe Pacific cyclone detected on great-circle route. Master vessel decided on 400nm southern detour.',
                'created_by' => $operator->id,
            ],
        ]);

        $sh2->exceptions()->delete();
        $sh2->exceptions()->create([
            'type' => 'weather_delay',
            'severity' => 'high',
            'status' => 'open',
            'description' => 'Severe Category 4 North Pacific cyclone forced vessel MSC Gülsün to alter course 400 nautical miles south, resulting in approximately 48 hours transit delay.',
            'detected_at' => Carbon::now()->subDays(2)->setHour(10)->setMinute(0),
            'resolved_at' => null,
            'reported_by' => $operator->id,
        ]);

        // --- SHIPMENT 3: Jakarta to Singapore (Delivered) ---
        $sh3 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-JK-SG03'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carrierModels['DHLG']->id,
                'origin_location_id' => $locationModels['IDJKT']->id,
                'destination_location_id' => $locationModels['SGSIN']->id,
                'cargo_type' => 'Specialty Robusta Coffee & Vanilla Extract',
                'weight' => 8400.00,
                'volume' => 32.00,
                'expected_departure' => Carbon::now()->subDays(10)->setHour(8)->setMinute(0),
                'expected_arrival' => Carbon::now()->subDays(6)->setHour(14)->setMinute(0),
                'estimated_arrival' => Carbon::now()->subDays(6)->setHour(13)->setMinute(45),
                'current_status' => 'delivered',
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'none',
            ]
        );

        $sh3->items()->delete();
        $sh3->items()->createMany([
            ['item_name' => 'Organic Java Preanger Green Beans', 'quantity' => 120, 'weight' => 7200.00, 'description' => 'Jute bags in desiccated shipping container'],
            ['item_name' => 'Gourmet Bourbon Vanilla Pods', 'quantity' => 20, 'weight' => 1200.00, 'description' => 'Vacuum sealed aluminum foil boxes'],
        ]);

        $sh3->trackingEvents()->delete();
        $sh3->trackingEvents()->createMany([
            [
                'status' => 'departed',
                'event_type' => 'departure',
                'event_time' => Carbon::now()->subDays(10)->setHour(9)->setMinute(0),
                'location_id' => $locationModels['IDJKT']->id,
                'latitude' => -6.101700,
                'longitude' => 106.883300,
                'description' => 'Vessel departed Tanjung Priok Gateway Terminal.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'in_transit',
                'event_type' => 'checkpoint',
                'event_time' => Carbon::now()->subDays(8)->setHour(15)->setMinute(0),
                'location_id' => null,
                'latitude' => -1.250000,
                'longitude' => 105.120000,
                'description' => 'Transiting Bangka Strait.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'arrived',
                'event_type' => 'arrival',
                'event_time' => Carbon::now()->subDays(6)->setHour(9)->setMinute(30),
                'location_id' => $locationModels['SGSIN']->id,
                'latitude' => 1.290270,
                'longitude' => 103.851959,
                'description' => 'Arrived at Port of Singapore. Berthing complete.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'customs_cleared',
                'event_type' => 'customs',
                'event_time' => Carbon::now()->subDays(6)->setHour(11)->setMinute(0),
                'location_id' => $locationModels['SGSIN']->id,
                'latitude' => 1.290270,
                'longitude' => 103.851959,
                'description' => 'Singapore Customs phytosanitary & import tax inspection cleared.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'delivered',
                'event_type' => 'delivery_completed',
                'event_time' => Carbon::now()->subDays(6)->setHour(13)->setMinute(45),
                'location_id' => $locationModels['SGSIN']->id,
                'latitude' => 1.290270,
                'longitude' => 103.851959,
                'description' => 'Final delivery signed and accepted by consignee at Tuas Logistics Hub. Electronic POD logged.',
                'created_by' => $operator->id,
            ],
        ]);

        // --- SHIPMENT 4: Dubai to Hamburg (Belongs to Customer 2 - tests isolation) ---
        $sh4 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-DX-HM04'],
            [
                'customer_id' => $customer2->id,
                'carrier_id' => $carrierModels['HPLU']->id,
                'origin_location_id' => $locationModels['AEDXB']->id,
                'destination_location_id' => $locationModels['DEHAM']->id,
                'cargo_type' => 'Precision Machined Industrial Turbine Components',
                'weight' => 18900.00,
                'volume' => 52.00,
                'expected_departure' => Carbon::now()->subDays(4)->setHour(7)->setMinute(30),
                'expected_arrival' => Carbon::now()->addDays(8)->setHour(16)->setMinute(0),
                'estimated_arrival' => Carbon::now()->addDays(8)->setHour(16)->setMinute(0),
                'current_status' => 'in_transit',
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'none',
            ]
        );

        $sh4->items()->delete();
        $sh4->items()->createMany([
            ['item_name' => 'High-Pressure Turbine Rotor Shaft', 'quantity' => 2, 'weight' => 12400.00, 'description' => 'Titanium alloy forged shaft in shock-dampening cradles'],
            ['item_name' => 'Combustion Chamber Liner Assemblies', 'quantity' => 16, 'weight' => 6500.00, 'description' => 'Ceramic thermal barrier coated assemblies'],
        ]);

        $sh4->trackingEvents()->delete();
        $sh4->trackingEvents()->createMany([
            [
                'status' => 'departed',
                'event_type' => 'departure',
                'event_time' => Carbon::now()->subDays(4)->setHour(8)->setMinute(0),
                'location_id' => $locationModels['AEDXB']->id,
                'latitude' => 25.008900,
                'longitude' => 55.060300,
                'description' => 'Departed Jebel Ali Port terminal 3 aboard Hapag-Lloyd vessel.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'in_transit',
                'event_type' => 'checkpoint',
                'event_time' => Carbon::now()->subDays(1)->setHour(12)->setMinute(0),
                'location_id' => null,
                'latitude' => 27.500000,
                'longitude' => 34.200000,
                'description' => 'Approaching Strait of Tiran / Red Sea northbound convoy.',
                'created_by' => $operator->id,
            ],
        ]);

        // --- SHIPMENT 5: Busan to Rotterdam (Customs Hold Exception) ---
        $sh5 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-BU-RT05'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carrierModels['CMAC']->id,
                'origin_location_id' => $locationModels['KRPUS']->id,
                'destination_location_id' => $locationModels['NLRTM']->id,
                'cargo_type' => 'Automotive EV Inverters & Powertrain Assemblies',
                'weight' => 16200.00,
                'volume' => 58.00,
                'expected_departure' => Carbon::now()->subDays(6)->setHour(10)->setMinute(0),
                'expected_arrival' => Carbon::now()->addDays(7)->setHour(15)->setMinute(0),
                'estimated_arrival' => Carbon::now()->addDays(8)->setHour(15)->setMinute(0),
                'current_status' => 'customs_hold',
                'is_delayed' => true,
                'delay_hours' => 24,
                'delay_severity' => 'medium',
            ]
        );

        $sh5->items()->delete();
        $sh5->items()->createMany([
            ['item_name' => 'Silicon Carbide EV Inverters 800V', 'quantity' => 140, 'weight' => 9800.00, 'description' => 'High-voltage drive inverter units with liquid cooling ports'],
            ['item_name' => 'Permanent Magnet Synchronous Motors', 'quantity' => 40, 'weight' => 6400.00, 'description' => 'Traction motors for commercial electric vans'],
        ]);

        $sh5->trackingEvents()->delete();
        $sh5->trackingEvents()->createMany([
            [
                'status' => 'departed',
                'event_type' => 'departure',
                'event_time' => Carbon::now()->subDays(6)->setHour(10)->setMinute(30),
                'location_id' => $locationModels['KRPUS']->id,
                'latitude' => 35.102800,
                'longitude' => 129.040300,
                'description' => 'Departed Busan PNIT terminal.',
                'created_by' => $operator->id,
            ],
            [
                'status' => 'customs_hold',
                'event_type' => 'customs_hold',
                'event_time' => Carbon::now()->subHours(18),
                'location_id' => $locationModels['SGSIN']->id,
                'latitude' => 1.290270,
                'longitude' => 103.851959,
                'description' => 'Transshipment container flagged at Singapore free-trade inspection zone for HS code tariff verification.',
                'created_by' => $operator->id,
            ],
        ]);

        $sh5->exceptions()->delete();
        $sh5->exceptions()->create([
            'type' => 'customs_hold',
            'severity' => 'medium',
            'status' => 'under_review',
            'description' => 'Customs documentation audit: Dual-use electronic component declaration requires supplementary manufacturer compliance certificate.',
            'detected_at' => Carbon::now()->subHours(18),
            'resolved_at' => null,
            'reported_by' => $operator->id,
        ]);

        // 5. Notifications
        Notification::create([
            'user_id' => $customer1->id,
            'shipment_id' => $sh2->id,
            'title' => 'Weather Exception Alert: CT-2026-SH-LA02',
            'message' => 'Your shipment from Shanghai to Los Angeles has encountered severe weather in the Pacific. Vessel diverted 400nm south; revised ETA +48h.',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $customer1->id,
            'shipment_id' => $sh5->id,
            'title' => 'Documentation Notice: CT-2026-BU-RT05',
            'message' => 'Customs inspection hold at Singapore transshipment hub. Customs agent is reviewing HS code documentation.',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $customer1->id,
            'shipment_id' => $sh3->id,
            'title' => 'Shipment Delivered: CT-2026-JK-SG03',
            'message' => 'Shipment #CT-2026-JK-SG03 has been successfully delivered and signed at Tuas Logistics Hub Singapore.',
            'read_at' => Carbon::now()->subDays(5),
        ]);

        Notification::create([
            'user_id' => $operator->id,
            'shipment_id' => $sh2->id,
            'title' => '[Action Required] Review Delay Impact on CT-2026-SH-LA02',
            'message' => 'Weather exception logged on Pacific transit. Re-route approved by maritime operations desk.',
            'read_at' => null,
        ]);

        // 6. Audit Logs
        AuditLog::log($admin->id, 'system_bootstrap', 'System', null, null, ['platform' => 'CargoTrace Global Visibility', 'version' => '1.0']);
        AuditLog::log($operator->id, 'create_shipment', 'Shipment', $sh1->id, null, ['shipment_number' => $sh1->shipment_number, 'status' => $sh1->current_status]);
        AuditLog::log($operator->id, 'report_exception', 'ShipmentException', 1, null, ['shipment_number' => $sh2->shipment_number, 'type' => 'weather_delay', 'severity' => 'high']);
        AuditLog::log($operator->id, 'create_tracking_event', 'Shipment', $sh3->id, ['status' => 'arrived'], ['status' => 'delivered']);
    }
}
