<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentFilterAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer1;
    protected User $customer2;
    protected Carrier $carrierA;
    protected Carrier $carrierB;
    protected Location $originJakarta;
    protected Location $originSingapore;
    protected Location $destRotterdam;
    protected Location $destLosAngeles;

    protected Shipment $shipment1;
    protected Shipment $shipment2;
    protected Shipment $shipment3;

    protected function setUp(): void
    {
        parent::setUp();

        // Users
        $this->admin = User::create([
            'name' => 'Admin Controller',
            'email' => 'admin@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->operator = User::create([
            'name' => 'Operator Lead',
            'email' => 'operator@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->customer1 = User::create([
            'name' => 'Alpha Global Trading',
            'email' => 'alpha@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->customer2 = User::create([
            'name' => 'Beta Industries Ltd',
            'email' => 'beta@ct.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        // Carriers
        $this->carrierA = Carrier::create(['name' => 'Maersk Line', 'code' => 'MAEU', 'status' => 'active']);
        $this->carrierB = Carrier::create(['name' => 'Hapag-Lloyd', 'code' => 'HLCU', 'status' => 'active']);

        // Locations
        $this->originJakarta = Location::create([
            'name' => 'Tanjung Priok',
            'code' => 'IDTPP',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'latitude' => -6.10,
            'longitude' => 106.88,
        ]);

        $this->originSingapore = Location::create([
            'name' => 'Port of Singapore',
            'code' => 'SGSIN',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'latitude' => 1.29,
            'longitude' => 103.85,
        ]);

        $this->destRotterdam = Location::create([
            'name' => 'Port of Rotterdam',
            'code' => 'NLRTM',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.92,
            'longitude' => 4.47,
        ]);

        $this->destLosAngeles = Location::create([
            'name' => 'Port of Los Angeles',
            'code' => 'USLAX',
            'city' => 'Los Angeles',
            'country' => 'United States',
            'latitude' => 33.74,
            'longitude' => -118.26,
        ]);

        // Seed 3 distinct test shipments
        // Shipment 1: Alpha Customer, Maersk, Jakarta -> Rotterdam, In Transit, Delayed HIGH, With Exception
        $this->shipment1 = Shipment::create([
            'shipment_number' => 'CT-FLT-ALPHA-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrierA->id,
            'origin_location_id' => $this->originJakarta->id,
            'destination_location_id' => $this->destRotterdam->id,
            'cargo_type' => 'Photovoltaic Solar Modules',
            'weight' => 15000,
            'volume' => 45,
            'expected_departure' => Carbon::parse('2026-06-01 08:00:00'),
            'expected_arrival' => Carbon::parse('2026-06-20 18:00:00'),
            'estimated_arrival' => Carbon::parse('2026-06-22 18:00:00'), // +48h delay
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'is_delayed' => true,
            'delay_hours' => 48,
            'delay_severity' => 'HIGH',
        ]);

        ShipmentException::create([
            'shipment_id' => $this->shipment1->id,
            'type' => ShipmentException::TYPE_WEATHER_DELAY,
            'severity' => ShipmentException::SEVERITY_HIGH,
            'status' => ShipmentException::STATUS_OPEN,
            'description' => 'Typhoon alert near Malacca Strait causing 48h rerouting.',
            'detected_at' => Carbon::parse('2026-06-05 10:00:00'),
            'reported_by' => $this->operator->id,
        ]);

        // Shipment 2: Alpha Customer, Hapag-Lloyd, Singapore -> Los Angeles, Delivered, On-time (NONE)
        $this->shipment2 = Shipment::create([
            'shipment_number' => 'CT-FLT-ALPHA-02',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrierB->id,
            'origin_location_id' => $this->originSingapore->id,
            'destination_location_id' => $this->destLosAngeles->id,
            'cargo_type' => 'Medical Diagnostic Equipment',
            'weight' => 5000,
            'volume' => 12,
            'expected_departure' => Carbon::parse('2026-07-01 10:00:00'),
            'expected_arrival' => Carbon::parse('2026-07-15 14:00:00'),
            'estimated_arrival' => Carbon::parse('2026-07-15 12:00:00'),
            'current_status' => Shipment::STATUS_DELIVERED,
            'is_delayed' => false,
            'delay_hours' => 0,
            'delay_severity' => 'NONE',
        ]);

        // Shipment 3: Beta Customer, Maersk, Jakarta -> Los Angeles, Picked Up, Delayed LOW (15h)
        $this->shipment3 = Shipment::create([
            'shipment_number' => 'CT-FLT-BETA-01',
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrierA->id,
            'origin_location_id' => $this->originJakarta->id,
            'destination_location_id' => $this->destLosAngeles->id,
            'cargo_type' => 'Automotive Spare Parts',
            'weight' => 8000,
            'volume' => 20,
            'expected_departure' => Carbon::parse('2026-08-01 06:00:00'),
            'expected_arrival' => Carbon::parse('2026-08-18 20:00:00'),
            'estimated_arrival' => Carbon::parse('2026-08-19 11:00:00'), // +15h delay
            'current_status' => Shipment::STATUS_PICKED_UP,
            'is_delayed' => true,
            'delay_hours' => 15,
            'delay_severity' => 'LOW',
        ]);
    }

    public function test_server_side_search_filters_correctly(): void
    {
        // Search by shipment number
        $response = $this->actingAs($this->admin)->get('/shipments?search=ALPHA-01');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-BETA-01');

        // Search by cargo type
        $response = $this->actingAs($this->admin)->get('/shipments?search=Diagnostic');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-ALPHA-01');

        // Search by customer name
        $response = $this->actingAs($this->admin)->get('/shipments?search=Beta+Industries');
        $response->assertOk();
        $response->assertSee('CT-FLT-BETA-01');
        $response->assertDontSee('CT-FLT-ALPHA-01');
    }

    public function test_filter_by_status_and_carrier(): void
    {
        // Status: in_transit
        $response = $this->actingAs($this->operator)->get('/shipments?status=in_transit');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-BETA-01');

        // Status: delivered
        $response = $this->actingAs($this->operator)->get('/shipments?status=delivered');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-ALPHA-01');

        // Carrier: Hapag-Lloyd
        $response = $this->actingAs($this->operator)->get('/shipments?carrier_id=' . $this->carrierB->id);
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-BETA-01');
    }

    public function test_filter_by_origin_and_destination_hubs(): void
    {
        // Origin: Jakarta
        $response = $this->actingAs($this->admin)->get('/shipments?origin_location_id=' . $this->originJakarta->id);
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertSee('CT-FLT-BETA-01');
        $response->assertDontSee('CT-FLT-ALPHA-02');

        // Destination: Rotterdam
        $response = $this->actingAs($this->admin)->get('/shipments?destination_location_id=' . $this->destRotterdam->id);
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-BETA-01');
    }

    public function test_filter_by_delay_status_and_severity(): void
    {
        // Delayed only
        $response = $this->actingAs($this->admin)->get('/shipments?is_delayed=1');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertSee('CT-FLT-BETA-01');
        $response->assertDontSee('CT-FLT-ALPHA-02');

        // On-time only
        $response = $this->actingAs($this->admin)->get('/shipments?is_delayed=0');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-ALPHA-01');

        // Severity: HIGH
        $response = $this->actingAs($this->admin)->get('/shipments?delay_severity=HIGH');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-BETA-01');

        // Severity: LOW
        $response = $this->actingAs($this->admin)->get('/shipments?delay_severity=LOW');
        $response->assertOk();
        $response->assertSee('CT-FLT-BETA-01');
        $response->assertDontSee('CT-FLT-ALPHA-01');
    }

    public function test_filter_by_open_exceptions(): void
    {
        // Has active exceptions
        $response = $this->actingAs($this->admin)->get('/shipments?has_exceptions=1');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-BETA-01');

        // Exception-free
        $response = $this->actingAs($this->admin)->get('/shipments?has_exceptions=0');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-02');
        $response->assertSee('CT-FLT-BETA-01');
        $response->assertDontSee('CT-FLT-ALPHA-01');
    }

    public function test_filter_by_date_ranges(): void
    {
        // Expected departure range (June 2026)
        $response = $this->actingAs($this->admin)->get('/shipments?departure_from=2026-06-01&departure_to=2026-06-30');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-BETA-01');

        // Expected arrival range (July 2026)
        $response = $this->actingAs($this->admin)->get('/shipments?arrival_from=2026-07-01&arrival_to=2026-07-31');
        $response->assertOk();
        $response->assertSee('CT-FLT-ALPHA-02');
        $response->assertDontSee('CT-FLT-ALPHA-01');
        $response->assertDontSee('CT-FLT-BETA-01');
    }

    public function test_customer_role_is_strictly_isolated_even_with_manipulated_filters(): void
    {
        // Customer 1 attempts to search across all or pass customer_id of customer 2
        $response = $this->actingAs($this->customer1)->get('/shipments?customer_id=' . $this->customer2->id);
        $response->assertOk();
        // Must see own shipments only
        $response->assertSee('CT-FLT-ALPHA-01');
        $response->assertSee('CT-FLT-ALPHA-02');
        // Must NEVER see Beta Customer's shipment
        $response->assertDontSee('CT-FLT-BETA-01');

        // Customer 2 logs in
        $responseCust2 = $this->actingAs($this->customer2)->get('/shipments');
        $responseCust2->assertOk();
        $responseCust2->assertSee('CT-FLT-BETA-01');
        $responseCust2->assertDontSee('CT-FLT-ALPHA-01');
        $responseCust2->assertDontSee('CT-FLT-ALPHA-02');
    }

    public function test_api_shipments_endpoint_supports_same_filters_consistently(): void
    {
        $adminToken = $this->admin->createToken('admin-test')->plainTextToken;

        // API Status filter + Delay Severity
        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/shipments?status=in_transit&delay_severity=HIGH');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'shipment_number', 'current_status', 'is_delayed', 'delay_severity']
            ],
            'meta' => ['current_page', 'total', 'per_page']
        ]);
        $response->assertJsonFragment(['shipment_number' => 'CT-FLT-ALPHA-01']);
        $response->assertJsonMissing(['shipment_number' => 'CT-FLT-ALPHA-02']);
        $response->assertJsonMissing(['shipment_number' => 'CT-FLT-BETA-01']);

        // API Customer isolation check
        $this->app['auth']->forgetGuards();
        $custToken = $this->customer1->createToken('cust-test')->plainTextToken;

        $custResponse = $this->withHeader('Authorization', 'Bearer ' . $custToken)
            ->getJson('/api/shipments?search=BETA');

        $custResponse->assertOk();
        $this->assertEquals(0, $custResponse->json('meta.total'));
        $custResponse->assertJsonMissing(['shipment_number' => 'CT-FLT-BETA-01']);
    }
}
