<?php

namespace Tests\Feature\Medecin;

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

    protected function medecin(int $serviceId, string $name = 'Dr. Aminata Ndiaye'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ]);
        $user->assignRole('Médecin');

        return $user;
    }

    protected function patient(string $name = 'Awa Diop'): User
    {
        $user = User::factory()->create(['name' => $name, 'statut_compte' => 'Actif']);
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

    public function test_le_tableau_de_bord_medecin_affiche_les_cartes_et_les_statistiques(): void
    {
        $service = $this->service('Cardiologie');

        $response = $this->actingAs($this->medecin($service->id))
            ->get('/medecin/tableau-de-bord');

        $response->assertStatus(200)
            ->assertSee('Espace médecin')
            ->assertSee('Accès rapides')
            ->assertSee('Ma journée')
            ->assertSee('Mon planning')
            ->assertSee('Patients du jour')
            ->assertSee('Rendez-vous du jour')
            ->assertSee('Prochain rendez-vous')
            ->assertSee("Rendez-vous terminés aujourd'hui", false)
            ->assertSee(route('medecin.planning'), false)
            ->assertSee(route('medecin.patients-du-jour'), false);
    }

    public function test_le_suffixe_est_masque_et_le_titre_dr_est_utilise_pour_la_salutation(): void
    {
        $service = $this->service('Cardiologie');

        $this->travelTo(now()->setTime(18, 0));

        $response = $this->actingAs($this->medecin($service->id, 'Dr. Aminata Ndiaye (Médecin Démo)'))
            ->get('/medecin/tableau-de-bord');

        $response->assertStatus(200)
            ->assertDontSee('(Médecin Démo)')
            ->assertSee('Dr. Aminata Ndiaye')
            ->assertSee('AN')
            ->assertSee('Médecin')
            ->assertSee('Bonsoir, Dr. Ndiaye.');
    }

    public function test_sans_titre_le_prenom_est_utilise_pour_la_salutation(): void
    {
        $service = $this->service('Cardiologie');

        $this->travelTo(now()->setTime(10, 0));

        $this->actingAs($this->medecin($service->id, 'Aminata Ndiaye'))
            ->get('/medecin/tableau-de-bord')
            ->assertSee('Bonjour, Aminata.');
    }

    public function test_la_salutation_depend_de_l_heure_du_serveur(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);

        $this->travelTo(now()->setTime(17, 59));
        $this->actingAs($medecin)->get('/medecin/tableau-de-bord')->assertSee('Bonjour, Dr. Ndiaye.');

        $this->travelTo(now()->setTime(18, 0));
        $this->actingAs($medecin)->get('/medecin/tableau-de-bord')->assertSee('Bonsoir, Dr. Ndiaye.');
    }

    public function test_les_statistiques_ne_comptent_que_les_rdv_du_medecin_connecte(): void
    {
        $service = $this->service('Cardiologie');
        $medecinA = $this->medecin($service->id, 'Dr. Aminata Ndiaye');
        $medecinB = $this->medecin($service->id, 'Dr. Ibrahima Fall');
        $patient = $this->patient('Awa Diop');
        $patientB = $this->patient('Moussa Diallo');

        // Heure figée en fin de journée : tous les RDV du jour sont passés.
        $this->travelTo(now()->setTime(23, 0));

        $aujourdHui = now()->toDateString();
        $demain = now()->addDay()->toDateString();

        // Médecin connecté : 2 RDV actifs aujourd'hui (1 validé + 1 demandé) + 1 annulé (exclu).
        $this->rdv($patient->id, $medecinA->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui, '09:00');
        $this->rdv($patient->id, $medecinA->id, $service->id, RendezVous::STATUT_DEMANDE, $aujourdHui, '10:00');
        $this->rdv($patient->id, $medecinA->id, $service->id, RendezVous::STATUT_ANNULE, $aujourdHui, '11:00');

        // Autre médecin : ne doit jamais être compté.
        $this->rdv($patient->id, $medecinB->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui, '09:00');
        $this->rdv($patientB->id, $medecinB->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui, '10:00');

        // Prochain rendez-vous : demain, propre au médecin connecté.
        $this->rdv($patient->id, $medecinA->id, $service->id, RendezVous::STATUT_VALIDE, $demain, '14:30');
        $this->rdv($patientB->id, $medecinB->id, $service->id, RendezVous::STATUT_VALIDE, $demain, '13:00');

        // Terminés aujourd'hui : via le statut fiable de la file d'attente.
        $rdvTermine = $this->rdv($patient->id, $medecinA->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui, '08:00');
        $rdvTermineAutre = $this->rdv($patient->id, $medecinB->id, $service->id, RendezVous::STATUT_VALIDE, $aujourdHui, '08:30');

        FileEntree::create([
            'service_id' => $service->id,
            'rendez_vous_id' => $rdvTermine->id,
            'patient_id' => $patient->id,
            'date' => $aujourdHui,
            'statut' => FileEntree::STATUT_TERMINE,
        ]);
        FileEntree::create([
            'service_id' => $service->id,
            'rendez_vous_id' => $rdvTermineAutre->id,
            'patient_id' => $patient->id,
            'date' => $aujourdHui,
            'statut' => FileEntree::STATUT_TERMINE,
        ]);

        $response = $this->actingAs($medecinA)
            ->get('/medecin/tableau-de-bord')
            ->assertStatus(200)
            ->assertSee('Awa Diop') // prochain rendez-vous du médecin connecté
            ->assertDontSee('Moussa Diallo');

        $response->assertSee('14:30', false);

        $html = $response->getContent();

        // 2 actifs aujourd'hui (le RDV de 08:00 est déjà créé → total 3), l'annulé exclu.
        $this->assertSame('3', $this->valeurStat($html, 'Rendez-vous du jour'));
        $this->assertSame('1', $this->valeurStat($html, "Rendez-vous terminés aujourd'hui"));
    }

    public function test_prochain_rendez_vous_absent_affiche_un_etat_vide(): void
    {
        $service = $this->service('Cardiologie');

        $this->actingAs($this->medecin($service->id))
            ->get('/medecin/tableau-de-bord')
            ->assertStatus(200)
            ->assertSee('Aucun rendez-vous à venir')
            ->assertSee('Rendez-vous terminés aujourd\'hui', false);
    }

    public function test_un_non_medecin_ne_peut_pas_acceder_au_tableau_de_bord_medecin(): void
    {
        $this->actingAs($this->patient())
            ->get('/medecin/tableau-de-bord')
            ->assertStatus(403);
    }
}
