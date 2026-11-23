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

class FullRestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer1;
    protected User $customer2;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;
    protected Shipment $shipment1;
    protected Shipment $shipment2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@api-test.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->operator = User::create([
            'name' => 'Logistics Operator',
            'email' => 'operator@api-test.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->customer1 = User::create([
            'name' => 'Customer One Corp',
            'email' => 'c1@api-test.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->customer2 = User::create([
            'name' => 'Customer Two Ltd',
            'email' => 'c2@api-test.test',
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
            'latitude' => 1.29027,
            'longitude' => 103.85195,
        ]);

        $this->destination = Location::create([
            'name' => 'Port of Rotterdam',
            'code' => 'NLRTM',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.92442,
            'longitude' => 4.47773,
        ]);

        $this->shipment1 = Shipment::create([
            'shipment_number' => 'CT-API-REST-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Medical Equipment',
            'weight' => 1200,
            'volume' => 10,
            'expected_departure' => Carbon::now()->subDays(2),
            'expected_arrival' => Carbon::now()->addDays(5),
            'current_status' => 'in_transit',
        ]);

        $this->shipment2 = Shipment::create([
            'shipment_number' => 'CT-API-REST-02',
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Automotive Parts',
            'weight' => 5000,
            'volume' => 30,
            'expected_departure' => Carbon::now()->subDays(1),
            'expected_arrival' => Carbon::now()->addDays(7),
            'current_status' => 'booked',
        ]);
    }

    protected function authHeader(User $user): array
    {
        $this->app['auth']->forgetGuards();
        $token = $user->createToken('test-token')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Authentication Endpoints
    |--------------------------------------------------------------------------
    */

    public function test_api_register_creates_customer_and_issues_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Customer PT',
            'email' => 'new.customer@test.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'role' => 'customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'new.customer@test.com']);
    }

    public function test_api_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_api_login_authenticates_and_returns_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'operator@api-test.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_api_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'operator@api-test.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_api_user_and_logout_protected_by_sanctum(): void
    {
        // Unauthenticated access
        $this->getJson('/api/user')->assertStatus(401);
        $this->postJson('/api/logout')->assertStatus(401);

        // Authenticated access
        $headers = $this->authHeader($this->operator);

        $userResponse = $this->withHeaders($headers)->getJson('/api/user');
        $userResponse->assertStatus(200)
            ->assertJsonFragment(['email' => 'operator@api-test.test']);

        // Logout
        $logoutResponse = $this->withHeaders($headers)->postJson('/api/logout');
        $logoutResponse->assertStatus(200);

        // Token revoked
        $this->app['auth']->forgetGuards();
        $this->withHeaders($headers)->getJson('/api/user')->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Shipments Endpoints
    |--------------------------------------------------------------------------
    */

    public function test_api_shipments_list_enforces_customer_isolation_and_supports_filtering(): void
    {
        // Customer 1 only sees shipment 1, never shipment 2
        $c1Response = $this->withHeaders($this->authHeader($this->customer1))
            ->getJson('/api/shipments');

        $c1Response->assertStatus(200);
        $c1Response->assertJsonFragment(['shipment_number' => 'CT-API-REST-01']);
        $c1Response->assertJsonMissing(['shipment_number' => 'CT-API-REST-02']);

        // Operator sees both shipments with pagination
        $opResponse = $this->withHeaders($this->authHeader($this->operator))
            ->getJson('/api/shipments?per_page=10&status=in_transit');

        $opResponse->assertStatus(200)
            ->assertJsonFragment(['shipment_number' => 'CT-API-REST-01'])
            ->assertJsonMissing(['shipment_number' => 'CT-API-REST-02']);
    }

    public function test_api_operator_can_create_shipment_and_customer_forbidden(): void
    {
        $payload = [
            'shipment_number' => 'CT-API-NEW-01',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Lithium Batteries',
            'weight' => 3000,
            'volume' => 15,
            'expected_departure' => now()->addDay()->format('Y-m-d H:i:s'),
            'expected_arrival' => now()->addDays(8)->format('Y-m-d H:i:s'),
            'current_status' => 'created',
            'items' => [
                ['item_name' => 'Pack A', 'quantity' => 10, 'weight' => 1500, 'description' => 'UN3480'],
            ],
        ];

        // Customer attempt -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->customer1))
            ->postJson('/api/shipments', $payload)
            ->assertStatus(403);

        // Operator attempt -> 201 Created
        $response = $this->withHeaders($this->authHeader($this->operator))
            ->postJson('/api/shipments', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['shipment_number' => 'CT-API-NEW-01']);

        $this->assertDatabaseHas('shipments', ['shipment_number' => 'CT-API-NEW-01']);
        $this->assertDatabaseHas('shipment_items', ['item_name' => 'Pack A']);
    }

    public function test_api_shipment_detail_authorization_and_not_found(): void
    {
        // Customer 1 viewing own shipment -> 200 OK
        $this->withHeaders($this->authHeader($this->customer1))
            ->getJson("/api/shipments/{$this->shipment1->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['shipment_number' => 'CT-API-REST-01']);

        // Customer 1 viewing Customer 2's shipment -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->customer1))
            ->getJson("/api/shipments/{$this->shipment2->id}")
            ->assertStatus(403);

        // Non-existent ID -> 404 Not Found without stack trace
        $notFoundResponse = $this->withHeaders($this->authHeader($this->customer1))
            ->getJson("/api/shipments/999999");

        $notFoundResponse->assertStatus(404)
            ->assertJsonFragment(['message' => 'Resource not found.']);
    }

    public function test_api_update_and_delete_shipment(): void
    {
        // Operator updates shipment -> 200 OK
        $updateResponse = $this->withHeaders($this->authHeader($this->operator))
            ->putJson("/api/shipments/{$this->shipment1->id}", [
                'current_status' => 'delivered',
                'cargo_type' => 'Medical Equipment (Completed)',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonFragment(['current_status' => 'delivered']);

        // Customer cannot delete -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->customer1))
            ->deleteJson("/api/shipments/{$this->shipment1->id}")
            ->assertStatus(403);

        // Operator cannot delete -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->operator))
            ->deleteJson("/api/shipments/{$this->shipment1->id}")
            ->assertStatus(403);

        // Admin deletes shipment -> 200 OK
        $this->withHeaders($this->authHeader($this->admin))
            ->deleteJson("/api/shipments/{$this->shipment1->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('shipments', ['id' => $this->shipment1->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Historical Tracking Endpoints
    |--------------------------------------------------------------------------
    */

    public function test_api_tracking_events_list_and_create(): void
    {
        // Operator adds tracking checkpoint -> 201 Created
        $postResponse = $this->withHeaders($this->authHeader($this->operator))
            ->postJson("/api/shipments/{$this->shipment1->id}/tracking", [
                'status' => 'in_transit',
                'event_type' => 'waypoint_telemetry',
                'latitude' => 5.923,
                'longitude' => 80.512,
                'description' => 'Passing maritime checkpoint Alpha.',
            ]);

        $postResponse->assertStatus(201)
            ->assertJsonFragment(['status' => 'in_transit'])
            ->assertJsonFragment(['description' => 'Passing maritime checkpoint Alpha.']);

        // Customer gets tracking timeline -> 200 OK
        $timelineResponse = $this->withHeaders($this->authHeader($this->customer1))
            ->getJson("/api/shipments/{$this->shipment1->id}/tracking");

        $timelineResponse->assertStatus(200)
            ->assertJsonFragment(['description' => 'Passing maritime checkpoint Alpha.']);

        // Customer cannot add tracking event -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->customer1))
            ->postJson("/api/shipments/{$this->shipment1->id}/tracking", [
                'status' => 'delivered',
                'event_type' => 'unauthorized_attempt',
                'description' => 'Customer trying to mark delivered.',
            ])->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Exceptions Management Endpoints
    |--------------------------------------------------------------------------
    */

    public function test_api_exceptions_lifecycle_reporting_and_patching(): void
    {
        // 1. Operator logs exception -> 201 Created
        $postResponse = $this->withHeaders($this->authHeader($this->operator))
            ->postJson("/api/shipments/{$this->shipment1->id}/exceptions", [
                'type' => 'WEATHER_DELAY',
                'severity' => 'HIGH',
                'description' => 'Severe swell in open corridor.',
                'estimated_delay_hours' => 24,
            ]);

        $postResponse->assertStatus(201)
            ->assertJsonFragment(['type' => 'WEATHER_DELAY'])
            ->assertJsonFragment(['status' => 'OPEN']);

        $exceptionId = $postResponse->json('data.id');

        // Customer views exceptions list -> 200 OK
        $this->withHeaders($this->authHeader($this->customer1))
            ->getJson("/api/shipments/{$this->shipment1->id}/exceptions")
            ->assertStatus(200)
            ->assertJsonFragment(['type' => 'WEATHER_DELAY']);

        // 2. Operator updates exception via PATCH /api/exceptions/{id} to INVESTIGATING
        $patchResponse = $this->withHeaders($this->authHeader($this->operator))
            ->patchJson("/api/exceptions/{$exceptionId}", [
                'status' => 'INVESTIGATING',
                'investigation_notes' => 'Port authority contacted regarding safe anchorage.',
            ]);

        $patchResponse->assertStatus(200)
            ->assertJsonFragment(['status' => 'INVESTIGATING']);

        // 3. Operator resolves exception via PATCH /api/exceptions/{id} to RESOLVED
        $resolveResponse = $this->withHeaders($this->authHeader($this->operator))
            ->patchJson("/api/exceptions/{$exceptionId}", [
                'status' => 'RESOLVED',
                'resolution_notes' => 'Storm cleared, vessel continuing voyage.',
            ]);

        $resolveResponse->assertStatus(200)
            ->assertJsonFragment(['status' => 'RESOLVED']);

        // Customer cannot patch exception -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->customer1))
            ->patchJson("/api/exceptions/{$exceptionId}", [
                'status' => 'RESOLVED',
            ])->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Master Resources Endpoints (Carriers & Locations)
    |--------------------------------------------------------------------------
    */

    public function test_api_master_carriers_and_locations(): void
    {
        // Listing carriers (All authenticated users) -> 200 OK
        $this->withHeaders($this->authHeader($this->customer1))
            ->getJson('/api/carriers')
            ->assertStatus(200)
            ->assertJsonFragment(['code' => 'MAEU']);

        // Customer creating carrier -> 403 Forbidden
        $this->withHeaders($this->authHeader($this->customer1))
            ->postJson('/api/carriers', [
                'name' => 'Hapag-Lloyd',
                'code' => 'HPLU',
                'status' => 'active',
            ])->assertStatus(403);

        // Admin creating carrier -> 201 Created
        $this->withHeaders($this->authHeader($this->admin))
            ->postJson('/api/carriers', [
                'name' => 'Hapag-Lloyd',
                'code' => 'HPLU',
                'status' => 'active',
            ])->assertStatus(201)
            ->assertJsonFragment(['code' => 'HPLU']);

        // Listing locations (All authenticated users) -> 200 OK
        $this->withHeaders($this->authHeader($this->customer1))
            ->getJson('/api/locations')
            ->assertStatus(200)
            ->assertJsonFragment(['code' => 'SGSIN']);

        // Admin creating location -> 201 Created
        $this->withHeaders($this->authHeader($this->admin))
            ->postJson('/api/locations', [
                'name' => 'Port of Hamburg',
                'code' => 'DEHAM',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'latitude' => 53.5353,
                'longitude' => 9.9872,
            ])->assertStatus(201)
            ->assertJsonFragment(['code' => 'DEHAM']);
    }
}
