<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSanctumTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@ct.test', 'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN]);
        $this->operator = User::create(['name' => 'Operator', 'email' => 'op@ct.test', 'password' => bcrypt('password'), 'role' => User::ROLE_OPERATOR]);
        $this->customer = User::create(['name' => 'Customer', 'email' => 'cust@ct.test', 'password' => bcrypt('password'), 'role' => User::ROLE_CUSTOMER]);

        $this->carrier = Carrier::create(['name' => 'Maersk', 'code' => 'MAEU', 'status' => 'active']);
        $this->origin = Location::create(['name' => 'Singapore', 'code' => 'SGSIN', 'city' => 'Singapore', 'country' => 'Singapore']);
        $this->destination = Location::create(['name' => 'Rotterdam', 'code' => 'NLRTM', 'city' => 'Rotterdam', 'country' => 'Netherlands']);
    }

    public function test_api_login_returns_sanctum_bearer_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'op@ct.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_api_shipments_list_accessible_with_token(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-API-01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Electronics',
            'weight' => 450,
            'volume' => 3,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(7),
            'current_status' => 'in_transit',
        ]);

        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/shipments');

        $response->assertStatus(200)
            ->assertJsonFragment(['shipment_number' => 'CT-API-01']);
    }

    public function test_operator_can_add_tracking_event_via_api(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-API-EVENT',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Sensors',
            'weight' => 200,
            'volume' => 2,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
            'current_status' => 'picked_up',
        ]);

        $token = $this->operator->createToken('op-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/shipments/{$shipment->id}/tracking-events", [
                'status' => 'in_transit',
                'event_type' => 'departure',
                'description' => 'Container loaded and vessel departed.',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('in_transit', $shipment->fresh()->current_status);
    }
}
