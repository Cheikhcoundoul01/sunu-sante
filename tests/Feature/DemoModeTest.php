<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_demo_mode_disabled_by_default(): void
    {
        Config::set('demo.enabled', false);

        $this->get('/demo/login/patient')->assertStatus(404);
    }

    public function test_demo_mode_enabled_allows_demo_routes(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DemoSeeder::class);

        $this->get('/demo/login/patient')->assertStatus(302); // Redirect after login
    }

    public function test_demo_login_creates_session_and_redirects(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DemoSeeder::class);

        $response = $this->get('/demo/login/patient');

        $response->assertStatus(302);
        $this->assertAuthenticatedAs(User::where('email', 'patient.demo@sunu-sante.sn')->first());
        $response->assertRedirect(route('patient.dashboard'));
    }

    public function test_all_six_roles_have_demo_accounts(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DemoSeeder::class);

        $roles = ['patient', 'secretaire', 'accueil', 'medecin', 'major', 'admin'];

        foreach ($roles as $role) {
            $response = $this->get("/demo/login/{$role}");
            $response->assertStatus(302);
            $this->assertAuthenticated();
        }
    }

    public function test_demo_accounts_have_is_demo_flag(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DemoSeeder::class);

        foreach (config('demo.accounts') as $account) {
            $user = User::where('email', $account['email'])->first();
            $this->assertNotNull($user);
            $this->assertTrue($user->is_demo);
            $this->assertSame($account['role'], $user->getRoleNames()->first());
        }
    }

    public function test_demo_routes_return_404_when_disabled(): void
    {
        Config::set('demo.enabled', false);

        $this->get('/demo/login/patient')->assertStatus(404);
        $this->get('/demo/login/secretaire')->assertStatus(404);
        $this->get('/demo/login/admin')->assertStatus(404);
    }

    public function test_invalid_demo_role_returns_404(): void
    {
        Config::set('demo.enabled', true);

        $this->get('/demo/login/invalid')->assertStatus(404);
    }

    public function test_demo_reset_command_only_deletes_demo_users(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(DemoSeeder::class);

        // Créer un utilisateur réel
        $realUser = User::factory()->create([
            'statut_compte' => 'Actif',
            'is_demo' => false,
        ]);
        $realUser->assignRole('Patient');

        $demoCountBefore = User::where('is_demo', true)->count();
        $realCountBefore = User::where('is_demo', false)->count();

        $this->assertGreaterThan(0, $demoCountBefore);
        $this->assertSame(1, $realCountBefore);

        // Lancer la commande de reset
        Artisan::call('demo:reset');

        $demoCountAfter = User::where('is_demo', true)->count();
        $realCountAfter = User::where('is_demo', false)->count();

        // Les utilisateurs demo sont recréés (même nombre)
        $this->assertSame($demoCountBefore, $demoCountAfter);
        // L'utilisateur réel est intact
        $this->assertSame($realCountBefore, $realCountAfter);
        $this->assertTrue(User::find($realUser->id)->exists());
    }

    public function test_demo_reset_command_fails_when_demo_disabled(): void
    {
        Config::set('demo.enabled', false);

        $exitCode = Artisan::call('demo:reset');
        $this->assertSame(1, $exitCode);
    }

    public function test_demo_banner_appears_in_layouts_when_enabled(): void
    {
        Config::set('demo.enabled', true);
        $this->seed(RolePermissionSeeder::class);

        $this->get('/connexion')
            ->assertStatus(200)
            ->assertSee('Mode démonstration : données fictives');
    }

    public function test_demo_banner_hidden_when_disabled(): void
    {
        Config::set('demo.enabled', false);
        $this->seed(RolePermissionSeeder::class);

        $this->get('/connexion')
            ->assertStatus(200)
            ->assertDontSee('Mode démonstration : données fictives');
    }
}