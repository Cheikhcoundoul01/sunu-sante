<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function personnel(string $role, string $email): User
    {
        $service = Service::create(['nom' => 'Service '.$role, 'actif' => true]);

        $user = User::factory()->create([
            'email' => $email,
            'statut_compte' => 'Actif',
            'service_id' => $service->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function patient(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Patient');

        return $user;
    }

    protected function logoutForm(): string
    {
        return '<form method="POST" action="'.route('logout').'"';
    }

    public function test_le_patient_voit_le_bouton_de_deconnexion(): void
    {
        $this->actingAs($this->patient())
            ->get('/patient/tableau-de-bord')
            ->assertStatus(200)
            ->assertSee('Déconnexion')
            ->assertSee($this->logoutForm(), false);
    }

    public function test_le_personnel_sur_son_tableau_de_bord_voit_le_bouton_de_deconnexion(): void
    {
        $this->actingAs($this->personnel('Secrétaire de service', 'secretaire@sunu-sante.sn'))
            ->get('/secretaire/tableau-de-bord')
            ->assertStatus(200)
            ->assertSee('Déconnexion')
            ->assertSee($this->logoutForm(), false);

        $this->actingAs($this->personnel('Médecin', 'medecin@sunu-sante.sn'))
            ->get('/medecin/tableau-de-bord')
            ->assertStatus(200)
            ->assertSee($this->logoutForm(), false);
    }

    public function test_le_personnel_sur_ses_pages_metier_voit_le_bouton_de_deconnexion(): void
    {
        $this->actingAs($this->personnel('Secrétaire de service', 'secretaire@sunu-sante.sn'))
            ->get('/secretaire/patients')
            ->assertStatus(200)
            ->assertSee('Déconnexion')
            ->assertSee($this->logoutForm(), false);

        $this->actingAs($this->personnel("Agent d'accueil", 'accueil@sunu-sante.sn'))
            ->get('/accueil-personnel/file')
            ->assertStatus(200)
            ->assertSee($this->logoutForm(), false);
    }

    public function test_l_administrateur_garde_sa_navigation_sans_doublon_de_deconnexion(): void
    {
        $admin = User::factory()->create(['statut_compte' => 'Actif']);
        $admin->assignRole('Administrateur');

        $response = $this->actingAs($admin)->get('/admin/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee('Gestion des services')
            ->assertSee('Gestion du personnel')
            ->assertSee('Déconnexion');

        // La navigation admin contient déjà deux formulaires (ordinateur + mobile).
        // Le bandeau générique du personnel ne doit pas en ajouter un troisième.
        $this->assertSame(2, substr_count($response->getContent(), 'action="'.route('logout').'"'));
    }

    public function test_la_deconnexion_invalide_la_session_et_redirige_vers_la_connexion(): void
    {
        $patient = $this->patient();

        $this->actingAs($patient)
            ->post('/deconnexion')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_apres_deconnexion_l_accueil_public_est_accessible(): void
    {
        $patient = $this->patient();

        $this->actingAs($patient)->post('/deconnexion');

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Comment ça marche')
            ->assertSee('Exemple');
    }

    public function test_les_redirections_par_role_restent_inchangees(): void
    {
        $patient = $this->patient();
        $secretaire = $this->personnel('Secrétaire de service', 'secretaire@sunu-sante.sn');

        $this->actingAs($patient)->get('/')->assertRedirect(route('patient.dashboard'));
        $this->actingAs($secretaire)->get('/')->assertRedirect(route('secretaire.dashboard'));
    }
}
