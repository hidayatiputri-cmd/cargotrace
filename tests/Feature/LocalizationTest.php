<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);
    }

    public function test_locale_switcher_route_switches_session_and_redirects(): void
    {
        // 1. Switch to Indonesian ('id')
        $responseId = $this->get('/locale/id');
        $responseId->assertRedirect();
        $responseId->assertSessionHas('locale', 'id');

        // 2. Switch to English ('en')
        $responseEn = $this->get('/locale/en');
        $responseEn->assertRedirect();
        $responseEn->assertSessionHas('locale', 'en');
    }

    public function test_invalid_locale_returns_404_and_does_not_set_session(): void
    {
        $responseInvalid = $this->get('/locale/fr');
        $responseInvalid->assertNotFound();

        $responseNumeric = $this->get('/locale/123');
        $responseNumeric->assertNotFound();
    }

    public function test_dashboard_renders_indonesian_translations_when_locale_is_id(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'id'])
            ->get('/dashboard');

        $response->assertOk();
        $this->assertEquals('id', App::getLocale());

        // Verify Indonesian localized elements
        $response->assertSee('Selamat Datang, Budi Santoso');
        $response->assertSee('Dasbor');
        $response->assertSee('Pengiriman');
        $response->assertSee('Armada Aktif');
        $response->assertSee('Pengiriman Tertunda');
        $response->assertSee('Tingkat Ketepatan SLA');
        $response->assertSee('Daftarkan Pengiriman Baru');
    }

    public function test_dashboard_renders_english_translations_when_locale_is_en(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'en'])
            ->get('/dashboard');

        $response->assertOk();
        $this->assertEquals('en', App::getLocale());

        // Verify English localized elements
        $response->assertSee('Welcome, Budi Santoso');
        $response->assertSee('Dashboard');
        $response->assertSee('Shipments');
        $response->assertSee('Active Fleet');
        $response->assertSee('Delayed Shipments');
        $response->assertSee('On-Time SLA Rate');
        $response->assertSee('Register New Consignment');
    }

    public function test_shipments_registry_renders_indonesian_translations(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'id'])
            ->get('/shipments');

        $response->assertOk();
        $response->assertSee('Registri Pengiriman Global');
        $response->assertSee('Filter Data');
        $response->assertSee('Hub Keberangkatan');
        $response->assertSee('Hub Tujuan');
        $response->assertSee('Tingkat Keterlambatan');
    }

    public function test_guest_can_switch_locale_and_login_page_renders_cleanly(): void
    {
        // Guest switches to ID
        $switchResponse = $this->get('/locale/id');
        $switchResponse->assertRedirect();
        $switchResponse->assertSessionHas('locale', 'id');

        // Guest visits login page with session
        $loginResponse = $this->withSession(['locale' => 'id'])->get('/login');
        $loginResponse->assertOk();
        $this->assertEquals('id', App::getLocale());
    }

    public function test_api_requests_remain_unaffected_by_web_locale(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/shipments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }
}
