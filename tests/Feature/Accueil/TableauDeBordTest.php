<?php

namespace Tests\Feature\Accueil;

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

    protected function agent(int $serviceId, string $name = 'Omar Diallo'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ]);
        $user->assignRole("Agent d'accueil");

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

    protected function entreeFile(User $patient, int $serviceId, User $medecin, string $statut, string $date, int $position): FileEntree
    {
        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $serviceId,
            'date' => $date,
            'heure_debut' => '09:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

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
        $pattern = '/'.preg_quote($libelle, '/').'<\/span>\s*<span[^<>]*>(\d+)<\/span>/u';
        preg_match($pattern, $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_le_tableau_de_bord_agent_affiche_la_carte_et_les_statistiques(): void
    {
        $service = $this->service('Médecine générale');

        $response = $this->actingAs($this->agent($service->id))
            ->get('/accueil-personnel/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee("Espace agent d'accueil", false)
            ->assertSee('Accès rapide')
            ->assertSee('File du jour')
            ->assertSee("File d'attente aujourd'hui", false)
            ->assertSee('En attente')
            ->assertSee('En cours')
            ->assertSee('Terminés')
            ->assertSee(route('accueil.file'), false);
    }

    public function test_le_suffixe_de_demonstration_est_masque_et_l_avatar_utilise_les_initiales(): void
    {
        $service = $this->service('Médecine générale');

        $this->travelTo(now()->setTime(10, 0));

        $response = $this->actingAs($this->agent($service->id, 'Omar Diallo (Agent Démo)'))
            ->get('/accueil-personnel/tableau-de-bord');

        $response->assertStatus(200)
            ->assertDontSee('(Agent Démo)')
            ->assertSee('Omar Diallo')
            ->assertSee('OD')
            ->assertSee("Agent d'accueil", false)
            ->assertSee('Bonjour, Omar.');
    }

    public function test_la_salutation_depend_de_l_heure_du_serveur(): void
    {
        $service = $this->service('Médecine générale');
        $agent = $this->agent($service->id);

        $this->travelTo(now()->setTime(17, 59));
        $this->actingAs($agent)->get('/accueil-personnel/tableau-de-bord')->assertSee('Bonjour, Omar.');

        $this->travelTo(now()->setTime(18, 0));
        $this->actingAs($agent)->get('/accueil-personnel/tableau-de-bord')->assertSee('Bonsoir, Omar.');
    }

    public function test_les_statistiques_respectent_le_service_la_date_et_les_statuts(): void
    {
        $service = $this->service('Médecine générale');
        $autreService = $this->service('Pédiatrie');

        $agent = $this->agent($service->id);
        $medecin = $this->medecin($service->id);
        $medecinAutre = $this->medecin($autreService->id);
        $patient = $this->patient();

        $aujourdHui = now()->toDateString();
        $demain = now()->addDay()->toDateString();

        // Service courant, aujourd'hui : 2 en attente, 1 en cours, 2 terminés, 1 absent.
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_EN_ATTENTE, $aujourdHui, 1);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_EN_ATTENTE, $aujourdHui, 2);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_EN_COURS, $aujourdHui, 3);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_TERMINE, $aujourdHui, 4);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_TERMINE, $aujourdHui, 5);
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_ABSENT, $aujourdHui, 6);

        // Service courant, demain : ne doit pas être compté.
        $this->entreeFile($patient, $service->id, $medecin, FileEntree::STATUT_EN_ATTENTE, $demain, 1);

        // Autre service, aujourd'hui : ne doit pas être compté.
        $this->entreeFile($patient, $autreService->id, $medecinAutre, FileEntree::STATUT_EN_ATTENTE, $aujourdHui, 1);

        $html = $this->actingAs($agent)
            ->get('/accueil-personnel/tableau-de-bord')
            ->assertStatus(200)
            ->getContent();

        $this->assertSame('2', $this->valeurStat($html, 'En attente'));
        $this->assertSame('1', $this->valeurStat($html, 'En cours'));
        $this->assertSame('2', $this->valeurStat($html, 'Terminés'));
    }

    public function test_un_non_agent_ne_peut_pas_acceder_au_tableau_de_bord(): void
    {
        $this->actingAs($this->patient())
            ->get('/accueil-personnel/tableau-de-bord')
            ->assertStatus(403);
    }
}
