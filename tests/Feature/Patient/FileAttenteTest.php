<?php

namespace Tests\Feature\Patient;

use App\Livewire\Patient\FileAttente;
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
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class FileAttenteTest extends TestCase
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

    protected function rdv(User $patient, User $medecin, Service $service, string $jour, string $heure, string $statut, ?int $salleId = null): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salleId,
            'date' => $jour,
            'heure_debut' => $heure,
            'duree_prevue' => 30,
            'statut' => $statut,
        ]);
    }

    public function test_patient_peut_acceder_sa_file(): void
    {
        $patient = $this->patient();

        $this->actingAs($patient)
            ->get('/patient/file')
            ->assertStatus(200);
    }

    public function test_patient_sans_rdv_aujourdhui_voit_etat_a(): void
    {
        $patient = $this->patient();

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Vous n\'avez pas de rendez-vous prévu aujourd\'hui.', false);
    }

    public function test_patient_voit_sa_position_dans_la_file(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('En attente')
            ->assertSee('dans la file')
            ->assertSee('0 personne(s) devant vous');
    }

    public function test_patient_voit_nombre_personnes_devant_lui(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');
        $this->rdv($patient1, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->rdv($patient2, $medecin, $service, $jour, '10:00', RendezVous::STATUT_VALIDE);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($patient1)
            ->test(FileAttente::class)
            ->assertSee('0 personne(s) devant vous');

        Livewire::actingAs($patient2)
            ->test(FileAttente::class)
            ->assertSee('1 personne(s) devant vous');
    }

    public function test_entree_en_cours_affiche_statut(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree = FileEntree::where('patient_id', $patient->id)->firstOrFail();
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_EN_COURS);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('En cours de consultation');
    }

    public function test_entree_terminee_affiche_statut(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree = FileEntree::where('patient_id', $patient->id)->firstOrFail();
        $svc = app(FileAttenteService::class);
        $svc->changerStatut($entree->id, FileEntree::STATUT_EN_COURS);
        $svc->changerStatut($entree->id, FileEntree::STATUT_TERMINE);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Votre consultation est terminée.')
            ->assertDontSee('Vous n\'avez pas de rendez-vous prévu aujourd\'hui.', false);
    }

    public function test_entree_absente_affiche_statut(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree = FileEntree::where('patient_id', $patient->id)->firstOrFail();
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_ABSENT);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Vous avez été marqué absent. Rapprochez-vous de l\'accueil.', false);
    }

    public function test_rdv_valide_sans_entree_affiche_etat_b(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        // File non générée : aucune entrée.

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Votre rendez-vous est prévu à 09:30. La file d\'attente sera générée par l\'accueil.', false);
    }

    public function test_confidentialite_autres_patients_non_affiches(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();
        $patient2->update(['name' => 'NomConfidentielXYZ']);

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient1, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        $this->rdv($patient2, $medecin, $service, $jour, '10:00', RendezVous::STATUT_VALIDE);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $html = Livewire::actingAs($patient1)
            ->test(FileAttente::class)
            ->html();

        $this->assertStringNotContainsString('NomConfidentielXYZ', $html);
        $this->assertStringNotContainsString('File complète', $html);
    }

    public function test_patient_ne_recupere_pas_entree_dun_autre_patient(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient2, $medecin, $service, $jour, '10:00', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        // patient1 n'a ni RDV ni entrée : le service ne doit jamais lui renvoyer celle de patient2.
        $this->assertNull(
            app(FileAttenteService::class)->getEntreePatient($patient1->id)
        );

        Livewire::actingAs($patient1)
            ->test(FileAttente::class)
            ->assertSee('Vous n\'avez pas de rendez-vous prévu aujourd\'hui.', false);
    }

    public function test_rdv_intervention_n_est_pas_dans_la_file(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE, $salle->id);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        // Une intervention a un RDV aujourd'hui mais n'entre pas dans la file : situation indéterminée.
        $this->assertNull(
            app(FileAttenteService::class)->getEntreePatient($patient->id)
        );

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Votre situation n\'a pas pu être déterminée', false);
    }

    public function test_rdv_annule_mene_a_situation_indeterminee(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_ANNULE);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $this->assertNull(
            app(FileAttenteService::class)->getEntreePatient($patient->id)
        );

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Votre situation n\'a pas pu être déterminée', false);
    }

    public function test_erreur_technique_affiche_etat_d(): void
    {
        Log::spy();

        $this->mock(FileAttenteService::class, function ($mock) {
            $mock->shouldReceive('getEntreePatient')->andThrow(new \RuntimeException('boom'));
        });

        $patient = $this->patient();

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Une erreur est survenue')
            ->assertSee('Réessayez dans un instant.');

        Log::shouldHaveReceived('error');
    }

    public function test_position_affiche_etiquette_et_etapes_reelles(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Votre position')
            ->assertSee('Progression dans la file d\'attente', false)
            ->assertSee('En attente')
            ->assertSee('En cours')
            ->assertSee('Terminé');
    }

    public function test_statut_en_cours_affiche_message_dappel(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree = FileEntree::where('patient_id', $patient->id)->firstOrFail();
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_EN_COURS);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('En cours de consultation')
            ->assertSee('C\'est votre tour', false);
    }

    public function test_statut_absent_nest_pas_presente_comme_termine_ni_progression(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->rdv($patient, $medecin, $service, $jour, '09:30', RendezVous::STATUT_VALIDE);
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree = FileEntree::where('patient_id', $patient->id)->firstOrFail();
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_ABSENT);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Absent')
            ->assertDontSee('Progression dans la file d\'attente', false)
            ->assertDontSee('Votre consultation est terminée.')
            ->assertDontSee('C\'est votre tour', false);
    }

    public function test_prochain_rdv_un_autre_jour_affiche_dans_etat_aucun(): void
    {
        $service = $this->service('Pédiatrie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $demain = Carbon::now()->addDay()->toDateString();
        $this->rdv($patient, $medecin, $service, $demain, '10:00', RendezVous::STATUT_VALIDE);

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertSee('Vous n\'avez pas de rendez-vous prévu aujourd\'hui.', false)
            ->assertSee('Prochain rendez-vous')
            ->assertSee('Pédiatrie');
    }

    public function test_aucun_prochain_rdv_affiche(): void
    {
        $patient = $this->patient();

        Livewire::actingAs($patient)
            ->test(FileAttente::class)
            ->assertDontSee('Prochain rendez-vous');
    }
}
