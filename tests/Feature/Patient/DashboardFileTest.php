<?php

namespace Tests\Feature\Patient;

use App\Livewire\Patient\Dashboard;
use App\Livewire\Patient\FileAttente;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use App\Services\FileAttenteService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardFileTest extends TestCase
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

    /**
     * Un compte patient n'est jamais rattaché à un service (seul le personnel l'est).
     */
    protected function patient(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => null]);
        $user->assignRole('Patient');

        return $user;
    }

    protected function rdv(User $patient, User $medecin, Service $service, string $jour, string $heure, string $statut): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => $heure,
            'duree_prevue' => 30,
            'statut' => $statut,
        ]);
    }

    protected function entree(User $patient, RendezVous $rdv, Service $service, string $jour, string $statut, int $position): FileEntree
    {
        return FileEntree::create([
            'service_id' => $service->id,
            'rendez_vous_id' => $rdv->id,
            'patient_id' => $patient->id,
            'date' => $jour,
            'statut' => $statut,
            'position' => $position,
            'priorite' => false,
        ]);
    }

    public function test_carte_affiche_position_quand_entree_en_attente(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->toDateString();

        $rdv = $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->entree($patient, $rdv, $service, $jour, FileEntree::STATUT_EN_ATTENTE, 3);

        Livewire::actingAs($patient)
            ->test(Dashboard::class)
            ->assertSee('Position dans la file')
            ->assertSee('3')
            ->assertDontSee('Pas en file');
    }

    public function test_carte_affiche_position_quand_entree_en_cours(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->toDateString();

        $rdv = $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->entree($patient, $rdv, $service, $jour, FileEntree::STATUT_EN_COURS, 1);

        Livewire::actingAs($patient)
            ->test(Dashboard::class)
            ->assertSee('Position dans la file')
            ->assertSee('1')
            ->assertDontSee('Pas en file');
    }

    public function test_carte_affiche_pas_en_file_sans_entree_du_jour(): void
    {
        $patient = $this->patient();

        Livewire::actingAs($patient)
            ->test(Dashboard::class)
            ->assertSee('Position dans la file')
            ->assertSee('Pas en file');
    }

    public function test_carte_affiche_pas_en_file_quand_entree_terminee(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->toDateString();

        $rdv = $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->entree($patient, $rdv, $service, $jour, FileEntree::STATUT_TERMINE, 1);

        Livewire::actingAs($patient)
            ->test(Dashboard::class)
            ->assertSee('Pas en file');
    }

    public function test_carte_affiche_pas_en_file_quand_entree_absente(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->toDateString();

        $rdv = $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->entree($patient, $rdv, $service, $jour, FileEntree::STATUT_ABSENT, 1);

        Livewire::actingAs($patient)
            ->test(Dashboard::class)
            ->assertSee('Pas en file');
    }

    public function test_dashboard_et_ma_file_affichent_la_meme_position(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();
        $jour = Carbon::now()->toDateString();

        $this->rdv($patient1, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->rdv($patient2, $medecin, $service, $jour, '10:00', RendezVous::STATUT_VALIDE);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $positionService = app(FileAttenteService::class)
            ->getEntreePatient($patient2->id)?->position;

        $this->assertNotNull($positionService);

        Livewire::actingAs($patient2)
            ->test(Dashboard::class)
            ->assertSee('Position dans la file')
            ->assertSee((string) $positionService)
            ->assertDontSee('Pas en file');

        Livewire::actingAs($patient2)
            ->test(FileAttente::class)
            ->assertSee((string) $positionService);
    }
}
