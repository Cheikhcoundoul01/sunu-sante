<?php

namespace Tests\Feature\Secretaire;

use App\Exceptions\DossierDejaTraiteException;
use App\Livewire\Secretaire\Patients;
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

    public function test_rejection_records_decision_logs_and_sends_no_email(): void
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

        Mail::assertNothingSent();

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

    public function test_second_decision_keeps_first_and_shows_already_processed(): void
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
    }

    public function test_only_one_activity_is_logged_per_decision(): void
    {
        Mail::fake();

        $secretaire = $this->secretaire();
        $patient = $this->patientEnAttente();

        app(PatientAccountService::class)->valider($patient, $secretaire);

        $this->assertDatabaseCount('activity_log', 1);
    }
}
