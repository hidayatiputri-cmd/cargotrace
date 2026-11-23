<?php

namespace Tests\Feature;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShipmentLifecycleAndTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer1;
    protected User $customer2;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;
    protected ShipmentService $shipmentService;
    protected TrackingService $trackingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentService = app(ShipmentService::class);
        $this->trackingService = app(TrackingService::class);

        $this->admin = User::create([
            'name' => 'Admin Controller',
            'email' => 'admin@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->operator = User::create([
            'name' => 'Logistics Operator',
            'email' => 'operator@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->customer1 = User::create([
            'name' => 'PT Consignee Satu',
            'email' => 'c1@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->customer2 = User::create([
            'name' => 'PT Consignee Dua',
            'email' => 'c2@ct.test',
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
    }

    public function test_valid_sequential_lifecycle_progression(): void
    {
        // 1. Initial creation: CREATED
        $shipment = $this->shipmentService->createShipment([
            'shipment_number' => 'CT-LIFECYCLE-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Medical Sensors',
            'weight' => 500,
            'volume' => 5,
            'expected_departure' => Carbon::now()->subDays(4),
            'expected_arrival' => Carbon::now()->addDays(5),
            'initial_event_time' => Carbon::now()->subDays(4),
            'current_status' => 'created',
            'items' => [
                ['item_name' => 'Biosensor Chips', 'quantity' => 100, 'weight' => 500, 'description' => 'Cold-chain'],
            ],
        ], $this->operator);

        $this->assertEquals('created', $shipment->current_status);

        // 2. CREATED -> BOOKED
        $ev1 = $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'booked',
            'event_type' => 'booking_confirmed',
            'event_time' => Carbon::now()->subDays(2),
            'description' => 'Booking confirmed with Maersk Line.',
        ], $this->operator);

        $this->assertEquals('booked', $shipment->fresh()->current_status);

        // 3. BOOKED -> PICKED_UP
        $ev2 = $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'picked_up',
            'event_type' => 'carrier_pickup',
            'event_time' => Carbon::now()->subDays(1),
            'location_id' => $this->origin->id,
            'description' => 'Consignment picked up at origin logistics facility.',
        ], $this->operator);

        $this->assertEquals('picked_up', $shipment->fresh()->current_status);

        // 4. PICKED_UP -> IN_TRANSIT
        $ev3 = $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'event_type' => 'departure',
            'event_time' => Carbon::now()->subHours(10),
            'latitude' => 2.50,
            'longitude' => 102.10,
            'description' => 'Vessel unmoored and navigating Malacca Strait.',
        ], $this->operator);

        $this->assertEquals('in_transit', $shipment->fresh()->current_status);

        // 5. IN_TRANSIT -> DELIVERED (Terminal)
        $ev4 = $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'delivered',
            'event_type' => 'delivery_completed',
            'event_time' => Carbon::now()->subHours(1),
            'location_id' => $this->destination->id,
            'description' => 'Cargo received and signed by consignee at Rotterdam Maasvlakte.',
        ], $this->operator);

        $this->assertEquals('delivered', $shipment->fresh()->current_status);

        // Verify that 5 distinct historical tracking events exist (append-only)
        $this->assertEquals(5, $shipment->trackingEvents()->count());
    }

    public function test_invalid_lifecycle_transition_is_rejected(): void
    {
        $shipment = $this->shipmentService->createShipment([
            'shipment_number' => 'CT-INVALID-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Hardware',
            'weight' => 200,
            'volume' => 2,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
            'current_status' => 'created',
        ], $this->operator);

        // Direct jump from CREATED -> IN_TRANSIT is prohibited
        $this->expectException(ValidationException::class);

        $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'description' => 'Attempting illegal transition to in_transit directly from created.',
        ], $this->operator);
    }

    public function test_delivered_terminal_status_cannot_be_reverted_without_special_procedure(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-DELIVERED-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Robotics',
            'weight' => 1000,
            'volume' => 10,
            'expected_departure' => now()->subDays(5),
            'expected_arrival' => now()->subDay(),
            'current_status' => 'delivered',
        ]);

        // Attempting to revert from DELIVERED back to IN_TRANSIT without procedure must fail
        $this->expectException(ValidationException::class);

        $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'description' => 'Trying to reopen delivered shipment.',
        ], $this->operator);
    }

    public function test_administrator_can_revert_delivered_status_with_special_procedure(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-REVERSAL-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Robotics',
            'weight' => 1000,
            'volume' => 10,
            'expected_departure' => now()->subDays(5),
            'expected_arrival' => now()->subDay(),
            'current_status' => 'delivered',
        ]);

        // Special administrative reversal procedure
        $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'description' => 'Delivery disputed by customer, cargo returned to transit custody.',
            'allow_terminal_reversal' => true,
            'reversal_reason' => 'Administrative correction: Consignee warehouse was closed, cargo remaining on feeder vessel.',
        ], $this->admin);

        $this->assertEquals('in_transit', $shipment->fresh()->current_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'terminal_status_reversal',
            'entity_id' => $shipment->id,
        ]);
    }

    public function test_historical_tracking_events_order_validation(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-TIME-ORDER-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Textiles',
            'weight' => 300,
            'volume' => 3,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(3),
            'current_status' => 'in_transit',
        ]);

        // First event at 12:00
        $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'event_type' => 'checkpoint',
            'event_time' => Carbon::now()->subHours(5),
            'description' => 'Passed checkpoint Alpha.',
        ], $this->operator);

        // Attempt to insert event with earlier timestamp (10:00) -> must fail
        $this->expectException(ValidationException::class);

        $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'event_type' => 'checkpoint',
            'event_time' => Carbon::now()->subHours(8), // Earlier than subHours(5)
            'description' => 'Attempting back-dated out-of-order event.',
        ], $this->operator);
    }

    public function test_current_location_from_latest_valid_event(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-LOCATION-TEST-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Electronics',
            'weight' => 600,
            'volume' => 4,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(5),
            'current_status' => 'in_transit',
        ]);

        // Event with coordinates
        $this->trackingService->addTrackingEvent($shipment, [
            'status' => 'in_transit',
            'event_type' => 'telemetry_ping',
            'event_time' => Carbon::now()->subHours(2),
            'latitude' => 5.923000,
            'longitude' => 80.512000,
            'description' => 'Transiting Indian Ocean corridor.',
        ], $this->operator);

        $currentDesc = $shipment->fresh()->current_location_description;
        $this->assertStringContainsString('5.9230', $currentDesc);
        $this->assertStringContainsString('80.5120', $currentDesc);
    }

    public function test_distinguishing_normal_and_exception_events(): void
    {
        $normalEvent = new TrackingEvent([
            'status' => 'in_transit',
            'event_type' => 'checkpoint',
        ]);
        $this->assertFalse($normalEvent->isExceptionEvent());

        $weatherEvent = new TrackingEvent([
            'status' => 'delayed',
            'event_type' => 'weather_alert',
        ]);
        $this->assertTrue($weatherEvent->isExceptionEvent());

        $customsEvent = new TrackingEvent([
            'status' => 'customs_hold',
            'event_type' => 'customs_hold',
        ]);
        $this->assertTrue($customsEvent->isExceptionEvent());
    }

    public function test_customer_isolation_and_operator_permissions_via_web(): void
    {
        $shipment1 = Shipment::create([
            'shipment_number' => 'CT-ISO-CUST1',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Solar Panels',
            'weight' => 1500,
            'volume' => 12,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(6),
            'current_status' => 'in_transit',
        ]);

        $shipment2 = Shipment::create([
            'shipment_number' => 'CT-ISO-CUST2',
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Turbines',
            'weight' => 3000,
            'volume' => 20,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(8),
            'current_status' => 'in_transit',
        ]);

        // Customer 1 viewing own shipment: 200 OK
        $response = $this->actingAs($this->customer1)->get(route('shipments.show', $shipment1));
        $response->assertStatus(200);

        // Customer 1 accessing Customer 2's shipment: 403 Forbidden
        $response = $this->actingAs($this->customer1)->get(route('shipments.show', $shipment2));
        $response->assertStatus(403);

        // Customer 1 attempting to add tracking event: 403 Forbidden
        $response = $this->actingAs($this->customer1)->post(route('tracking-events.store', $shipment1), [
            'status' => 'delivered',
            'event_type' => 'delivery_completed',
            'event_time' => now()->format('Y-m-d H:i'),
            'description' => 'Unauthorized customer attempt.',
        ]);
        $response->assertStatus(403);

        // Operator adding tracking event: 302 Redirect (Success)
        $response = $this->actingAs($this->operator)->post(route('tracking-events.store', $shipment1), [
            'status' => 'delivered',
            'event_type' => 'delivery_completed',
            'event_time' => now()->format('Y-m-d H:i'),
            'description' => 'Authorized operator delivery sign-off.',
        ]);
        $response->assertRedirect(route('shipments.show', $shipment1));
        $this->assertEquals('delivered', $shipment1->fresh()->current_status);
    }
}
