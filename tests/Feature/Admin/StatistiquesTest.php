<?php

namespace Tests\Feature\Admin;

use App\Models\Disponibilite;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use App\Services\FileAttenteService;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatistiquesTest extends TestCase
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

    protected function admin(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Administrateur');
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

    protected function rdv(
        int $patientId,
        int $medecinId,
        int $serviceId,
        string $statut,
        string $date,
        string $heure
    ): RendezVous {
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

    protected function entreeFile(
        int $serviceId,
        int $rdvId,
        int $patientId,
        string $date,
        string $statut,
        int $position = 1
    ): FileEntree {
        return FileEntree::create([
            'service_id' => $serviceId,
            'rendez_vous_id' => $rdvId,
            'patient_id' => $patientId,
            'date' => $date,
            'statut' => $statut,
            'position' => $position,
        ]);
    }

    public function test_admin_peut_acceder_statistiques(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/statistiques')
            ->assertStatus(200)
            ->assertSee('Statistiques globales');
    }

    public function test_non_admin_ne_peut_pas_acceder(): void
    {
        $service = $this->service('Cardiologie');

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get('/admin/statistiques')->assertStatus(403);

        $medecin = $this->medecin($service->id);
        $this->actingAs($medecin)->get('/admin/statistiques')->assertStatus(403);

        $secretaire = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $secretaire->assignRole('Secrétaire de service');
        $this->actingAs($secretaire)->get('/admin/statistiques')->assertStatus(403);
    }

    public function test_statistiques_globales_calculent_correctement(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);
        $patient = $this->patient();

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        // Service A: 2 valides, 1 demande
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);

        // Service B: 1 annulé
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertSame(4, $stats['global']['rdv']['total']);
        $this->assertSame(2, $stats['global']['rdv']['valides']);
        $this->assertSame(1, $stats['global']['rdv']['demandes']);
        $this->assertSame(1, $stats['global']['rdv']['annules']);
        $this->assertSame(0, $stats['global']['rdv']['interventions']);

        // Taux validation = valides / (valides + annules) = 2/3 = 66.7%
        $this->assertSame(66.7, $stats['global']['rdv']['taux_validation_pct']);
    }

    public function test_top_services_par_volume(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        // Service A: 3 RDV
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '10:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        // Service B: 1 RDV
        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertCount(2, $stats['top_services']);
        $this->assertSame('Cardiologie', $stats['top_services'][0]['nom']);
        $this->assertSame(3, $stats['top_services'][0]['rdv']);
        $this->assertSame('Pédiatrie', $stats['top_services'][1]['nom']);
        $this->assertSame(1, $stats['top_services'][1]['rdv']);
    }

    public function test_detail_par_service(): void
    {
        $serviceA = $this->service('Cardiologie');
        $serviceB = $this->service('Pédiatrie');
        $medecinA = $this->medecin($serviceA->id);
        $medecinB = $this->medecin($serviceB->id);

        $jour = now()->toDateString();
        $this->disponibilite($medecinA->id, $serviceA->id, $jour, '09:00', '12:00');
        $this->disponibilite($medecinB->id, $serviceB->id, $jour, '09:00', '12:00');

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinA->id,
            'service_id' => $serviceA->id,
            'date' => $jour,
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        RendezVous::create([
            'patient_id' => $this->patient()->id,
            'medecin_id' => $medecinB->id,
            'service_id' => $serviceB->id,
            'date' => $jour,
            'heure_debut' => '10:00',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_ANNULE,
        ]);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertArrayHasKey('Cardiologie', $stats['par_service']);
        $this->assertArrayHasKey('Pédiatrie', $stats['par_service']);
        $this->assertSame(1, $stats['par_service']['Cardiologie']['rdv']['total']);
        $this->assertSame(1, $stats['par_service']['Cardiologie']['rdv']['valides']);
        $this->assertSame(0, $stats['par_service']['Cardiologie']['rdv']['annules']);
        $this->assertSame(1, $stats['par_service']['Pédiatrie']['rdv']['total']);
        $this->assertSame(0, $stats['par_service']['Pédiatrie']['rdv']['valides']);
        $this->assertSame(1, $stats['par_service']['Pédiatrie']['rdv']['annules']);
    }

    public function test_statistiques_de_file_avec_repartition_par_statut(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();
        $jour = '2026-10-21';

        $statuts = [
            FileEntree::STATUT_EN_ATTENTE,
            FileEntree::STATUT_EN_COURS,
            FileEntree::STATUT_TERMINE,
            FileEntree::STATUT_ABSENT,
        ];

        foreach ($statuts as $index => $statut) {
            $rdv = $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, $jour, sprintf('08:%02d', $index * 10));

            $this->entreeFile($service->id, $rdv->id, $patient->id, $jour, $statut, $index + 1);
        }

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse($jour));

        $this->assertSame(4, $stats['global']['file']['total']);
        $this->assertSame(1, $stats['global']['file']['en_attente']);
        $this->assertSame(1, $stats['global']['file']['en_cours']);
        $this->assertSame(1, $stats['global']['file']['termine']);
        $this->assertSame(1, $stats['global']['file']['absent']);

        // Le détail par service respecte la même répartition.
        $this->assertSame(4, $stats['par_service']['Cardiologie']['file']['total']);
        $this->assertSame(1, $stats['par_service']['Cardiologie']['file']['en_attente']);
        $this->assertSame(1, $stats['par_service']['Cardiologie']['file']['absent']);
    }

    public function test_jour_sans_donnee_renvoie_tous_les_compteurs_a_zero(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        // Données présentes mais un autre jour.
        $rdv = $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-20', '09:00');
        $this->entreeFile($service->id, $rdv->id, $patient->id, '2026-10-20', FileEntree::STATUT_EN_ATTENTE);

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-21'));

        $this->assertSame(0, $stats['global']['rdv']['total']);
        $this->assertSame(0, $stats['global']['rdv']['valides']);
        $this->assertSame(0, $stats['global']['rdv']['demandes']);
        $this->assertSame(0, $stats['global']['rdv']['annules']);
        $this->assertSame(0, $stats['global']['rdv']['interventions']);
        $this->assertSame(0, $stats['global']['rdv']['taux_validation_pct']);

        $this->assertSame(0, $stats['global']['file']['total']);
        $this->assertSame(0, $stats['global']['file']['en_attente']);
        $this->assertSame(0, $stats['global']['file']['en_cours']);
        $this->assertSame(0, $stats['global']['file']['termine']);
        $this->assertSame(0, $stats['global']['file']['absent']);
    }

    public function test_le_changement_de_date_recalcule_les_statistiques(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-20', '09:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-22', '09:00');

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Statistiques::class)
            ->set('date', '2026-10-20')
            ->assertViewHas('stats', fn (array $stats) => $stats['global']['rdv']['total'] === 1)
            ->set('date', '2026-10-22')
            ->assertViewHas('stats', fn (array $stats) => $stats['global']['rdv']['total'] === 1)
            ->set('date', '2026-10-23')
            ->assertViewHas('stats', fn (array $stats) => $stats['global']['rdv']['total'] === 0);
    }

    public function test_periode_semaine_compte_seulement_les_rdv_de_la_semaine(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        // Référence : mercredi 21/10/2026 -> semaine du 19/10 au 25/10.
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-19', '09:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-25', '10:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-18', '11:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-26', '12:00');

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-21'), 'semaine');

        $this->assertSame('semaine', $stats['periode']['type']);
        $this->assertSame('2026-10-19', $stats['periode']['debut']);
        $this->assertSame('2026-10-25', $stats['periode']['fin']);
        $this->assertSame(2, $stats['global']['rdv']['total']);
        $this->assertSame(2, $stats['par_service']['Cardiologie']['rdv']['total']);
    }

    public function test_periode_mois_compte_seulement_les_rdv_du_mois(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        // Référence : 21/10/2026 -> mois du 01/10 au 31/10.
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-01', '09:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-31', '10:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-09-30', '11:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-11-01', '12:00');

        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-21'), 'mois');

        $this->assertSame('mois', $stats['periode']['type']);
        $this->assertSame('2026-10-01', $stats['periode']['debut']);
        $this->assertSame('2026-10-31', $stats['periode']['fin']);
        $this->assertSame(2, $stats['global']['rdv']['total']);
        $this->assertSame(2, $stats['par_service']['Cardiologie']['rdv']['total']);
    }

    public function test_limites_de_la_semaine_lundi_et_dimanche(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $rdvLundi = $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-19', '09:00');
        $rdvDimanche = $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-25', '10:00');

        // Référence = lundi 19/10 : la semaine couvre bien le dimanche 25/10.
        $statsLundi = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-19'), 'semaine');
        $this->assertSame('2026-10-19', $statsLundi['periode']['debut']);
        $this->assertSame('2026-10-25', $statsLundi['periode']['fin']);
        $this->assertSame(2, $statsLundi['global']['rdv']['total']);

        // Référence = dimanche 25/10 : la semaine couvre bien le lundi 19/10.
        $statsDimanche = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-25'), 'semaine');
        $this->assertSame('2026-10-19', $statsDimanche['periode']['debut']);
        $this->assertSame('2026-10-25', $statsDimanche['periode']['fin']);
        $this->assertSame(2, $statsDimanche['global']['rdv']['total']);

        // Le jour seul ne compte que la date de référence.
        $statsJour = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-19'), 'jour');
        $this->assertSame(1, $statsJour['global']['rdv']['total']);

        unset($rdvLundi, $rdvDimanche);
    }

    public function test_limites_du_mois_premier_et_dernier_jour(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-01', '09:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-31', '10:00');

        // Référence = 1er du mois.
        $statsDebut = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-01'), 'mois');
        $this->assertSame('2026-10-01', $statsDebut['periode']['debut']);
        $this->assertSame('2026-10-31', $statsDebut['periode']['fin']);
        $this->assertSame(2, $statsDebut['global']['rdv']['total']);

        // Référence = dernier jour du mois.
        $statsFin = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-31'), 'mois');
        $this->assertSame('2026-10-01', $statsFin['periode']['debut']);
        $this->assertSame('2026-10-31', $statsFin['periode']['fin']);
        $this->assertSame(2, $statsFin['global']['rdv']['total']);
    }

    public function test_periode_invalide_retombe_sur_jour(): void
    {
        $service = $this->service('Cardiologie');
        $medecin = $this->medecin($service->id);
        $patient = $this->patient();

        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-21', '09:00');
        $this->rdv($patient->id, $medecin->id, $service->id, RendezVous::STATUT_VALIDE, '2026-10-22', '10:00');

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Statistiques::class)
            ->set('date', '2026-10-21')
            ->set('periode', 'trimestre')
            ->assertSet('periode', 'jour')
            // Retour sur 'jour' : seul le RDV du 21/10 est compté, celui du 22/10 est exclu.
            ->assertViewHas('stats', fn (array $stats) => $stats['global']['rdv']['total'] === 1)
            ->assertViewHas('stats', fn (array $stats) => $stats['periode']['type'] === 'jour');
    }

    public function test_periode_invalide_est_normalisee_par_le_service(): void
    {
        $stats = app(StatisticsService::class)->getGlobalStatistics(Carbon::parse('2026-10-21'), 'trimestre');

        $this->assertSame('jour', $stats['periode']['type']);
        $this->assertSame('2026-10-21', $stats['periode']['debut']);
        $this->assertSame('2026-10-21', $stats['periode']['fin']);
    }
}