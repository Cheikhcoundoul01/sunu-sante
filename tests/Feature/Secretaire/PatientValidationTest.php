<?php

namespace Tests\Feature\Secretaire;

use App\Exceptions\DossierDejaTraiteException;
use App\Livewire\Secretaire\Patients;
use App\Mail\ComptePatientRejete;
use App\Mail\ComptePatientValide;
use App\Models\User;
use App\Services\PatientAccountService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PatientValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function secretaire(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Secrétaire de service');

        return $user;
    }

    protected function patientEnAttente(): User
    {
        $user = User::factory()->create([
            'email' => 'patient.attente@example.com',
            'statut_compte' => 'En attente',
        ]);
        $user->assignRole('Patient');

        return $user;
    }

    public function test_secretaire_can_access_patients_screen(): void
    {
        $this->actingAs($this->secretaire())
            ->get('/secretaire/patients')
            ->assertStatus(200)
            ->assertSee('Patients');
    }

    public function test_non_secretaire_cannot_access_patients_screen(): void
    {
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $this->actingAs($patient)->get('/secretaire/patients')->assertStatus(403);

        $medecin = User::factory()->create(['statut_compte' => 'Actif']);
        $medecin->assignRole('Médecin');

        $this->actingAs($medecin)->get('/secretaire/patients')->assertStatus(403);
    }

    public function test_email_patient_affiche_uniquement_pour_les_comptes_en_attente(): void
    {
        $enAttente = $this->patientEnAttente();

        $valide = User::factory()->create([
            'email' => 'patient.valide@example.com',
            'statut_compte' => 'Actif',
        ]);
        $valide->assignRole('Patient');

        $rejete = User::factory()->create([
            'email' => 'patient.rejete@example.com',
            'statut_compte' => 'Rejeté',
        ]);
        $rejete->assignRole('Patient');

        Livewire::actingAs($this->secretaire())
            ->test(Patients::class)
            ->set('filtre', 'Tous')
            ->assertSee($enAttente->email)
            ->assertSee($valide->name)
            ->assertDontSee($valide->email)
            ->assertSee($rejete->name)
            ->assertDontSee($rejete->email);
    }

    public function test_validation_activates_account_records_decision_sends_email_and_logs(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        Livewire::actingAs($secretaire)
            ->test(Patients::class)
            ->call('valider', $patient->id);

        $patient->refresh();

        $this->assertSame('Actif', $patient->statut_compte);
        $this->assertSame($secretaire->id, $patient->traite_par_id);
        $this->assertNotNull($patient->traite_at);

        Mail::assertSent(ComptePatientValide::class, function ($mail) use ($patient) {
            return $mail->hasTo($patient->email)
                && $mail->hasSubject('Sunu Santé — Votre compte patient est validé');
        });

        $this->assertDatabaseHas('activity_log', [
            'event' => 'validation_compte_patient',
            'subject_id' => $patient->id,
            'subject_type' => User::class,
            'causer_id' => $secretaire->id,
        ]);
    }

    public function test_rejection_records_decision_logs_and_sends_email(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        Livewire::actingAs($secretaire)
            ->test(Patients::class)
            ->call('rejeter', $patient->id);

        $patient->refresh();

        $this->assertSame('Rejeté', $patient->statut_compte);
        $this->assertSame($secretaire->id, $patient->traite_par_id);

        Mail::assertSent(ComptePatientRejete::class, function ($mail) use ($patient) {
            return $mail->hasTo($patient->email)
                && $mail->hasSubject('Sunu Santé — Votre demande de compte patient');
        });

        Mail::assertNotSent(ComptePatientValide::class);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'rejet_compte_patient',
            'subject_id' => $patient->id,
            'causer_id' => $secretaire->id,
        ]);
    }

    public function test_second_decision_is_refused_as_already_processed(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        app(PatientAccountService::class)->valider($patient, $secretaire);

        $this->expectException(DossierDejaTraiteException::class);

        app(PatientAccountService::class)->rejeter($patient->fresh(), $secretaire);
    }

    public function test_second_decision_keeps_first_shows_already_processed_and_sends_no_second_email(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        Livewire::actingAs($secretaire)
            ->test(Patients::class)
            ->call('valider', $patient->id);

        Livewire::actingAs($secretaire)
            ->test(Patients::class)
            ->call('rejeter', $patient->id)
            ->assertSet('messageErreur', 'Dossier déjà traité');

        $patient->refresh();

        $this->assertSame('Actif', $patient->statut_compte);
        $this->assertDatabaseCount('activity_log', 1);

        Mail::assertSentCount(1);
        Mail::assertNotSent(ComptePatientRejete::class);
    }

    public function test_journalisation_ne_conserve_pas_les_emails(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        app(PatientAccountService::class)->valider($patient, $secretaire);

        $activite = \Spatie\Activitylog\Models\Activity::where('event', 'validation_compte_patient')->firstOrFail();
        $proprietes = $activite->properties->toArray();

        $this->assertSame(['statut' => 'Actif'], $proprietes);
        $this->assertStringNotContainsString($patient->email, json_encode($proprietes));
        $this->assertStringNotContainsString($secretaire->email, json_encode($proprietes));
    }

    public function test_only_one_activity_is_logged_per_decision(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        app(PatientAccountService::class)->valider($patient, $secretaire);

        $this->assertDatabaseCount('activity_log', 1);
    }

    public function test_rejection_email_failure_does_not_revert_decision(): void
    {
        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        Mail::shouldReceive('to->send')->once()->andThrow(new \RuntimeException('SMTP indisponible'));

        $resultat = app(PatientAccountService::class)->rejeter($patient, $secretaire);

        $this->assertSame('Rejeté', $resultat->statut_compte);

        $patient->refresh();

        $this->assertSame('Rejeté', $patient->statut_compte);
        $this->assertNotSame('En attente', $patient->statut_compte);
        $this->assertSame($secretaire->id, $patient->traite_par_id);
        $this->assertNotNull($patient->traite_at);
        $this->assertDatabaseCount('activity_log', 1);
    }
}
