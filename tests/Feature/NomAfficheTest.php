<?php

namespace Tests\Feature;

use App\Livewire\Admin\Personnel;
use App\Mail\RendezVousAnnule;
use App\Mail\RendezVousModifie;
use App\Mail\RendezVousValide;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NomAfficheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function service(string $nom = 'Cardiologie'): Service
    {
        return Service::create(['nom' => $nom, 'actif' => true]);
    }

    protected function compte(string $name, string $role, ?int $serviceId = null): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_le_titre_dr_est_ajoute_pour_un_medecin_sans_titre_enregistre(): void
    {
        $medecin = $this->compte('Ndiaye Mamadou', 'Médecin', $this->service()->id);

        $this->assertSame('Dr. Ndiaye Mamadou', $medecin->nomAffiche);
    }

    public function test_un_titre_deja_enregistre_n_est_jamais_duplique(): void
    {
        $service = $this->service();

        $this->assertSame(
            'Dr. Isma COUNDOUL',
            $this->compte('DR. Isma COUNDOUL', 'Médecin', $service->id)->nomAffiche
        );
        $this->assertSame(
            'Dr. Aminata Ndiaye (Médecin Démo)',
            $this->compte('Dr. Aminata Ndiaye (Médecin Démo)', 'Médecin', $service->id)->nomAffiche
        );
    }

    public function test_les_autres_roles_et_les_comptes_sans_role_n_obtiennent_pas_le_titre(): void
    {
        $service = $this->service();

        $this->assertSame('Fatou Sarr', $this->compte('Fatou Sarr', 'Secrétaire de service', $service->id)->nomAffiche);
        $this->assertSame('Moussa Diallo', $this->compte('Moussa Diallo', "Agent d'accueil", $service->id)->nomAffiche);
        $this->assertSame('Awa Diop', $this->compte('Awa Diop', 'Patient')->nomAffiche);

        $sansRole = User::factory()->create(['name' => 'Compte Sans Role', 'statut_compte' => 'Actif']);
        $this->assertSame('Compte Sans Role', $sansRole->nomAffiche);
    }

    public function test_un_nom_commencant_par_dr_sans_titre_reel_n_est_pas_abime(): void
    {
        $medecin = $this->compte('Dramé Fati', 'Médecin', $this->service()->id);

        $this->assertSame('Dr. Dramé Fati', $medecin->nomAffiche);
    }

    public function test_l_affichage_ne_modifie_jamais_le_nom_enregistre_en_base(): void
    {
        $medecin = $this->compte('Ndiaye Mamadou', 'Médecin', $this->service()->id);

        $this->assertSame('Dr. Ndiaye Mamadou', $medecin->nomAffiche);

        $this->assertDatabaseHas('users', ['id' => $medecin->id, 'name' => 'Ndiaye Mamadou']);
        $this->assertSame('Ndiaye Mamadou', $medecin->fresh()->name);
    }

    public function test_la_liste_du_personnel_affiche_le_titre_dr_sans_modifier_les_donnees(): void
    {
        $service = $this->service();
        $medecin = $this->compte('Ndiaye Mamadou', 'Médecin', $service->id);
        $medecinDr = $this->compte('DR. Isma COUNDOUL', 'Médecin', $service->id);
        $secretaire = $this->compte('Fatou Sarr', 'Secrétaire de service', $service->id);

        $admin = User::factory()->create(['statut_compte' => 'Actif']);
        $admin->assignRole('Administrateur');

        Livewire::actingAs($admin)
            ->test(Personnel::class)
            ->assertSee('Dr. Ndiaye Mamadou')
            ->assertSee('Dr. Isma COUNDOUL')
            ->assertSee('Fatou Sarr')
            ->assertDontSee('DR. Isma COUNDOUL')
            ->assertDontSee('Dr. Fatou Sarr');

        $this->assertSame('Ndiaye Mamadou', $medecin->fresh()->name);
        $this->assertSame('DR. Isma COUNDOUL', $medecinDr->fresh()->name);
        $this->assertSame('Fatou Sarr', $secretaire->fresh()->name);
    }

    public function test_le_header_de_l_espace_medecin_affiche_le_titre_dr(): void
    {
        $medecin = $this->compte('Ndiaye Mamadou', 'Médecin', $this->service()->id);

        $this->actingAs($medecin)
            ->get('/medecin/patients-du-jour')
            ->assertStatus(200)
            ->assertSee('Dr. Ndiaye Mamadou');

        $this->assertSame('Ndiaye Mamadou', $medecin->fresh()->name);
    }

    public function test_les_emails_de_rendez_vous_affichent_le_titre_dr_sans_doublon(): void
    {
        $service = $this->service();
        $medecin = $this->compte('Ndiaye Mamadou', 'Médecin', $service->id);
        $patient = $this->compte('Awa Diop', 'Patient');

        $rdv = RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => '2026-10-20',
            'heure_debut' => '09:30',
            'duree_prevue' => 30,
            'statut' => RendezVous::STATUT_VALIDE,
        ]);

        foreach ([RendezVousValide::class, RendezVousModifie::class, RendezVousAnnule::class] as $mailableClass) {
            $mailable = new $mailableClass($rdv);

            $html = $mailable->render();
            $this->assertStringContainsString('Dr. Ndiaye Mamadou', $html);
            $this->assertStringNotContainsString('Dr. Dr.', $html);

            $text = view($mailable->textView, $mailable->buildViewData())->render();
            $this->assertStringContainsString('Dr. Ndiaye Mamadou', $text);
            $this->assertStringNotContainsString('Dr. Dr.', $text);
        }

        $this->assertSame('Ndiaye Mamadou', $medecin->fresh()->name);
    }
}
