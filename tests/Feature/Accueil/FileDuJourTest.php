<?php

namespace Tests\Feature\Accueil;

use App\Models\Disponibilite;
use App\Models\FileEntree;
use App\Models\Parametre;
use App\Models\RendezVous;
use App\Models\Salle;
use App\Models\Service;
use App\Models\User;
use App\Services\FileAttenteService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FileDuJourTest extends TestCase
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

    protected function agent(int $serviceId): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $serviceId]);
        $user->assignRole("Agent d'accueil");
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

    public function test_agent_peut_acceder_file_du_jour(): void
    {
        $service = $this->service('Cardiologie');
        $this->medecin($service->id);

        $this->actingAs($this->agent($service->id))
            ->get('/accueil-personnel/file')
            ->assertStatus(200)
            ->assertSee('File du jour');
    }

    public function test_non_agent_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = $this->patient();
        $this->actingAs($patient)->get('/accueil-personnel/file')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/accueil-personnel/file')->assertStatus(403);

        $secretaire = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $secretaire->assignRole('Secrétaire de service');
        $this->actingAs($secretaire)->get('/accueil-personnel/file')->assertStatus(403);
    }

    public function test_generation_file_cree_entrees_pour_rdv_valides_sans_salle(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        // RDV Validé sans salle -> doit entrer dans la file
        $rdv1 = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        // RDV Demandé sans salle -> NE DOIT PAS entrer (seuls Validés)
        $patient2 = $this->patient();
        $rdv2 = RendezVous::create([
            'patient_id' => $patient2->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        // RDV Annulé sans salle -> NE DOIT PAS entrer
        $patient3 = $this->patient();
        $rdv3 = RendezVous::create([
            'patient_id' => $patient3->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        // RDV Validé AVEC salle (intervention) -> NE DOIT PAS entrer
        $patient4 = $this->patient();
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);
        $rdv4 = RendezVous::create([
            'patient_id' => $patient4->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => $jour,
            'heure_debut' => '11:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('generer')
            ->assertSet('messageErreur', null);

        $this->assertDatabaseHas('file_entrees', [
            'rendez_vous_id' => $rdv1->id,
            'statut' => FileEntree::STATUT_EN_ATTENTE,
        ]);
        $this->assertDatabaseMissing('file_entrees', ['rendez_vous_id' => $rdv2->id]);
        $this->assertDatabaseMissing('file_entrees', ['rendez_vous_id' => $rdv3->id]);
        $this->assertDatabaseMissing('file_entrees', ['rendez_vous_id' => $rdv4->id]);
    }

    public function test_generation_file_est_idempotente(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        // Première génération
        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('generer')
            ->assertSet('messageErreur', null);

        // Deuxième génération (relance) -> ne doit pas créer de doublon
        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('generer')
            ->assertSet('messageErreur', null);

        $this->assertDatabaseCount('file_entrees', 1);
        $this->assertDatabaseHas('file_entrees', ['rendez_vous_id' => $rdv->id]);
    }

    public function test_agent_peut_demarrer_consultation(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        // Générer la file
        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree = FileEntree::where('rendez_vous_id', $rdv->id)->first();
        $this->assertSame(FileEntree::STATUT_EN_ATTENTE, $entree->statut);

        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('demarrer', $entree->id)
            ->assertSet('messageErreur', null);

        $entree->refresh();
        $this->assertSame(FileEntree::STATUT_EN_COURS, $entree->statut);

        // Vérifier journalisation
        $this->assertDatabaseHas('activity_log', [
            'event' => 'changement_statut_file',
            'subject_id' => $entree->id,
        ]);
    }

    public function test_agent_peut_terminer_consultation(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        // Démarrer
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_EN_COURS);
        $entree->refresh();
        $this->assertSame(FileEntree::STATUT_EN_COURS, $entree->statut);

        // Terminer
        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('terminer', $entree->id)
            ->assertSet('messageErreur', null);

        $entree->refresh();
        $this->assertSame(FileEntree::STATUT_TERMINE, $entree->statut);
    }

    public function test_agent_peut_marquer_absent(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('marquerAbsent', $entree->id)
            ->assertSet('messageErreur', null);

        $entree->refresh();
        $this->assertSame(FileEntree::STATUT_ABSENT, $entree->statut);
    }

    public function test_erreur_technique_generation_n_expose_pas_le_message_brut(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);

        $this->mock(FileAttenteService::class, function ($mock) {
            $mock->shouldReceive('genererFileDuJour')
                ->once()
                ->andThrow(new \RuntimeException('SQLSTATE[HY000] secret interne'));
            $mock->shouldReceive('getFileDuJour')->andReturn(collect());
        });

        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('generer')
            ->assertSet('messageErreur', 'Une erreur est survenue lors de la génération de la file. Veuillez réessayer.')
            ->assertDontSee('SQLSTATE');
    }

    public function test_agent_peut_remettre_en_attente_un_absent(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        // Marquer absent
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_ABSENT);
        $entree->refresh();
        $this->assertSame(FileEntree::STATUT_ABSENT, $entree->statut);

        // Remettre en attente
        Livewire::actingAs($agent)
            ->test(\App\Livewire\Accueil\FileDuJour::class)
            ->call('remettreEnAttente', $entree->id)
            ->assertSet('messageErreur', null);

        $entree->refresh();
        $this->assertSame(FileEntree::STATUT_EN_ATTENTE, $entree->statut);
        $this->assertFalse($entree->priorite);
    }

    public function test_transitions_interdites_refusees(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        // Tenter En attente -> Terminé (interdit)
        $this->expectException(\Exception::class);
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_TERMINE);

        // Tenter En cours -> En attente (interdit)
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_EN_COURS);
        $entree->refresh();

        $this->expectException(\Exception::class);
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_EN_ATTENTE);
    }

    public function test_priorite_obligatoire_pour_passage_prioritaire(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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

        // Tenter passage prioritaire sans motif -> doit échouer
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('obligatoire');
        app(FileAttenteService::class)->passerPrioritaire($entree->id, '');
    }

    public function test_passage_prioritaire_passe_devant(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdv1 = RendezVous::create([
            'patient_id' => $patient1->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $rdv2 = RendezVous::create([
            'patient_id' => $patient2->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($service->id);

        $entree1 = FileEntree::where('rendez_vous_id', $rdv1->id)->first();
        $entree2 = FileEntree::where('rendez_vous_id', $rdv2->id)->first();

        // patient1 position 1, patient2 position 2
        $this->assertSame(1, $entree1->position);
        $this->assertSame(2, $entree2->position);

        // patient2 passe en priorité
        app(FileAttenteService::class)->passerPrioritaire($entree2->id, 'Urgence médicale');

        $entree1->refresh();
        $entree2->refresh();

        // patient2 passe devant (position 1), patient1 recule (position 2)
        $this->assertSame(1, $entree2->position);
        $this->assertSame(2, $entree1->position);
        $this->assertTrue($entree2->priorite);
    }

    public function test_agent_voit_uniquement_file_de_son_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $agentA = $this->agent($serviceA->id);
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        $patientA = $this->patient();
        $patientB = $this->patient();

        $rdvA = RendezVous::create([
            'patient_id' => $patientA->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $rdvB = RendezVous::create([
            'patient_id' => $patientB->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($serviceA->id);
        app(FileAttenteService::class)->genererFileDuJour($serviceB->id);

        $component = Livewire::actingAs($agentA)
            ->test(\App\Livewire\Accueil\FileDuJour::class);

        $html = $component->html();
        $this->assertStringContainsString($patientA->name, $html);
        $this->assertStringNotContainsString($patientB->name, $html);
    }

    public function test_agent_ne_peut_pas_modifier_la_file_d_un_autre_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $agentA = $this->agent($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = Carbon::now()->toDateString();
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        $rdvB = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        app(FileAttenteService::class)->genererFileDuJour($serviceB->id);
        $entreeB = FileEntree::where('rendez_vous_id', $rdvB->id)->firstOrFail();

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($agentA)
                ->test(\App\Livewire\Accueil\FileDuJour::class)
                ->call('demarrer', $entreeB->id);

            $this->fail("L'agent ne doit pas pouvoir modifier la file d'un autre service.");
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(FileEntree::STATUT_EN_ATTENTE, $entreeB->fresh()->statut);
    }

    public function test_agent_ne_peut_pas_modifier_une_entree_dune_autre_date(): void
    {
        $service = $this->service('Cardiologie');
        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->subDay()->toDateString();
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

        app(FileAttenteService::class)->genererFileDuJour($service->id, Carbon::now()->subDay());
        $entree = FileEntree::where('rendez_vous_id', $rdv->id)->firstOrFail();

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($agent)
                ->test(\App\Livewire\Accueil\FileDuJour::class)
                ->call('demarrer', $entree->id);

            $this->fail("L'agent ne doit pas pouvoir modifier une entrée d'une autre date.");
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(FileEntree::STATUT_EN_ATTENTE, $entree->fresh()->statut);
    }

    public function test_remettre_en_attente_exige_la_permission_file_gerer(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

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
        $entree = FileEntree::where('rendez_vous_id', $rdv->id)->firstOrFail();
        app(FileAttenteService::class)->changerStatut($entree->id, FileEntree::STATUT_ABSENT);

        $utilisateur = User::factory()->create([
            'statut_compte' => 'Actif',
            'service_id' => $service->id,
        ]);
        $utilisateur->givePermissionTo('file.voir');

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($utilisateur)
                ->test(\App\Livewire\Accueil\FileDuJour::class)
                ->call('remettreEnAttente', $entree->id);

            $this->fail('La permission file.gerer est obligatoire pour remettre un patient en attente.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(FileEntree::STATUT_ABSENT, $entree->fresh()->statut);
    }
}