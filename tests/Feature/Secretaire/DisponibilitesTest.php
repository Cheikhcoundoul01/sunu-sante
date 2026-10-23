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
use Illuminate\Support\Facades\DB;
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

    protected function patient(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Patient');

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

    public function test_creneaux_passes_absents_de_la_liste_principale_et_presents_dans_l_historique(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $passé = Carbon::now()->subDay()->toDateString();
        $avenir = Carbon::now()->addDay()->toDateString();

        $dispoPassee = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $passé,
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);

        $dispoFutur = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $avenir,
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('onglet', 'principal')
            ->assertSee(Carbon::parse($dispoFutur->jour)->format('d/m/Y'))
            ->assertDontSee(Carbon::parse($dispoPassee->jour)->format('d/m/Y'))
            ->set('onglet', 'historique')
            ->assertSee(Carbon::parse($dispoPassee->jour)->format('d/m/Y'))
            ->assertSee(substr((string) $dispoPassee->heure_debut, 0, 5));
    }

    public function test_aujourd_hui_creneau_fin_passee_masque_creneau_fin_future_visible(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        Carbon::setTestNow(Carbon::create(2026, 10, 22, 10, 0, 0, config('app.timezone')));
        $jour = Carbon::now()->toDateString();

        $dispoFinPassee = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'active' => true,
        ]);

        $dispoFinFuture = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '10:30',
            'heure_fin' => '11:30',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('onglet', 'principal')
            ->assertSee(substr((string) $dispoFinFuture->heure_debut, 0, 5))
            ->assertDontSee(substr((string) $dispoFinPassee->heure_debut, 0, 5));

        Carbon::setTestNow();
    }

    public function test_modifier_un_creneau_libre_et_futur_est_accepte(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->set('heure_debut', '10:00')
            ->set('heure_fin', '12:00')
            ->call('ajouter')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Disponibilité modifiée.');

        $dispo->refresh();
        $this->assertSame('10:00', substr((string) $dispo->heure_debut, 0, 5));
        $this->assertSame('12:00', substr((string) $dispo->heure_fin, 0, 5));
    }

    public function test_modifier_avec_rdv_demande_est_refuse_avec_message_exact(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->assertSet('messageErreur', 'Ce créneau est déjà pris par un patient, il ne peut pas être modifié.');
    }

    public function test_modifier_avec_rdv_valide_est_refuse_avec_message_exact(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->assertSet('messageErreur', 'Ce créneau est déjà pris par un patient, il ne peut pas être modifié.');
    }

    public function test_rdv_annule_ne_bloque_pas_la_modification(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->assertSet('messageErreur', null)
            ->set('heure_fin', '13:00')
            ->call('ajouter')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Disponibilité modifiée.');

        $dispo->refresh();
        $this->assertSame('13:00', substr((string) $dispo->heure_fin, 0, 5));
    }

    public function test_modifier_un_creneau_passe_refuse_y_compris_par_appel_direct(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jour = Carbon::now()->subDay()->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->assertSet('messageErreur', 'Impossible de modifier une disponibilité passée.');
    }

    public function test_chevauchement_avec_autre_disponibilite_active_refuse_sans_faux_conflit_avec_soi_meme(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jour = Carbon::now()->addDays(2)->toDateString();

        Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '14:00',
            'heure_fin' => '16:00',
            'active' => true,
        ]);

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->set('heure_debut', '13:00')
            ->set('heure_fin', '15:00')
            ->call('ajouter')
            ->assertSet('messageErreur', 'Une disponibilité existe déjà sur cette plage horaire.');

        // Modification sans changement ne crée pas de faux conflit
        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->call('ajouter')
            ->assertSet('messageErreur', null);
    }

    public function test_chevauchement_partiel_rdv_30_min_commence_a_1145_dans_9h12h_est_pris(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '11:45',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->assertSet('messageErreur', 'Ce créneau est déjà pris par un patient, il ne peut pas être modifié.');
    }

    public function test_desactiver_avec_rdv_actif_refuse_avec_message_exact(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('basculer', $dispo->id)
            ->assertSet('messageErreur', "Des rendez-vous sont liés à ce créneau. Annulez-les d'abord.");

        $dispo->refresh();
        $this->assertTrue($dispo->active);
    }

    public function test_desactiver_sans_rdv_actif_est_accepte(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('basculer', $dispo->id)
            ->assertSet('messageErreur', null);

        $dispo->refresh();
        $this->assertFalse($dispo->active);
    }

    public function test_rdv_cree_apres_ouverture_formulaire_est_refuse_a_l_enregistrement(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispo = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        $composant = Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('modifier', $dispo->id)
            ->assertSet('messageErreur', null)
            ->set('heure_fin', '13:00');

        RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        $composant->call('ajouter')
            ->assertSet('messageErreur', 'Ce créneau est déjà pris par un patient, il ne peut pas être modifié.');

        $dispo->refresh();
        $this->assertSame('12:00', substr((string) $dispo->heure_fin, 0, 5));
    }

    public function test_reactiver_refuse_en_cas_de_chevauchement_avec_autre_disponibilite_active(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $jour = Carbon::now()->addDays(2)->toDateString();

        $dispoActive = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'active' => true,
        ]);

        $dispoInactive = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $jour,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'active' => false,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->call('basculer', $dispoInactive->id)
            ->assertSet('messageErreur', 'Une disponibilité existe déjà sur cette plage horaire.');

        $dispoInactive->refresh();
        $this->assertFalse($dispoInactive->active);

        unset($dispoActive, $dispoInactive);
    }

    public function test_disponibilites_passees_restent_en_base_et_apparaissent_dans_historique(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $passé = Carbon::now()->subDays(3)->toDateString();

        $dispoPassee = Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'jour' => $passé,
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'active' => true,
        ]);

        $this->assertDatabaseHas('disponibilites', ['id' => $dispoPassee->id]);

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('onglet', 'principal')
            ->assertDontSee($dispoPassee->heure_debut)
            ->set('onglet', 'historique')
            ->assertSee($dispoPassee->heure_debut);

        $this->assertDatabaseHas('disponibilites', ['id' => $dispoPassee->id]);
    }
}
