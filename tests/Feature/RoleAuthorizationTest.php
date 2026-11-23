<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer1;
    protected User $customer2;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->operator = User::create([
            'name' => 'Operator User',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->customer1 = User::create([
            'name' => 'Customer One',
            'email' => 'c1@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->customer2 = User::create([
            'name' => 'Customer Two',
            'email' => 'c2@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->carrier = Carrier::create([
            'name' => 'Maersk',
            'code' => 'MAEU',
            'status' => 'active',
        ]);

        $this->origin = Location::create([
            'name' => 'Singapore Port',
            'code' => 'SGSIN',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'latitude' => 1.29,
            'longitude' => 103.85,
        ]);

        $this->destination = Location::create([
            'name' => 'Rotterdam Port',
            'code' => 'NLRTM',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.92,
            'longitude' => 4.47,
        ]);
    }

    public function test_admin_can_access_admin_management(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/carriers');
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->get('/admin/audit-logs');
        $response->assertStatus(200);
    }

    public function test_operator_and_customer_forbidden_from_admin_area(): void
    {
        $response = $this->actingAs($this->operator)->get('/admin/carriers');
        $response->assertStatus(403);

        $response = $this->actingAs($this->customer1)->get('/admin/carriers');
        $response->assertStatus(403);

        $response = $this->actingAs($this->customer1)->get('/admin/audit-logs');
        $response->assertStatus(403);
    }

    public function test_operator_can_create_shipments(): void
    {
        $response = $this->actingAs($this->operator)->get('/shipment/create');
        $response->assertStatus(200);
    }

    public function test_customer_cannot_create_shipments(): void
    {
        $response = $this->actingAs($this->customer1)->get('/shipment/create');
        $response->assertStatus(403);
    }

    public function test_customer_can_only_view_own_shipment(): void
    {
        $shipment1 = Shipment::create([
            'shipment_number' => 'CT-TEST-001',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Electronics',
            'weight' => 500,
            'volume' => 10,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
            'current_status' => 'in_transit',
        ]);

        $shipment2 = Shipment::create([
            'shipment_number' => 'CT-TEST-002',
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Industrial Machinery',
            'weight' => 2000,
            'volume' => 30,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(8),
            'current_status' => 'in_transit',
        ]);

        // Customer 1 can view own shipment
        $response = $this->actingAs($this->customer1)->get("/shipments/{$shipment1->id}");
        $response->assertStatus(200);

        // Customer 1 is forbidden from viewing Customer 2's shipment
        $response = $this->actingAs($this->customer1)->get("/shipments/{$shipment2->id}");
        $response->assertStatus(403);

        // Operator and Admin can view any shipment
        $response = $this->actingAs($this->operator)->get("/shipments/{$shipment2->id}");
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->get("/shipments/{$shipment2->id}");
        $response->assertStatus(200);
    }
}
