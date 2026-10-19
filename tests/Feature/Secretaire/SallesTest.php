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
}