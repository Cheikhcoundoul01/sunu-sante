<?php

namespace Tests\Feature\Mail;

use App\Mail\ComptePatientRejete;
use App\Mail\ComptePatientValide;
use App\Mail\RendezVousAnnule;
use App\Mail\RendezVousModifie;
use App\Mail\RendezVousValide;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailRenderingTest extends TestCase
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
        $user = User::factory()->create(['name' => 'Aminata Diallo', 'statut_compte' => 'Actif']);
        $user->assignRole('Patient');

        return $user;
    }

    protected function rendezVous(): array
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => '2026-10-20',
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        return [$rdv, $medecin, $patient];
    }

    protected function assertHtmlCommon(string $html): void
    {
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('lang="fr"', $html);
        $this->assertStringContainsString('src="data:image/png;base64,', $html);
        $this->assertStringContainsString('alt="Sunu Santé"', $html);
        $this->assertStringContainsString('#115E59', $html);
        $this->assertStringContainsString('#F0FDFA', $html);
        $this->assertStringContainsString('Merci de votre confiance.', $html);
        $this->assertStringContainsString('Hôpital Fann, Dakar', $html);
    }

    protected function assertTextCommon(string $text): void
    {
        $this->assertStringNotContainsString('<', $text);
        $this->assertStringContainsString('Sunu Santé', $text);
        $this->assertStringContainsString('Merci de votre confiance.', $text);
        $this->assertStringContainsString('Hôpital Fann, Dakar', $text);
    }

    public function test_logo_embed_uses_cid_reference_for_sending(): void
    {
        $message = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email());

        $cid = $message->embed(public_path('images/logo-sunu-sante.png'));

        $this->assertStringStartsWith('cid:', $cid);
        $this->assertFileExists(public_path('images/logo-sunu-sante.png'));
    }

    public function test_compte_patient_valide_html_and_text_rendering(): void
    {
        $patient = $this->patient();

        $mailable = new ComptePatientValide($patient);
        $html = $mailable->render();
        $this->assertHtmlCommon($html);
        $this->assertStringContainsString('#0F766E', $html);
        $this->assertStringContainsString('Votre compte patient est validé', $html);
        $this->assertStringContainsString($patient->name, $html);

        $this->assertSame('mail.compte-patient-valide-text', $mailable->textView);
        $text = view($mailable->textView, $mailable->buildViewData())->render();
        $this->assertTextCommon($text);
        $this->assertStringContainsString($patient->name, $text);

        $this->assertSame('Sunu Santé — Votre compte patient est validé', $mailable->envelope()->subject);
    }

    public function test_compte_patient_rejete_html_and_text_rendering(): void
    {
        $patient = $this->patient();

        $mailable = new ComptePatientRejete($patient);
        $html = $mailable->render();
        $this->assertHtmlCommon($html);
        $this->assertStringContainsString('#B45309', $html);
        $this->assertStringContainsString('Votre demande de compte patient', $html);
        $this->assertStringContainsString("n'a pas été acceptée", $html);
        $this->assertStringContainsString($patient->name, $html);

        $this->assertSame('mail.compte-patient-rejete-text', $mailable->textView);
        $text = view($mailable->textView, $mailable->buildViewData())->render();
        $this->assertTextCommon($text);
        $this->assertStringContainsString("n'a pas été acceptée", $text);
        $this->assertStringContainsString($patient->name, $text);

        $this->assertSame('Sunu Santé — Votre demande de compte patient', $mailable->envelope()->subject);
    }

    public function test_rendez_vous_valide_html_and_text_rendering(): void
    {
        [$rdv, $medecin, $patient] = $this->rendezVous();

        $mailable = new RendezVousValide($rdv);
        $html = $mailable->render();
        $this->assertHtmlCommon($html);
        $this->assertStringContainsString('#0F766E', $html);
        $this->assertStringContainsString('Votre rendez-vous a été confirmé', $html);
        $this->assertStringContainsString($patient->name, $html);
        $this->assertStringContainsString($medecin->name, $html);
        $this->assertStringContainsString('20/10/2026', $html);
        $this->assertStringContainsString('09:30', $html);

        $this->assertSame('mail.rendez-vous-valide-text', $mailable->textView);
        $text = view($mailable->textView, $mailable->buildViewData())->render();
        $this->assertTextCommon($text);
        $this->assertStringContainsString($patient->name, $text);
        $this->assertStringContainsString($medecin->name, $text);
        $this->assertStringContainsString('20/10/2026', $text);
        $this->assertStringContainsString('09:30', $text);

        $this->assertSame('Sunu Santé — Votre rendez-vous a été confirmé', $mailable->envelope()->subject);
    }

    public function test_rendez_vous_modifie_html_and_text_rendering(): void
    {
        [$rdv, $medecin, $patient] = $this->rendezVous();

        $mailable = new RendezVousModifie($rdv);
        $html = $mailable->render();
        $this->assertHtmlCommon($html);
        $this->assertStringContainsString('#0F766E', $html);
        $this->assertStringContainsString('Votre demande de rendez-vous a été modifiée', $html);
        $this->assertStringContainsString($patient->name, $html);
        $this->assertStringContainsString($medecin->name, $html);
        $this->assertStringContainsString('20/10/2026', $html);
        $this->assertStringContainsString('09:30', $html);

        $this->assertSame('mail.rendez-vous-modifie-text', $mailable->textView);
        $text = view($mailable->textView, $mailable->buildViewData())->render();
        $this->assertTextCommon($text);
        $this->assertStringContainsString($patient->name, $text);
        $this->assertStringContainsString($medecin->name, $text);

        $this->assertSame('Sunu Santé — Votre demande de rendez-vous a été modifiée', $mailable->envelope()->subject);
    }

    public function test_rendez_vous_annule_html_and_text_rendering(): void
    {
        [$rdv, $medecin, $patient] = $this->rendezVous();

        $mailable = new RendezVousAnnule($rdv);
        $html = $mailable->render();
        $this->assertHtmlCommon($html);
        $this->assertStringContainsString('#B45309', $html);
        $this->assertStringContainsString('Votre rendez-vous a été annulé', $html);
        $this->assertStringContainsString($patient->name, $html);
        $this->assertStringContainsString($medecin->name, $html);
        $this->assertStringContainsString('20/10/2026', $html);
        $this->assertStringContainsString('09:30', $html);

        $this->assertSame('mail.rendez-vous-annule-text', $mailable->textView);
        $text = view($mailable->textView, $mailable->buildViewData())->render();
        $this->assertTextCommon($text);
        $this->assertStringContainsString($patient->name, $text);
        $this->assertStringContainsString($medecin->name, $text);

        $this->assertSame('Sunu Santé — Votre rendez-vous a été annulé', $mailable->envelope()->subject);
    }
}
