<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterPatient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/connexion')->assertStatus(200);
    }

    public function test_guest_can_view_registration_page(): void
    {
        $this->get('/inscription')->assertStatus(200);
    }

    public function test_guest_can_view_demande_envoyee_page(): void
    {
        $this->get('/demande-envoyee')
            ->assertStatus(200)
            ->assertSee('Demande envoyée');
    }

    public function test_guest_can_view_public_home(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('Exemple');
    }

    public function test_patient_registration_creates_pending_account_with_patient_role(): void
    {
        Livewire::test(RegisterPatient::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa@example.com')
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('register')
            ->assertRedirect(route('patient.demande-envoyee'));

        $user = User::where('email', 'awa@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('En attente', $user->statut_compte);
        $this->assertTrue($user->hasRole('Patient'));
    }

    public function test_patient_login_redirects_to_patient_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.com',
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => 'Actif',
        ]);
        $user->assignRole('Patient');

        Livewire::test(Login::class)
            ->set('email', 'patient@example.com')
            ->set('password', 'motdepasse123')
            ->call('login')
            ->assertRedirect(route('patient.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_credentials_shows_generic_error(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.com',
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => 'Actif',
        ]);
        $user->assignRole('Patient');

        Livewire::test(Login::class)
            ->set('email', 'patient@example.com')
            ->set('password', 'mauvais')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_pending_account_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'attente@example.com',
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => 'En attente',
        ]);
        $user->assignRole('Patient');

        Livewire::test(Login::class)
            ->set('email', 'attente@example.com')
            ->set('password', 'motdepasse123')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_patient_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Patient');

        $this->actingAs($user)->get('/admin/tableau-de-bord')->assertStatus(403);
    }

    public function test_administrator_can_access_admin_area(): void
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Administrateur');

        $this->actingAs($user)->get('/admin/tableau-de-bord')->assertStatus(200);
    }

    public function test_login_rate_limiting_after_five_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.com',
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => 'Actif',
        ]);
        $user->assignRole('Patient');

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'patient@example.com')
                ->set('password', 'mauvais')
                ->call('login');
        }

        Livewire::test(Login::class)
            ->set('email', 'patient@example.com')
            ->set('password', 'mauvais')
            ->call('login')
            ->assertHasErrors(['email']);
    }
}
