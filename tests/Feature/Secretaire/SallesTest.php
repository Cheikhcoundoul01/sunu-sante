<?php

namespace Tests\Feature\Secretaire;

use App\Models\DisponibiliteSalle;
use App\Models\RendezVous;
use App\Models\Salle;
use App\Models\Service;
use App\Models\User;
use App\Services\SalleService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SallesTest extends TestCase
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

    protected function secretaire(int $serviceId): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $serviceId]);
        $user->assignRole('Secrétaire de service');
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

    public function test_secretaire_peut_acceder_ecran_salles(): void
    {
        $service = $this->service('Cardiologie');
        $this->medecin($service->id);

        $this->actingAs($this->secretaire($service->id))
            ->get('/secretaire/salles')
            ->assertStatus(200);
    }

    public function test_non_secretaire_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = $this->patient();
        $this->actingAs($patient)->get('/secretaire/salles')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/secretaire/salles')->assertStatus(403);
    }

    public function test_secretaire_peut_ajouter_une_salle(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->set('nom', 'Bloc opératoire 1')
            ->set('description', 'Salle de chirurgie cardiaque')
            ->set('active', true)
            ->call('ajouterSalle')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Salle ajoutée avec succès.');

        $this->assertDatabaseHas('salles', [
            'service_id' => $service->id,
            'nom' => 'Bloc opératoire 1',
            'description' => 'Salle de chirurgie cardiaque',
            'active' => true,
        ]);
    }

    public function test_secretaire_ne_peut_pas_ajouter_salle_meme_nom(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);

        Salle::create([
            'service_id' => $service->id,
            'nom' => 'Bloc 1',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->set('nom', 'Bloc 1')
            ->call('ajouterSalle')
            ->assertSet('messageErreur', 'Une salle avec ce nom existe déjà dans votre service.');

        $this->assertDatabaseCount('salles', 1);
    }

    public function test_secretaire_voit_uniquement_salles_de_son_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);

        Salle::create(['service_id' => $serviceA->id, 'nom' => 'Salle A', 'active' => true]);
        Salle::create(['service_id' => $serviceB->id, 'nom' => 'Salle B', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->assertSee('Salle A')
            ->assertDontSee('Salle B');
    }

    public function test_secretaire_peut_basculer_statut_salle(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('basculerSalle', $salle->id)
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Salle désactivée.');

        $salle->refresh();
        $this->assertFalse($salle->active);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('basculerSalle', $salle->id)
            ->assertSet('messageSucces', 'Salle réactivée.');

        $salle->refresh();
        $this->assertTrue($salle->active);
    }

    public function test_secretaire_peut_ajouter_disponibilite_salle(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->set('dispoSalleId', $salle->id)
            ->set('dispoJourSemaine', 1) // Lundi
            ->set('dispoHeureDebut', '08:00')
            ->set('dispoHeureFin', '12:00')
            ->set('dispoActive', true)
            ->call('ajouterDisponibilite')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Disponibilité ajoutée.');

        $this->assertDatabaseHas('disponibilite_salles', [
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);
    }

    public function test_chevauchement_disponibilites_refuse(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->set('dispoSalleId', $salle->id)
            ->set('dispoJourSemaine', 1)
            ->set('dispoHeureDebut', '10:00')
            ->set('dispoHeureFin', '14:00')
            ->call('ajouterDisponibilite')
            ->assertSet('messageErreur', 'Une disponibilité existe déjà sur cette plage horaire pour ce jour.');

        $this->assertDatabaseCount('disponibilite_salles', 1);
    }

    public function test_disponibilite_hors_plage_horaire_refuse(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->set('dispoSalleId', $salle->id)
            ->set('dispoJourSemaine', 1)
            ->set('dispoHeureDebut', '14:00')
            ->set('dispoHeureFin', '10:00') // Fin avant début
            ->call('ajouterDisponibilite')
            ->assertSet('messageErreur', fn ($message) => str_contains($message, 'after') || str_contains($message, 'après'));
    }

    public function test_salle_inactive_n_a_pas_de_creneaux(): void
    {
        $service = $this->service('Cardiologie');
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => false]);

        $creneaux = app(SalleService::class)->creneauxLibres($salle, Carbon::now()->addDay());

        $this->assertSame([], $creneaux);
    }

    public function test_creneau_libre_dans_plage_disponibilite(): void
    {
        $service = $this->service('Cardiologie');
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1, // Lundi
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        $lundiProchain = Carbon::now()->next('Monday');
        $creneaux = app(SalleService::class)->creneauxLibres($salle, $lundiProchain, 60);

        $this->assertNotEmpty($creneaux);
        $this->assertEquals('08:00', $creneaux[0]['debut']);
        $this->assertEquals('12:00', end($creneaux)['fin']);
    }

    public function test_conflit_rdv_intervention_salle(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');
        $patient = $this->patient();
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => (int) Carbon::now()->addDay()->format('N'),
            'heure_debut' => '08:00',
            'heure_fin' => '18:00',
            'active' => true,
        ]);

        $jour = Carbon::now()->addDay()->toDateString();

        // Premier RDV intervention
        $rdv1 = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        // Deuxième RDV sur même créneau même salle - doit être refusé
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ce créneau n\'est plus disponible pour cette salle.');

        app(SalleService::class)->verifierConflit($salle->id, Carbon::parse($jour), '10:00', 60);
    }

    public function test_rdv_annule_libere_creneau_salle(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');
        $patient = $this->patient();
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => (int) Carbon::now()->addDay()->format('N'),
            'heure_debut' => '08:00',
            'heure_fin' => '18:00',
            'active' => true,
        ]);

        $jour = Carbon::now()->addDay()->toDateString();

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        // Annuler le RDV
        $rdv->update(['statut' => RendezVous::STATUT_ANNULE]);

        // Le créneau doit être libre maintenant
        $estDispo = app(SalleService::class)->estDisponible($salle->id, Carbon::parse($jour), '10:00', 60);
        $this->assertTrue($estDispo);
    }

    public function test_rdv_intervention_n_entre_pas_dans_file_attente(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');
        $patient = $this->patient();
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        $rdvIntervention = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => Carbon::now()->addDay()->toDateString(),
            'heure_debut' => '10:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $rdvConsultation = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => Carbon::now()->addDay()->toDateString(),
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $this->assertTrue($rdvIntervention->estIntervention());
        $this->assertFalse($rdvConsultation->estIntervention());
    }

    public function test_secretaire_peut_modifier_le_nom_et_la_description_d_une_salle(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create([
            'service_id' => $service->id,
            'nom' => 'Bloc 1',
            'description' => 'Ancienne description',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierSalle', $salle->id)
            ->assertSet('salleId', $salle->id)
            ->assertSet('nom', 'Bloc 1')
            ->assertSet('description', 'Ancienne description')
            ->set('nom', 'Bloc opératoire 1')
            ->set('description', 'Nouvelle description')
            ->call('enregistrerSalle')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Salle mise à jour.');

        $salle->refresh();

        $this->assertSame('Bloc opératoire 1', $salle->nom);
        $this->assertSame('Nouvelle description', $salle->description);
        // Le service et l'activation ne changent pas.
        $this->assertSame($service->id, $salle->service_id);
        $this->assertTrue($salle->active);
    }

    public function test_modification_refusee_si_le_nom_existe_deja_dans_le_meme_service(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);

        Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 2', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierSalle', $salle->id)
            ->set('nom', 'Bloc 1')
            ->call('enregistrerSalle')
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Une salle avec ce nom existe déjà dans votre service.');

        $salle->refresh();

        $this->assertSame('Bloc 2', $salle->nom);
    }

    public function test_le_meme_nom_est_autorise_dans_un_autre_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);

        // Salle homonyme dans un autre service : elle ne bloque pas la salle du
        // service de la secrétaire.
        Salle::create(['service_id' => $serviceB->id, 'nom' => 'Bloc 1', 'active' => true]);
        $salleA = Salle::create(['service_id' => $serviceA->id, 'nom' => 'Bloc 2', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierSalle', $salleA->id)
            ->set('nom', 'Bloc 1')
            ->call('enregistrerSalle')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Salle mise à jour.');

        $salleA->refresh();

        $this->assertSame('Bloc 1', $salleA->nom);
        $this->assertSame($serviceA->id, $salleA->service_id);
    }

    public function test_creation_autorisee_meme_si_le_nom_existe_dans_un_autre_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);

        Salle::create(['service_id' => $serviceB->id, 'nom' => 'Bloc 1', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->set('nom', 'Bloc 1')
            ->call('enregistrerSalle')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Salle ajoutée avec succès.');

        $this->assertDatabaseHas('salles', [
            'service_id' => $serviceA->id,
            'nom' => 'Bloc 1',
        ]);
    }

    public function test_secretaire_ne_peut_pas_modifier_une_salle_hors_perimetre(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);
        $salle = Salle::create(['service_id' => $serviceB->id, 'nom' => 'Bloc 1', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierSalle', $salle->id)
            ->assertSet('salleId', null)
            ->assertSet('messageErreur', 'Salle introuvable dans votre service.');

        $salle->refresh();

        $this->assertSame('Bloc 1', $salle->nom);
    }

    public function test_un_compte_sans_permission_ne_peut_pas_modifier_une_salle(): void
    {
        $service = $this->service('Cardiologie');
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);
        $medecin = $this->medecin($service->id);

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($medecin)
                ->test(\App\Livewire\Secretaire\Salles::class)
                ->call('modifierSalle', $salle->id);
            $this->fail('Une réponse 403 était attendue pour un compte sans la permission salles.gerer.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $salle->refresh();

        $this->assertSame('Bloc 1', $salle->nom);
    }

    public function test_secretaire_peut_modifier_une_disponibilite(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        $dispo = DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierDisponibilite', $dispo->id)
            ->assertSet('dispoId', $dispo->id)
            ->assertSet('dispoSalleId', $salle->id)
            ->assertSet('dispoJourSemaine', 1)
            ->assertSet('dispoHeureDebut', '08:00')
            ->assertSet('dispoHeureFin', '12:00')
            ->set('dispoJourSemaine', 3)
            ->set('dispoHeureDebut', '09:00')
            ->set('dispoHeureFin', '13:00')
            ->call('enregistrerDisponibilite')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Disponibilité modifiée.');

        $dispo->refresh();

        $this->assertSame(3, $dispo->jour_semaine);
        $this->assertSame('09:00', substr($dispo->heure_debut, 0, 5));
        $this->assertSame('13:00', substr($dispo->heure_fin, 0, 5));
        // La salle et l'activation ne changent pas.
        $this->assertSame($salle->id, $dispo->salle_id);
        $this->assertTrue($dispo->active);
    }

    public function test_modification_de_disponibilite_refusee_avec_des_horaires_invalides(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        $dispo = DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierDisponibilite', $dispo->id)
            ->set('dispoHeureDebut', '14:00')
            ->set('dispoHeureFin', '10:00')
            ->call('enregistrerDisponibilite')
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', fn ($message) => str_contains($message, 'after') || str_contains($message, 'après'));

        $dispo->refresh();

        $this->assertSame('08:00', substr($dispo->heure_debut, 0, 5));
        $this->assertSame('12:00', substr($dispo->heure_fin, 0, 5));
    }

    public function test_modification_de_disponibilite_refusee_si_la_plage_se_chevauche(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        $dispo = DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '14:00',
            'heure_fin' => '18:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierDisponibilite', $dispo->id)
            ->set('dispoHeureDebut', '10:00')
            ->set('dispoHeureFin', '16:00')
            ->call('enregistrerDisponibilite')
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Une disponibilité existe déjà sur cette plage horaire pour ce jour.');

        $dispo->refresh();

        $this->assertSame('14:00', substr($dispo->heure_debut, 0, 5));
        $this->assertSame('18:00', substr($dispo->heure_fin, 0, 5));
    }

    public function test_modification_de_disponibilite_refusee_si_une_intervention_active_est_afectee(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');
        $patient = $this->patient();
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        $dispo = DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '18:00',
            'active' => true,
        ]);

        // Intervention active le lundi à 10:00 pendant 60 minutes.
        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => Carbon::now()->next('Monday')->toDateString(),
            'heure_debut' => '10:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierDisponibilite', $dispo->id)
            ->set('dispoHeureDebut', '12:00')
            ->set('dispoHeureFin', '18:00')
            ->call('enregistrerDisponibilite')
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', fn ($message) => str_contains($message, 'intervention')
                && str_contains($message, '10:00'));

        // La disponibilité n'est pas modifiée et le rendez-vous est intact.
        $dispo->refresh();
        $this->assertSame('08:00', substr($dispo->heure_debut, 0, 5));
        $this->assertSame('18:00', substr($dispo->heure_fin, 0, 5));

        $rdv->refresh();
        $this->assertSame('10:00', substr($rdv->heure_debut, 0, 5));
        $this->assertSame(RendezVous::STATUT_VALIDE, $rdv->statut);
    }

    public function test_une_intervention_annulee_ne_bloque_pas_la_modification(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');
        $patient = $this->patient();
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'active' => true]);

        $dispo = DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '18:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => Carbon::now()->next('Monday')->toDateString(),
            'heure_debut' => '10:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierDisponibilite', $dispo->id)
            ->set('dispoHeureDebut', '12:00')
            ->set('dispoHeureFin', '18:00')
            ->call('enregistrerDisponibilite')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Disponibilité modifiée.');

        $dispo->refresh();

        $this->assertSame('12:00', substr($dispo->heure_debut, 0, 5));
        $this->assertSame('18:00', substr($dispo->heure_fin, 0, 5));
    }

    public function test_la_modification_conserve_les_donnees_existantes(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');
        $patient = $this->patient();
        $secretaire = $this->secretaire($service->id);
        $salle = Salle::create(['service_id' => $service->id, 'nom' => 'Bloc 1', 'description' => 'Description', 'active' => true]);

        $dispo = DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '18:00',
            'active' => true,
        ]);

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'salle_id' => $salle->id,
            'date' => Carbon::now()->next('Monday')->toDateString(),
            'heure_debut' => '10:00',
            'duree_prevue' => 60,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\Salles::class)
            ->call('modifierSalle', $salle->id)
            ->set('nom', 'Bloc opératoire 1')
            ->call('enregistrerSalle')
            ->assertSet('messageErreur', null);

        // Aucune suppression, aucun déplacement d'intervention.
        $this->assertDatabaseCount('salles', 1);
        $this->assertDatabaseCount('disponibilite_salles', 1);
        $this->assertDatabaseHas('disponibilite_salles', [
            'id' => $dispo->id,
            'salle_id' => $salle->id,
            'service_id' => $service->id,
            'jour_semaine' => 1,
            'active' => true,
        ]);
        $this->assertDatabaseHas('rendez_vouses', [
            'id' => $rdv->id,
            'salle_id' => $salle->id,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $dispo->refresh();
        $this->assertSame('08:00', substr($dispo->heure_debut, 0, 5));
        $this->assertSame('18:00', substr($dispo->heure_fin, 0, 5));

        $rdv->refresh();
        $this->assertSame('10:00', substr($rdv->heure_debut, 0, 5));
    }
}