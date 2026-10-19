<?php

namespace Tests\Feature\Medecin;

use App\Models\Disponibilite;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Salle;
use App\Models\Service;
use App\Models\User;
use App\Services\FileAttenteService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientsDuJourTest extends TestCase
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

    public function test_medecin_peut_acceder_patients_du_jour(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);

        $this->actingAs($medecin)
            ->get('/medecin/patients-du-jour')
            ->assertStatus(200)
            ->assertSee('Patients du jour');
    }

    public function test_non_medecin_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get('/medecin/patients-du-jour')->assertStatus(403);

        $agent = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $agent->assignRole("Agent d'accueil");
        $this->actingAs($agent)->get('/medecin/patients-du-jour')->assertStatus(403);
    }

    public function test_medecin_voit_ses_patients_du_jour(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($medecin)
            ->test(\App\Livewire\Medecin\PatientsDuJour::class)
            ->set('date', $jour)
            ->assertSee($patient->name)
            ->assertSee('09:30')
            ->assertSee('Validé');
    }

    public function test_medecin_voit_position_et_statut_dans_la_file(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($medecin)
            ->test(\App\Livewire\Medecin\PatientsDuJour::class)
            ->set('date', $jour)
            ->assertSee('Pos: 1')
            ->assertSee('En attente');
    }

    public function test_medecin_voit_statut_en_cours_apres_demarrage(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($service->id);
        $entree = FileEntree::where('rendez_vous_id', $rdv->id)->first();

        // Agent démarre la consultation
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_EN_COURS);

        Livewire::actingAs($medecin)
            ->test(\App\Livewire\Medecin\PatientsDuJour::class)
            ->set('date', Carbon::now()->toDateString())
            ->assertSee('En cours');
    }

    public function test_medecin_voit_intervention_marquee(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        // RDV avec salle = intervention
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($medecin)
            ->test(\App\Livewire\Medecin\PatientsDuJour::class)
            ->set('date', $jour)
            ->assertSee('Intervention')
            ->assertSee('Hors file');
    }

    public function test_medecin_voit_uniquement_ses_patients(): void
    {
        $service = $this->service('Cardiologie');
        $medecin1 = $this->medecin($service->id);
        $medecin2 = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin2->assignRole('Médecin');
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin1->id, $service->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecin2->id, $service->id, $jour, '09:00', '12:00');

        $rdv1 = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin1->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $rdv2 = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin2->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($medecin1)
            ->test(\App\Livewire\Medecin\PatientsDuJour::class)
            ->set('date', $jour)
            ->assertSee($patient->name)
            ->assertDontSee('10:00'); // RDV du medecin2
    }

    public function test_medecin_ne_peut_pas_modifier_la_file(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);

        $this->actingAs($medecin)
            ->get('/accueil-personnel/file')
            ->assertStatus(403);
    }
}