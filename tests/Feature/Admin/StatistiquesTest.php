<?php

namespace Tests\Feature\Admin;

use App\Models\Disponibilite;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use App\Services\FileAttenteService;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatistiquesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function service(string $nom): Service
    {
        return Service::create(['nom' => $nom, 'actif' => true]);
    }

    protected function admin(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Administrateur');
        return $user;
    }

    protected function medecin(int $serviceId): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $serviceId]);
        $user->assignRole('Médecin');
        return $user;
    }

    protected function patient(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Patient');
        return $user;
    }

    protected function disponibilite(int $medecinId, int $serviceId, string $jour, string $debut, string $fin): Disponibilite
    {
        return Disponibilite::create([
            'medecin_id' => $medecinId,
            'service_id' => $serviceId,
            'jour' => $jour,
            'heure_debut' => $debut,
            'heure_fin' => $fin,
            'active' => true,
        ]);
    }

    public function test_admin_peut_acceder_statistiques(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/statistiques')
            ->assertStatus(200)
            ->assertSee('Statistiques globales');
    }

    public function test_non_admin_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get('/admin/statistiques')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/admin/statistiques')->assertStatus(403);

        $secretaire = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $secretaire->assignRole('Secrétaire de service');
        $this->actingAs($secretaire)->get('/admin/statistiques')->assertStatus(403);
    }

    public function test_statistiques_globales_calculent_correctement(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        // Service A: 2 valides, 1 demande
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        // Service B: 1 annulé
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertSame(4, $stats['global']['rdv']['total']);
        $this->assertSame(2, $stats['global']['rdv']['valides']);
        $this->assertSame(1, $stats['global']['rdv']['demandes']);
        $this->assertSame(1, $stats['global']['rdv']['annules']);
        $this->assertSame(0, $stats['global']['rdv']['interventions']);

        // Taux validation = valides / (valides + annules) = 2/3 = 66.7%
        $this->assertSame(66.7, $stats['global']['rdv']['taux_validation_pct']);
    }

    public function test_top_services_par_volume(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        // Service A: 3 RDV
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        // Service B: 1 RDV
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertCount(2, $stats['top_services']);
        $this->assertSame('Cardiologie', $stats['top_services'][0]['nom']);
        $this->assertSame(3, $stats['top_services'][0]['rdv']);
        $this->assertSame('Pédiatrie', $stats['top_services'][1]['nom']);
        $this->assertSame(1, $stats['top_services'][1]['rdv']);
    }

    public function test_detail_par_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertArrayHasKey('Cardiologie', $stats['par_service']);
        $this->assertArrayHasKey('Pédiatrie', $stats['par_service']);
        $this->assertSame(1, $stats['par_service']['Cardiologie']['rdv']['total']);
        $this->assertSame(1, $stats['par_service']['Cardiologie']['rdv']['valides']);
        $this->assertSame(0, $stats['par_service']['Cardiologie']['rdv']['annules']);
        $this->assertSame(1, $stats['par_service']['Pédiatrie']['rdv']['total']);
        $this->assertSame(0, $stats['par_service']['Pédiatrie']['rdv']['valides']);
        $this->assertSame(1, $stats['par_service']['Pédiatrie']['rdv']['annules']);
    }
}