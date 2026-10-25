<?php

namespace Tests\Feature\Patient;

use App\Mail\RendezVousAnnule;
use App\Mail\RendezVousModifie;
use App\Models\Disponibilite;
use App\Models\Parametre;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationTest extends TestCase
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

    public function test_patient_peut_acceder_dashboard(): void
    {
        $this->actingAs($this->patient())
            ->get('/patient/tableau-de-bord')
            ->assertStatus(200)
            ->assertSee('Nanga def');
    }

    public function test_patient_peut_acceder_prendre_rendez_vous(): void
    {
        $this->actingAs($this->patient())
            ->get('/patient/rendez-vous/prendre')
            ->assertStatus(200)
            ->assertSee('Prendre un rendez-vous');
    }

    public function test_patient_peut_acceder_mes_rendez_vous(): void
    {
        $this->actingAs($this->patient())
            ->get('/patient/rendez-vous')
            ->assertStatus(200)
            ->assertSee('Mes rendez-vous');
    }

    public function test_patient_peut_acceder_profil(): void
    {
        $this->actingAs($this->patient())
            ->get('/patient/profil')
            ->assertStatus(200)
            ->assertSee('Mon profil');
    }

    public function test_patient_peut_reserver_un_creneau_libre(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->set('motif', 'Consultation')
            ->call('reserver')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Rendez-vous demandé avec succès. Il sera validé par une secrétaire.');

        $this->assertDatabaseHas('rendez_vouses', [
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Mail::assertNothingSent();
    }

    public function test_medecin_inactif_est_absent_des_medecins_proposes(): void
    {
        $service = $this->service('Cardiologie');
        $actif = $this->medecin($service->id);
        $inactif = User::factory()->create(['statut_compte' => 'Rejeté', 'service_id' => $service->id]);
        $inactif->assignRole('Médecin');

        $component = Livewire::actingAs($this->patient())
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id);

        $medecins = $component->viewData('medecins');

        $this->assertTrue($medecins->contains(fn ($medecin) => $medecin->id === $actif->id));
        $this->assertFalse($medecins->contains(fn ($medecin) => $medecin->id === $inactif->id));
    }

    public function test_medecin_inactif_ne_peut_pas_etre_choisi_cote_serveur(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $inactif = User::factory()->create(['statut_compte' => 'Rejeté', 'service_id' => $service->id]);
        $inactif->assignRole('Médecin');
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($inactif->id, $service->id, $jour, '09:00', '12:00');

        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $inactif->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->call('reserver')
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Une erreur est survenue lors de la demande de rendez-vous. Veuillez réessayer.');

        $this->assertDatabaseMissing('rendez_vouses', [
            'patient_id' => $patient->id,
            'medecin_id' => $inactif->id,
        ]);
    }

    public function test_conflit_creneau_deja_pris(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        // Premier patient réserve
        Livewire::actingAs($patient1)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->call('reserver')
            ->assertSet('messageErreur', null);

        // Deuxième patient essaie le même créneau
        Livewire::actingAs($patient2)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->call('reserver')
            ->assertSet('messageErreur', 'Ce créneau n\'est plus disponible.');

        $this->assertDatabaseCount('rendez_vouses', 1);
    }

    public function test_patient_ne_peut_modifier_que_ses_propres_rdv(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdv = RendezVous::create([
            'patient_id' => $patient1->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($patient2)
            ->test(\App\Livewire\Patient\MesRendezVous::class)
            ->call('ouvrirModification', $rdv)
            ->assertSet('messageErreur', 'Vous n\'êtes pas autorisé à modifier ce rendez-vous.');

        // Even if we try to force it via service
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Vous n\'êtes pas autorisé à modifier ce rendez-vous.');
        app(ReservationService::class)->modifier($rdv->fresh(), $patient2, $medecin->id, $jour, '10:00');
    }

    public function test_modification_rdv_repasse_en_demande_et_envoie_email(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
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

        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\MesRendezVous::class)
            ->call('ouvrirModification', $rdv)
            ->set('editMedecinId', $medecin->id)
            ->set('editDate', $jour)
            ->set('editHeureDebut', '10:00')
            ->call('sauvegarderModification')
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Rendez-vous modifié. Il repasse en attente de validation.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->statut);
        $this->assertSame('10:00', substr($rdv->heure_debut, 0, 5));

        Mail::assertSent(RendezVousModifie::class, function ($mail) use ($patient) {
            return $mail->hasTo($patient->email)
                && $mail->hasSubject('Sunu Santé — Votre demande de rendez-vous a été modifiée');
        });
    }

    public function test_annulation_rdv_libere_creneau_et_envoie_email(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
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

        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\MesRendezVous::class)
            ->call('annuler', $rdv)
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Rendez-vous annulé. Le créneau est libéré.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_ANNULE, $rdv->statut);

        Mail::assertSent(RendezVousAnnule::class, function ($mail) use ($patient) {
            return $mail->hasTo($patient->email)
                && $mail->hasSubject('Sunu Santé — Votre rendez-vous a été annulé');
        });
    }

    public function test_creneau_annule_peut_etre_reserve_a_nouveau(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient1 = $this->patient();
        $patient2 = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        // Patient 1 réserve
        Livewire::actingAs($patient1)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->call('reserver')
            ->assertSet('messageErreur', null);

        // Patient 1 annule
        $rdv = RendezVous::where('patient_id', $patient1->id)->first();
        Livewire::actingAs($patient1)
            ->test(\App\Livewire\Patient\MesRendezVous::class)
            ->call('annuler', $rdv)
            ->assertSet('messageErreur', null);

        // Patient 2 peut maintenant réserver le même créneau
        Livewire::actingAs($patient2)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->call('reserver')
            ->assertSet('messageErreur', null);

        $this->assertDatabaseCount('rendez_vouses', 2);
        $this->assertDatabaseHas('rendez_vouses', ['statut' => RendezVous::STATUT_ANNULE]);
    }

    public function test_journalisation_creation_modification_annulation(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        // Création
        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jour)
            ->set('heureDebut', '09:30')
            ->call('reserver');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'creation_rendez_vous',
            'causer_id' => $patient->id,
        ]);

        $rdv = RendezVous::where('patient_id', $patient->id)->first();

        // Modification
        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\MesRendezVous::class)
            ->call('ouvrirModification', $rdv)
            ->set('editMedecinId', $medecin->id)
            ->set('editDate', $jour)
            ->set('editHeureDebut', '10:00')
            ->call('sauvegarderModification');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'modification_rendez_vous',
            'causer_id' => $patient->id,
            'subject_id' => $rdv->id,
        ]);

        // Annulation
        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\MesRendezVous::class)
            ->call('annuler', $rdv->fresh());

        $this->assertDatabaseHas('activity_log', [
            'event' => 'annulation_rendez_vous',
            'causer_id' => $patient->id,
            'subject_id' => $rdv->id,
        ]);
    }

    public function test_horizon_reservation_respecte(): void
    {
        Parametre::create(['cle' => Parametre::CLE_HORIZON_RESERVATION, 'valeur' => '30']);

        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jourHorsHorizon = Carbon::now()->addDays(31)->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jourHorsHorizon, '09:00', '12:00');

        Livewire::actingAs($patient)
            ->test(\App\Livewire\Patient\PrendreRendezVous::class)
            ->set('serviceId', $service->id)
            ->set('medecinId', $medecin->id)
            ->set('date', $jourHorsHorizon)
            ->set('heureDebut', '09:30')
            ->call('reserver')
            ->assertSet('messageErreur', "Cette date dépasse l'horizon de réservation.");
    }
}