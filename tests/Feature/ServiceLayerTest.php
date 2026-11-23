<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceLayerTest extends TestCase
{
    use RefreshDatabase;

    protected EtaService $etaService;
    protected TrackingService $trackingService;
    protected ExceptionService $exceptionService;
    protected ShipmentService $shipmentService;

    protected User $admin;
    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etaService = app(EtaService::class);
        $this->trackingService = app(TrackingService::class);
        $this->exceptionService = app(ExceptionService::class);
        $this->shipmentService = app(ShipmentService::class);

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@ct.test', 'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN]);
        $this->operator = User::create(['name' => 'Operator', 'email' => 'op@ct.test', 'password' => bcrypt('password'), 'role' => User::ROLE_OPERATOR]);
        $this->customer = User::create(['name' => 'Customer', 'email' => 'cust@ct.test', 'password' => bcrypt('password'), 'role' => User::ROLE_CUSTOMER]);

        $this->carrier = Carrier::create(['name' => 'DHL Global', 'code' => 'DHLG', 'status' => 'active']);
        $this->origin = Location::create(['name' => 'Jakarta Hub', 'code' => 'IDJKT', 'city' => 'Jakarta', 'country' => 'Indonesia', 'latitude' => -6.1, 'longitude' => 106.8]);
        $this->destination = Location::create(['name' => 'Singapore PSA', 'code' => 'SGSIN', 'city' => 'Singapore', 'country' => 'Singapore', 'latitude' => 1.29, 'longitude' => 103.85]);
    }

    public function test_eta_service_evaluates_delay_and_severity(): void
    {
        $expected = Carbon::now()->addDays(5);
        $shipment = new Shipment([
            'expected_arrival' => $expected,
            'current_status' => 'in_transit',
        ]);

        // On time
        $resultOnTime = $this->etaService->evaluateDelay($shipment, $expected->copy()->subHours(2));
        $this->assertFalse($resultOnTime['is_delayed']);
        $this->assertEquals(0, $resultOnTime['delay_hours']);
        $this->assertEquals('none', $resultOnTime['delay_severity']);

        // 10 hours delay -> low
        $resultLow = $this->etaService->evaluateDelay($shipment, $expected->copy()->addHours(10));
        $this->assertTrue($resultLow['is_delayed']);
        $this->assertEquals('low', $resultLow['delay_severity']);

        // 24 hours delay -> medium
        $resultMed = $this->etaService->evaluateDelay($shipment, $expected->copy()->addHours(24));
        $this->assertEquals('medium', $resultMed['delay_severity']);

        // 48 hours delay -> high
        $resultHigh = $this->etaService->evaluateDelay($shipment, $expected->copy()->addHours(48));
        $this->assertEquals('high', $resultHigh['delay_severity']);

        // 80 hours delay -> critical
        $resultCrit = $this->etaService->evaluateDelay($shipment, $expected->copy()->addHours(80));
        $this->assertEquals('critical', $resultCrit['delay_severity']);
    }

    public function test_shipment_service_creates_shipment_with_items_and_audit(): void
    {
        $data = [
            'shipment_number' => 'CT-SRV-TEST',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Medical Equipment',
            'weight' => 1200,
            'volume' => 15,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(4),
            'items' => [
                ['item_name' => 'Ventilator Kits', 'quantity' => 20, 'weight' => 600, 'description' => 'Fragile'],
                ['item_name' => 'Monitoring Consoles', 'quantity' => 10, 'weight' => 600, 'description' => 'Sterile'],
            ],
        ];

        $shipment = $this->shipmentService->createShipment($data, $this->operator);

        $this->assertDatabaseHas('shipments', ['shipment_number' => 'CT-SRV-TEST']);
        $this->assertDatabaseHas('shipment_items', ['item_name' => 'Ventilator Kits', 'shipment_id' => $shipment->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->customer->id, 'shipment_id' => $shipment->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create_shipment', 'entity_id' => $shipment->id]);
    }

    public function test_tracking_service_updates_status_and_dispatches_notification(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-TRACK-01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Coffee',
            'weight' => 800,
            'volume' => 8,
            'expected_departure' => now()->subDay(),
            'expected_arrival' => now()->addDays(2),
            'current_status' => 'booked',
        ]);

        $event = $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'event_type' => 'departure',
            'event_time' => now(),
            'location_id' => $this->origin->id,
            'description' => 'Vessel has departed origin port.',
        ], $this->operator);

        $this->assertEquals('in_transit', $shipment->fresh()->current_status);
        $this->assertDatabaseHas('tracking_events', ['id' => $event->id, 'status' => 'in_transit']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'shipment_id' => $shipment->id,
        ]);
    }

    public function test_exception_service_logs_and_resolves_exceptions(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-EXC-01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Automotive Parts',
            'weight' => 5000,
            'volume' => 20,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(3),
            'current_status' => 'in_transit',
        ]);

        $exception = $this->exceptionService->logException($shipment, [
            'type' => 'weather_delay',
            'severity' => 'high',
            'description' => 'Tropical cyclone in maritime lane',
            'detected_at' => now(),
        ], $this->operator);

        $this->assertEquals('exception', $shipment->fresh()->current_status);
        $this->assertTrue($shipment->fresh()->is_delayed);
        $this->assertEquals('open', $exception->status);

        // Resolve
        $resolved = $this->exceptionService->resolveException($exception, [
            'resolution_notes' => 'Storm bypassed, vessel resumed standard course.',
        ], $this->operator);

        $this->assertEquals('resolved', $resolved->status);
        $this->assertEquals('in_transit', $shipment->fresh()->current_status);
    }
}
