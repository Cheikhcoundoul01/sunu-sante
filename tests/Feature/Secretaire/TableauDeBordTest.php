<?php

namespace Tests\Feature\Secretaire;

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

    protected function secretaire(int $serviceId, string $name = 'Fatou Ndiaye'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ]);
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

    protected function rdv(int $patientId, int $medecinId, int $serviceId, string $statut, string $date, string $heure): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $patientId,
            'medecin_id' => $medecinId,
            'service_id' => $serviceId,
            'date' => $date,
            'heure_debut' => $heure,
            'duree_prevue' => 30,
            'statut' => $statut,
        ]);
    }

    protected function valeurStat(string $html, string $libelle): string
    {
        $pattern = '/'.preg_quote($libelle, '/').'<\/span>\s*<span[^<>]*>(\d+)<\/span>/u';
        preg_match($pattern, $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_le_tableau_de_bord_secrétaire_affiche_les_cartes_et_les_statistiques(): void
    {
        $service = $this->service('Cardiologie');

        $response = $this->actingAs($this->secretaire($service->id))
            ->get('/secretaire/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee('Espace secrétaire')
            ->assertSee('Accès rapides')
            ->assertSee('Utile pour la secrétaire')
            ->assertSee('Patients')
            ->assertSee('Rendez-vous')
            ->assertSee('Disponibilités')
            ->assertSee('Salles')
            ->assertSee('Patients à valider')
            ->assertSee('Rendez-vous du jour')
            ->assertSee('Rendez-vous en attente de validation')
            ->assertSee(route('secretaire.patients'), false)
            ->assertSee(route('secretaire.rendez-vous'), false)
            ->assertSee(route('secretaire.disponibilites'), false)
            ->assertSee(route('secretaire.salles'), false);
    }

    public function test_le_suffixe_de_demonstration_est_masque_et_l_avatar_utilise_les_initiales(): void
    {
        $service = $this->service('Cardiologie');

        $this->travelTo(now()->setTime(10, 0));

        $response = $this->actingAs($this->secretaire($service->id, 'Fatou Ndiaye (Secrétaire Démo)'))
            ->get('/secretaire/tableau-de-bord');

        $response->assertStatus(200)
            ->assertDontSee('(Secrétaire Démo)')
            ->assertSee('Fatou Ndiaye')
            ->assertSee('FN')
            ->assertSee('Secrétaire')
            ->assertSee('Bonjour, Fatou');
    }

    public function test_la_salutation_depend_de_l_heure_du_serveur(): void
    {
        $service = $this->service('Cardiologie');
        $secretaire = $this->secretaire($service->id);

        $this->travelTo(now()->setTime(17, 59));
        $this->actingAs($secretaire)->get('/secretaire/tableau-de-bord')->assertSee('Bonjour, Fatou');

        $this->travelTo(now()->setTime(18, 0));
        $this->actingAs($secretaire)->get('/secretaire/tableau-de-bord')->assertSee('Bonsoir, Fatou');
    }

    public function test_les_statistiques_respectent_le_service_et_la_date(): void
    {
        $service = $this->service('Cardiologie');
        $autreService = $this->service('Pédiatrie');

        $secretaire = $this->secretaire($service->id);
        $medecin = $this->medecin($service->id);
        $medecinAutre = $this->medecin($autreService->id);
        $patient = $this->patient();

        User::factory()->count(2)->create(['statut_compte' => 'En attente'])
            ->each(fn (User $u) => $u->assignRole('Patient'));

        $aujourdHui = now()->toDateString();
        $demain = now()->addDay()->toDateString();

        // Service courant, aujourd'hui : 2 demandés + 1 validé.
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_DEMANDE, $aujourdHui, '09:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_DEMANDE, $aujourdHui, '09:30');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui, '10:00');

        // Service courant, demain : 1 demandé (compte dans « en attente », pas « du jour »).
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_DEMANDE, $demain, '09:00');

        // Autre service, aujourd'hui : ne doit pas être compté.
        $this->rdv($patient->id, $medecinAutre->id, $autreService->id, RendezVous::STATUT_DEMANDE, $aujourdHui, '09:00');

        $html = $this->actingAs($secretaire)
            ->get('/secretaire/tableau-de-bord')
            ->assertStatus(200)
            ->getContent();

        $this->assertSame('2', $this->valeurStat($html, 'Patients à valider'));
        $this->assertSame('3', $this->valeurStat($html, 'Rendez-vous du jour'));
        $this->assertSame('3', $this->valeurStat($html, 'Rendez-vous en attente de validation'));
    }

    public function test_un_non_secretaire_ne_peut_pas_acceder_au_tableau_de_bord_secrétaire(): void
    {
        $this->actingAs($this->patient())
            ->get('/secretaire/tableau-de-bord')
            ->assertStatus(403);
    }
}
