<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE_DESACTIVATION = "Votre compte a été désactivé. Veuillez contacter l'administrateur.";

    private const MESSAGE_REJET_PATIENT = "Votre compte n'a pas été retenu.";

    private const MESSAGE_EN_ATTENTE = 'Votre compte est en attente de validation.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function personnel(string $role, string $statut, string $email = 'staff@sunu-sante.sn'): User
    {
        $service = Service::create(['nom' => 'Service test', 'actif' => true]);

        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => $statut,
            'service_id' => $service->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function patient(string $statut, string $email = 'patient@sunu-sante.sn'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => $statut,
        ]);
        $user->assignRole('Patient');

        return $user;
    }

    /** A. Messages affichés à la connexion. */
    public function test_connexion_d_un_compte_personnel_desactive_affiche_le_message_de_desactivation(): void
    {
        $user = $this->personnel('Secrétaire de service', 'Rejeté');

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'motdepasse123')
            ->call('login')
            ->assertHasErrors(['email' => self::MESSAGE_DESACTIVATION]);

        $this->assertGuest();
    }

    public function test_connexion_d_un_compte_patient_rejete_garde_le_message_du_patient(): void
    {
        $user = $this->patient('Rejeté');

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'motdepasse123')
            ->call('login')
            ->assertHasErrors(['email' => self::MESSAGE_REJET_PATIENT]);

        $this->assertGuest();
    }

    public function test_connexion_d_un_compte_en_attente_garde_le_message_d_attente(): void
    {
        $user = $this->patient('En attente');

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'motdepasse123')
            ->call('login')
            ->assertHasErrors(['email' => self::MESSAGE_EN_ATTENTE]);

        $this->assertGuest();
    }

    public function test_connexion_d_un_compte_actif_reussit_normalement(): void
    {
        $user = $this->patient('Actif');

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'motdepasse123')
            ->call('login')
            ->assertRedirect(route('patient.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /** B. Accès aux routes protégées (HTTP). */
    public function test_un_compte_personnel_desactive_est_bloque_avec_le_message_adapte(): void
    {
        $user = $this->personnel('Médecin', 'Rejeté');

        $this->actingAs($user)
            ->get('/medecin/tableau-de-bord')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::MESSAGE_DESACTIVATION]);

        $this->assertGuest();
    }

    public function test_un_compte_patient_rejete_est_bloque_avec_le_message_inchange(): void
    {
        $user = $this->patient('Rejeté');

        $this->actingAs($user)
            ->get('/patient/tableau-de-bord')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::MESSAGE_REJET_PATIENT]);

        $this->assertGuest();
    }

    public function test_un_compte_patient_en_attente_est_bloque_avec_le_message_inchange(): void
    {
        $user = $this->patient('En attente');

        $this->actingAs($user)
            ->get('/patient/tableau-de-bord')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::MESSAGE_EN_ATTENTE]);

        $this->assertGuest();
    }
}
