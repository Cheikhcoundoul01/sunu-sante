<?php

namespace Tests\Feature\Major;

use App\Models\Disponibilite;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
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

    protected function major(int $serviceId): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $serviceId]);
        $user->assignRole('Major de service');
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

    public function test_major_peut_acceder_statistiques(): void
    {
        $service = $this->service('Cardiologie');
        $this->medecin($service->id);

        $this->actingAs($this->major($service->id))
            ->get('/major/statistiques')
            ->assertStatus(200)
            ->assertSee('Statistiques du service');
    }

    public function test_non_major_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get('/major/statistiques')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/major/statistiques')->assertStatus(403);

        $secretaire = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $secretaire->assignRole('Secrétaire de service');
        $this->actingAs($secretaire)->get('/major/statistiques')->assertStatus(403);
    }

    public function test_major_voit_uniquement_stats_de_son_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $majorA = $this->major($serviceA->id);
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        $rdvA = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $rdvB = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $component = Livewire::actingAs($majorA)
            ->test(\App\Livewire\Major\Statistiques::class)
            ->set('date', $jour);

        $html = $component->html();
        $this->assertStringContainsString($serviceA->nom, $html);
        $this->assertStringContainsString('Total RDV', $html);
        $this->assertStringContainsString('1', $html);
        $this->assertStringNotContainsString($serviceB->nom, $html);
    }

    public function test_statistiques_rdv_calculent_correctement(): void
    {
        $service = $this->service('Cardiologie');
        $major = $this->major($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        $stats = app(StatisticsService::class)->getServiceStatistics($service->id, Carbon::parse($jour));

        $this->assertSame(3, $stats['rdv']['total']);
        $this->assertSame(1, $stats['rdv']['valides']);
        $this->assertSame(1, $stats['rdv']['demandes']);
        $this->assertSame(1, $stats['rdv']['annules']);
        $this->assertSame(0, $stats['rdv']['interventions']);
        $this->assertSame(1, $stats['rdv']['consultations']);
    }

    public function test_statistiques_file_calculent_correctement(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdv1 = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $rdv2 = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(\App\Services\FileAttenteService::class)->genererFileDuJour($service->id);
        $entree1 = FileEntree::where('rendez_vous_id', $rdv1->id)->first();
        $entree2 = FileEntree::where('rendez_vous_id', $rdv2->id)->first();

        app(\App\Services\FileAttenteService::class)->changerStatut($entree1->id, FileEntree::STATUT_EN_COURS);
        app(\App\Services\FileAttenteService::class)->changerStatut($entree2->id, FileEntree::STATUT_EN_COURS);
        app(\App\Services\FileAttenteService::class)->changerStatut($entree2->id, FileEntree::STATUT_TERMINE);

        $stats = app(StatisticsService::class)->getServiceStatistics($service->id, Carbon::parse($jour));

        $this->assertSame(2, $stats['file']['total']);
        $this->assertSame(0, $stats['file']['en_attente']);
        $this->assertSame(1, $stats['file']['en_cours']);
        $this->assertSame(1, $stats['file']['termine']);
        $this->assertSame(0, $stats['file']['absent']);
    }
}