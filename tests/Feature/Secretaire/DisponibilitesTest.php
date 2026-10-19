<?php

namespace Tests\Feature\Secretaire;

use App\Livewire\Secretaire\Disponibilites;
use App\Models\Disponibilite;
use App\Models\Parametre;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use App\Services\CreneauService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisponibilitesTest extends TestCase
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

    protected function secretaire(?int $serviceId = null): User
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

    public function test_secretaire_peut_acceder_ecran_disponibilites(): void
    {
        $service = $this->service('Cardiologie');
        $this->medecin($service->id);

        $this->actingAs($this->secretaire($service->id))
            ->get('/secretaire/disponibilites')
            ->assertStatus(200)
            ->assertSee('Disponibilités');
    }

    public function test_non_secretaire_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $this->actingAs($patient)->get('/secretaire/disponibilites')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/secretaire/disponibilites')->assertStatus(403);
    }

    public function test_secretaire_ajoute_une_disponibilite_pour_son_service(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jour = Carbon::now()->addDay()->toDateString();

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('medecinId', $medecin->id)
            ->set('jour', $jour)
            ->set('heure_debut', '09:00')
            ->set('heure_fin', '11:00')
            ->call('ajouter')
            ->assertSet('messageErreur', null);

        $this->assertDatabaseHas('disponibilites', [
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);
    }

    public function test_secretaire_ne_peut_pas_gerer_un_medecin_d_un_autre_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('medecinId', $medecinB->id)
            ->set('jour', Carbon::now()->addDay()->toDateString())
            ->set('heure_debut', '09:00')
            ->set('heure_fin', '11:00')
            ->call('ajouter')
            ->assertSet('messageErreur', 'Médecin introuvable dans votre service.');

        $this->assertDatabaseCount('disponibilites', 0);
    }

    public function test_liste_des_medecins_limitee_au_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->assertSee($medecinA->name)
            ->assertDontSee($medecinB->name);
    }

    public function test_creneaux_libres_excluent_les_rendez_vous_actifs(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $reference = Carbon::parse('2026-06-15 08:00:00');
        $jour = $reference->copy()->startOfDay();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour->toDateString(),
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour->toDateString(),
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $creneaux = app(CreneauService::class)->creneauxLibres($dispo, $reference);
        $debuts = array_column($creneaux, 'debut');

        $this->assertSame(['09:00', '10:00', '10:30'], $debuts);
    }

    public function test_un_creneau_annule_redevient_libre(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $reference = Carbon::parse('2026-06-15 08:00:00');
        $jour = $reference->copy()->startOfDay();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour->toDateString(),
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour->toDateString(),
            'heure_debut' => '09:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        $creneaux = app(CreneauService::class)->creneauxLibres($dispo, $reference);
        $debuts = array_column($creneaux, 'debut');

        $this->assertSame(['09:00', '09:30'], $debuts);
    }

    public function test_horizon_limite_les_creneaux_et_la_date_limite(): void
    {
        Parametre::create(['cle' => Parametre::CLE_HORIZON_RESERVATION, 'valeur' => '30']);

        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $reference = Carbon::parse('2026-06-15 08:00:00');

        $serviceCreneaux = app(CreneauService::class);

        // À la limite de l'horizon (15/06 + 30 jours) : réservable.
        $dispoLimite = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $reference->copy()->addDays(30)->toDateString(),
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
            'active' => true,
        ]);

        // Au-delà de l'horizon : aucun créneau.
        $dispoHors = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $reference->copy()->addDays(31)->toDateString(),
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
            'active' => true,
        ]);

        $this->assertTrue($serviceCreneaux->estDansHorizon($reference->copy()->addDays(30), $reference));
        $this->assertFalse($serviceCreneaux->estDansHorizon($reference->copy()->addDays(31), $reference));

        $this->assertNotEmpty($serviceCreneaux->creneauxLibres($dispoLimite, $reference));
        $this->assertSame([], $serviceCreneaux->creneauxLibres($dispoHors, $reference));
    }

    public function test_ajout_hors_horizon_refuse(): void
    {
        Parametre::create(['cle' => Parametre::CLE_HORIZON_RESERVATION, 'valeur' => '30']);

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jourHors = Carbon::now()->addDays(31)->toDateString();

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('medecinId', $medecin->id)
            ->set('jour', $jourHors)
            ->set('heure_debut', '09:00')
            ->set('heure_fin', '10:00')
            ->call('ajouter')
            ->assertSet('messageErreur', "Cette date dépasse l'horizon de réservation (30 jours).");

        $this->assertDatabaseCount('disponibilites', 0);
    }

    public function test_ajout_date_passee_refuse(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jourPasse = Carbon::now()->subDay()->toDateString();

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('medecinId', $medecin->id)
            ->set('jour', $jourPasse)
            ->set('heure_debut', '09:00')
            ->set('heure_fin', '10:00')
            ->call('ajouter')
            ->assertSet('messageErreur', 'La date choisie est déjà passée.');

        $this->assertDatabaseCount('disponibilites', 0);
    }
}
