<?php

namespace Tests\Feature\Admin;

use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function service(string $nom, bool $actif = true): Service
    {
        return Service::create(['nom' => $nom, 'actif' => $actif]);
    }

    protected function admin(string $name = 'Ali Ba'): User
    {
        $user = User::factory()->create(['name' => $name, 'statut_compte' => 'Actif']);
        $user->assignRole('Administrateur');

        return $user;
    }

    protected function staff(string $prenom, string $role, string $statut, ?int $serviceId = null): User
    {
        $user = User::factory()->create([
            'name' => $prenom,
            'statut_compte' => $statut,
            'service_id' => $serviceId,
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function patient(string $statut = 'Actif'): User
    {
        $user = User::factory()->create(['statut_compte' => $statut]);
        $user->assignRole('Patient');

        return $user;
    }

    protected function rdv(int $patientId, int $medecinId, int $serviceId, string $statut, string $date): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $patientId,
            'medecin_id' => $medecinId,
            'service_id' => $serviceId,
            'date' => $date,
            'heure_debut' => '09:00',
            'duree_prevue' => 30,
            'statut' => $statut,
        ]);
    }

    protected function valeurStat(string $html, string $libelle): string
    {
        $pattern = '/'.preg_quote(e($libelle), '/').'<\/span>\s*<span[^<>]*>(\d+)<\/span>/u';
        preg_match($pattern, $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_le_tableau_de_bord_admin_affiche_les_cartes_et_les_chiffres(): void
    {
        $response = $this->actingAs($this->admin())
            ->get('/admin/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee('Espace administrateur')
            ->assertSee('Accès rapides')
            ->assertSee('Chiffres clés')
            ->assertSee('Administration')
            ->assertSee('Statistiques globales')
            ->assertSee('Gestion des services')
            ->assertSee('Gestion du personnel')
            ->assertSee('Services')
            ->assertSee('Personnel actif')
            ->assertSee('Patients inscrits')
            ->assertSee('Rendez-vous du jour')
            ->assertSee(route('admin.statistiques'), false)
            ->assertSee(route('admin.services'), false)
            ->assertSee(route('admin.personnel'), false);
    }

    public function test_le_suffixe_de_demonstration_est_masque_et_l_avatar_utilise_les_initiales(): void
    {
        $this->travelTo(now()->setTime(10, 0));

        $response = $this->actingAs($this->admin('Ali Ba (Admin Démo)'))
            ->get('/admin/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee('AB')
            ->assertSee('Bonjour, Ali.');
    }

    public function test_la_salutation_depend_de_l_heure_du_serveur(): void
    {
        $admin = $this->admin();

        $this->travelTo(now()->setTime(17, 59));
        $this->actingAs($admin)->get('/admin/tableau-de-bord')->assertSee('Bonjour, Ali.');

        $this->travelTo(now()->setTime(18, 0));
        $this->actingAs($admin)->get('/admin/tableau-de-bord')->assertSee('Bonsoir, Ali.');
    }

    public function test_les_chiffres_cles_couvrent_tout_l_etablissement(): void
    {
        $service = $this->service('Cardiologie');
        $this->service('Pédiatrie');
        $this->service('Radiologie', false); // inactif : compté dans « Services ».

        $admin = $this->admin();
        $medecin = $this->staff('Moussa', 'Médecin', 'Actif', $service->id);
        $this->staff('Fatou', 'Secrétaire de service', 'Actif', $service->id);
        $this->staff('Omar', "Agent d'accueil", 'En attente', $service->id); // non actif : exclu.

        $patient = $this->patient('Actif');
        $this->patient('En attente');
        $this->patient('Rejeté');

        $aujourdHui = now()->toDateString();
        $demain = now()->addDay()->toDateString();

        // Rendez-vous du jour : 1 demandé + 1 validé (actifs) + 1 annulé (exclu).
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_DEMANDE, $aujourdHui);
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui);
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_ANNULE, $aujourdHui);
        // Demain : exclu.
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_DEMANDE, $demain);

        $html = $this->actingAs($admin)
            ->get('/admin/tableau-de-bord')
            ->assertStatus(200)
            ->getContent();

        // 3 services (2 actifs + 1 inactif).
        $this->assertSame('3', $this->valeurStat($html, 'Services'));
        // Actif : admin + médecin + secrétaire (l'agent en attente est exclu).
        $this->assertSame('3', $this->valeurStat($html, 'Personnel actif'));
        // Tous les comptes patient, quel que soit leur statut.
        $this->assertSame('3', $this->valeurStat($html, 'Patients inscrits'));
        // Demandé + Validé du jour, hors Annulé et hors demain.
        $this->assertSame('2', $this->valeurStat($html, 'Rendez-vous du jour'));
    }

    public function test_un_non_admin_ne_peut_pas_acceder_au_tableau_de_bord(): void
    {
        $this->actingAs($this->patient())
            ->get('/admin/tableau-de-bord')
            ->assertStatus(403);
    }
}
