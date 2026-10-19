<?php

namespace Tests\Feature\Major;

use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableauDeBordTest extends TestCase
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

    protected function major(int $serviceId, string $name = 'Awa Sy'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ]);
        $user->assignRole('Major de service');

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

    protected function rdv(User $patient, int $serviceId, User $medecin, string $statut, string $date, string $heure): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $serviceId,
            'date' => $date,
            'heure_debut' => $heure,
            'duree_prevue' => 30,
            'statut' => $statut,
        ]);
    }

    protected function entreeFile(User $patient, int $serviceId, User $medecin, string $statut, string $date, int $position): FileEntree
    {
        $rdv = $this->rdv($patient, $serviceId, $medecin, RendezVous::STATUT_VALIDE, $date, '09:00');

        return FileEntree::create([
            'service_id' => $serviceId,
            'rendez_vous_id' => $rdv->id,
            'patient_id' => $patient->id,
            'date' => $date,
            'statut' => $statut,
            'position' => $position,
            'priorite' => false,
        ]);
    }

    protected function valeurStat(string $html, string $libelle): string
    {
        $pattern = '/'.preg_quote(e($libelle), '/').'<\/span>\s*<span[^<>]*>(\d+)<\/span>/u';
        preg_match($pattern, $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_le_tableau_de_bord_major_affiche_la_carte_et_les_statistiques(): void
    {
        $service = $this->service('Cardiologie');

        $response = $this->actingAs($this->major($service->id))
            ->get('/major/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee('Espace major de service')
            ->assertSee('Accès rapide')
            ->assertSee('Statistiques du service')
            ->assertSee("Aperçu du service aujourd'hui", false)
            ->assertSee('Rendez-vous du jour')
            ->assertSee("Patients en file d'attente")
            ->assertSee("Rendez-vous terminés aujourd'hui")
            ->assertSee(route('major.statistiques'), false);
    }

    public function test_le_suffixe_de_demonstration_est_masque_et_l_avatar_utilise_les_initiales(): void
    {
        $service = $this->service('Cardiologie');

        $this->travelTo(now()->setTime(10, 0));

        $response = $this->actingAs($this->major($service->id, 'Awa Sy (Major Démo)'))
            ->get('/major/tableau-de-bord');

        $response->assertStatus(200)
            ->assertDontSee('(Major Démo)')
            ->assertSee('Awa Sy')
            ->assertSee('AS')
            ->assertSee('Major de service')
            ->assertSee('Bonjour, Awa.');
    }

    public function test_la_salutation_depend_de_l_heure_du_serveur(): void
    {
        $service = $this->service('Cardiologie');
        $major = $this->major($service->id);

        $this->travelTo(now()->setTime(17, 59));
        $this->actingAs($major)->get('/major/tableau-de-bord')->assertSee('Bonjour, Awa.');

        $this->travelTo(now()->setTime(18, 0));
        $this->actingAs($major)->get('/major/tableau-de-bord')->assertSee('Bonsoir, Awa.');
    }

    public function test_les_statistiques_respectent_le_service_la_date_et_les_statuts(): void
    {
        $service = $this->service('Cardiologie');
        $autreService = $this->service('Pédiatrie');

        $major = $this->major($service->id);
        $medecin = $this->medecin($service->id);
        $medecinAutre = $this->medecin($autreService->id);
        $patient = $this->patient();

        $aujourdHui = now()->toDateString();
        $demain = now()->addDay()->toDateString();

        // Service courant, aujourd'hui : 2 demandés + 1 annulé + 4 validés (portés par la file) = 7 RDV.
        $this->rdv($patient, $service->id, $medecin, RendezVous::STATUT_DEMANDE, $aujourdHui, '08:00');
        $this->rdv($patient, $service->id, $medecin, RendezVous::STATUT_DEMANDE, $aujourdHui, '08:30');
        $this->rdv($patient, $service->id, $medecin, RendezVous::STATUT_ANNULE, $aujourdHui, '11:00');

        // File du service, aujourd'hui : 1 en attente + 1 en cours + 1 terminé + 1 absent.
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_EN_ATTENTE, $aujourdHui, 1);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_EN_COURS, $aujourdHui, 2);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_TERMINE, $aujourdHui, 3);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_ABSENT, $aujourdHui, 4);

        // Service courant, demain : ne doit pas être compté.
        $this->rdv($patient, $service->id, $medecin, RendezVous::STATUT_DEMANDE, $demain, '09:00');

        // Autre service, aujourd'hui : ne doit pas être compté.
        $this->rdv($patient, $autreService->id, $medecinAutre, RendezVous::STATUT_VALIDE, $aujourdHui, '09:00');
        $this->entreeFile($patient, $autreService->id, $medecinAutre, FileEntree::STATUT_EN_ATTENTE, $aujourdHui, 1);

        $html = $this->actingAs($major)
            ->get('/major/tableau-de-bord')
            ->assertStatus(200)
            ->getContent();

        // 7 RDV du jour (2 demandés + 1 annulé + 4 validés portés par la file).
        $this->assertSame('7', $this->valeurStat($html, 'Rendez-vous du jour'));
        // En attente + en cours, hors Terminé et Absent.
        $this->assertSame('2', $this->valeurStat($html, "Patients en file d'attente"));
        // Uniquement les entrées de file au statut « Terminé ».
        $this->assertSame('1', $this->valeurStat($html, "Rendez-vous terminés aujourd'hui"));
    }

    public function test_un_non_major_ne_peut_pas_acceder_au_tableau_de_bord(): void
    {
        $this->actingAs($this->patient())
            ->get('/major/tableau-de-bord')
            ->assertStatus(403);
    }
}
