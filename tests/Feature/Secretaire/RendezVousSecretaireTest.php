<?php

namespace Tests\Feature\Secretaire;

use App\Mail\RendezVousAnnule;
use App\Mail\RendezVousValide;
use App\Models\Disponibilite;
use App\Models\Parametre;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RendezVousSecretaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Événement du modèle RendezVous sur lequel les tests d'échec métier
     * enregistrent un écouteur temporaire.
     */
    private function cleEvenementMiseAJourRendezVous(): string
    {
        return 'eloquent.updating: ' . RendezVous::class;
    }

    /**
     * Enregistre un écouteur temporaire qui fait échouer la mise à jour du
     * rendez-vous, exécute le test, puis retire uniquement cet écouteur.
     *
     * Le retrait est ciblé sur la seule clé d'événement utilisée : aucun autre
     * écouteur ni wildcard n'est touché, et le nettoyage a lieu même si une
     * assertion échoue (grâce au `finally`).
     */
    private function simulerEchecMiseAJour(callable $test): void
    {
        $cle = $this->cleEvenementMiseAJourRendezVous();
        $dispatcher = RendezVous::getEventDispatcher();

        $this->assertCount(
            0,
            $dispatcher->getListeners($cle),
            'Un écouteur préexistant a été détecté sur ' . $cle . ' : nettoyage ciblé impossible sans le supprimer.'
        );

        RendezVous::updating(function () {
            throw new \RuntimeException('Échec de la mise à jour du statut');
        });

        try {
            $test();
        } finally {
            $dispatcher->forget($cle);
        }
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

    public function test_secretaire_peut_acceder_ecran_rendez_vous(): void
    {
        $service = $this->service('Cardiologie');
        $this->medecin($service->id);

        $this->actingAs($this->secretaire($service->id))
            ->get('/secretaire/rendez-vous')
            ->assertStatus(200)
            ->assertSee('Rendez-vous');
    }

    public function test_non_secretaire_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = $this->patient();
        $this->actingAs($patient)->get('/secretaire/rendez-vous')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/secretaire/rendez-vous')->assertStatus(403);
    }

    public function test_secretaire_voit_uniquement_rdv_de_son_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        $rdvA = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        $rdvB = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        $component = Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class);

        // Vérifier que le composant rend au moins un RDV du bon service
        $this->assertNotEmpty($component->viewData('rdvs'));
        $rdvs = $component->viewData('rdvs');
        $this->assertTrue($rdvs->contains(function ($rdv) use ($rdvA) {
            return $rdv->id === $rdvA->id;
        }));
        $this->assertFalse($rdvs->contains(function ($rdv) use ($rdvB) {
            return $rdv->id === $rdvB->id;
        }));
    }

    public function test_secretaire_peut_valider_rdv_demande(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
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
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Rendez-vous validé. Email de confirmation envoyé au patient.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_VALIDE, $rdv->statut);

        Mail::assertSent(RendezVousValide::class, function ($mail) use ($patient) {
            return $mail->hasTo($patient->email)
                && $mail->hasSubject('Sunu Santé — Votre rendez-vous a été confirmé');
        });

        $this->assertDatabaseHas('activity_log', [
            'event' => 'validation_rendez_vous',
            'causer_id' => $secretaire->id,
            'subject_id' => $rdv->id,
        ]);
    }

    public function test_secretaire_ne_peut_pas_valider_rdv_deja_valide(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
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

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertSet('messageErreur', 'Seuls les rendez-vous au statut « Demandé » peuvent être validés.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_VALIDE, $rdv->statut);
    }

    public function test_secretaire_ne_peut_pas_valider_rdv_annule(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
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
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertSet('messageErreur', 'Seuls les rendez-vous au statut « Demandé » peuvent être validés.');
    }

    public function test_secretaire_peut_rejeter_rdv_demande(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
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
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('rejeter', $rdv->id)
            ->assertSet('messageErreur', null)
            ->assertSet('messageSucces', 'Demande de rendez-vous rejetée. Le créneau est libéré. Email envoyé au patient.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_ANNULE, $rdv->statut);

        Mail::assertSent(RendezVousAnnule::class, function ($mail) use ($patient) {
            return $mail->hasTo($patient->email)
                && $mail->hasSubject('Sunu Santé — Votre rendez-vous a été annulé');
        });

        $this->assertDatabaseHas('activity_log', [
            'event' => 'rejet_rendez_vous',
            'causer_id' => $secretaire->id,
            'subject_id' => $rdv->id,
        ]);
    }

    public function test_secretaire_ne_peut_pas_rejeter_rdv_valide(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
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

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('rejeter', $rdv->id)
            ->assertSet('messageErreur', 'Seuls les rendez-vous au statut « Demandé » peuvent être rejetés.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_VALIDE, $rdv->statut);
    }

    public function test_secretaire_ne_peut_pas_traiter_rdv_autre_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $secretaire = $this->secretaire($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertSet('messageErreur', 'Rendez-vous introuvable dans votre service.');
    }

    public function test_filtre_statut_fonctionne(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $rdvDemande = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        $rdvValide = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $component = Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->set('filtreStatut', 'Demandé');

        $rdvs = $component->viewData('rdvs');
        $this->assertTrue($rdvs->contains(function ($rdv) use ($rdvDemande) {
            return $rdv->id === $rdvDemande->id;
        }));
        $this->assertFalse($rdvs->contains(function ($rdv) use ($rdvValide) {
            return $rdv->id === $rdvValide->id;
        }));

        $component = Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->set('filtreStatut', 'Validé');

        $rdvs = $component->viewData('rdvs');
        $this->assertTrue($rdvs->contains(function ($rdv) use ($rdvValide) {
            return $rdv->id === $rdvValide->id;
        }));
        $this->assertFalse($rdvs->contains(function ($rdv) use ($rdvDemande) {
            return $rdv->id === $rdvDemande->id;
        }));
    }

    public function test_recherche_patient_fonctionne(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);

        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        $patient1 = User::factory()->create(['name' => 'Awa Diop', 'statut_compte' => 'Actif']);
        $patient1->assignRole('Patient');

        $patient2 = User::factory()->create(['name' => 'Moussa Diallo', 'statut_compte' => 'Actif']);
        $patient2->assignRole('Patient');

        RendezVous::create([
            'patient_id' => $patient1->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        RendezVous::create([
            'patient_id' => $patient2->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->set('search', 'Awa')
            ->assertSee('Awa Diop')
            ->assertDontSee('Moussa Diallo');
    }

    protected function rdvDemande(User $patient, User $medecin, Service $service): RendezVous
    {
        $jour = Carbon::now()->addDay()->toDateString();
        $this->disponibilite($medecin->id, $service->id, $jour, '09:00', '12:00');

        return RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);
    }

    public function test_echec_envoi_email_validation_conserve_la_decision_et_message_precis(): void
    {
        Mail::shouldReceive('to->send')->once()->andThrow(new \RuntimeException('SMTP indisponible'));

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = $this->rdvDemande($patient, $medecin, $service);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Rendez-vous validé, mais l\'envoi de l\'email de confirmation a échoué.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_VALIDE, $rdv->statut);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'validation_rendez_vous',
            'causer_id' => $secretaire->id,
            'subject_id' => $rdv->id,
        ]);
    }

    public function test_echec_envoi_email_rejet_conserve_la_decision_et_message_precis(): void
    {
        Mail::shouldReceive('to->send')->once()->andThrow(new \RuntimeException('SMTP indisponible'));

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = $this->rdvDemande($patient, $medecin, $service);

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('rejeter', $rdv->id)
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Demande rejetée et créneau libéré, mais l\'envoi de l\'email au patient a échoué.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_ANNULE, $rdv->statut);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'rejet_rendez_vous',
            'causer_id' => $secretaire->id,
            'subject_id' => $rdv->id,
        ]);
    }

    public function test_echec_metier_validation_affiche_message_generique_sans_email(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = $this->rdvDemande($patient, $medecin, $service);

        $this->simulerEchecMiseAJour(function () use ($secretaire, $rdv) {
            Livewire::actingAs($secretaire)
                ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
                ->call('valider', $rdv->id)
                ->assertSet('messageSucces', null)
                ->assertSet('messageErreur', 'Une erreur inattendue est survenue lors de la validation du rendez-vous.');

            $rdv->refresh();
            $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->statut);

            Mail::assertNothingSent();
            $this->assertDatabaseMissing('activity_log', [
                'event' => 'validation_rendez_vous',
                'subject_id' => $rdv->id,
            ]);
        });
    }

    public function test_echec_metier_rejet_affiche_message_generique_sans_email(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = $this->rdvDemande($patient, $medecin, $service);

        $this->simulerEchecMiseAJour(function () use ($secretaire, $rdv) {
            Livewire::actingAs($secretaire)
                ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
                ->call('rejeter', $rdv->id)
                ->assertSet('messageSucces', null)
                ->assertSet('messageErreur', 'Une erreur inattendue est survenue lors du rejet de la demande de rendez-vous.');

            $rdv->refresh();
            $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->statut);

            Mail::assertNothingSent();
            $this->assertDatabaseMissing('activity_log', [
                'event' => 'rejet_rendez_vous',
                'subject_id' => $rdv->id,
            ]);
        });
    }

    protected function forcerEchecJournalisation(): void
    {
        $logger = \Mockery::mock(\Spatie\Activitylog\ActivityLogger::class, [
            $this->app->make(\Illuminate\Contracts\Config\Repository::class),
            $this->app->make(\Spatie\Activitylog\ActivityLogStatus::class),
            $this->app->make(\Spatie\Activitylog\LogBatch::class),
            $this->app->make(\Spatie\Activitylog\CauserResolver::class),
        ]);
        $logger->shouldReceive('setLogStatus')->andReturnSelf();
        $logger->shouldReceive('useLog')->andReturnSelf();
        $logger->shouldReceive('causedBy')->andReturnSelf();
        $logger->shouldReceive('performedOn')->andReturnSelf();
        $logger->shouldReceive('event')->andReturnSelf();
        $logger->shouldReceive('log')->andThrow(new \RuntimeException('Échec de la journalisation'));

        $pending = \Mockery::mock(\Spatie\Activitylog\PendingActivityLog::class);
        $pending->shouldReceive('logger')->andReturn($logger);
        $pending->shouldReceive('useLog')->andReturnSelf();

        $this->app->instance(\Spatie\Activitylog\PendingActivityLog::class, $pending);
    }

    public function test_echec_journalisation_validation_annule_le_changement_de_statut(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = $this->rdvDemande($patient, $medecin, $service);

        $this->forcerEchecJournalisation();

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Une erreur inattendue est survenue lors de la validation du rendez-vous.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->statut);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('activity_log', [
            'event' => 'validation_rendez_vous',
            'subject_id' => $rdv->id,
        ]);
    }

    public function test_echec_journalisation_rejet_annule_le_changement_de_statut(): void
    {
        Mail::fake();

        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = $this->rdvDemande($patient, $medecin, $service);

        $this->forcerEchecJournalisation();

        Livewire::actingAs($secretaire)
            ->test(\App\Livewire\Secretaire\RendezVousSecretaire::class)
            ->call('rejeter', $rdv->id)
            ->assertSet('messageSucces', null)
            ->assertSet('messageErreur', 'Une erreur inattendue est survenue lors du rejet de la demande de rendez-vous.');

        $rdv->refresh();
        $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->statut);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('activity_log', [
            'event' => 'rejet_rendez_vous',
            'subject_id' => $rdv->id,
        ]);
    }
}
