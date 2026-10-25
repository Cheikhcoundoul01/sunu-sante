<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JournalActivite;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class JournalActiviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create([
            'statut_compte' => 'Actif',
            'telephone' => '+221771234567',
        ]);
        $user->assignRole('Administrateur');

        return $user;
    }

    protected function utilisateur(string $role): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole($role);

        return $user;
    }

    protected function journal(array $attributs = []): Activity
    {
        return Activity::create(array_merge([
            'description' => 'Description technique',
            'event' => 'creation_rendez_vous',
            'subject_type' => RendezVous::class,
            'subject_id' => 1,
            'properties' => [],
        ], $attributs));
    }

    public function test_administrateur_accede_au_journal(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.journal'))
            ->assertOk()
            ->assertSee("Journal d'activité");
    }

    public function test_les_cinq_autres_roles_sont_interdits(): void
    {
        foreach ([
            'Patient',
            'Secrétaire de service',
            "Agent d'accueil",
            'Médecin',
            'Major de service',
        ] as $role) {
            $this->actingAs($this->utilisateur($role))
                ->get(route('admin.journal'))
                ->assertForbidden();
        }
    }

    public function test_visiteur_non_connecte_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('admin.journal'))
            ->assertRedirect(route('login'));
    }

    public function test_lignes_affichees_sans_donnees_personnelles_du_causeur(): void
    {
        $admin = $this->admin();

        $this->journal([
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'subject_type' => RendezVous::class,
            'subject_id' => 12,
            'event' => 'validation_rendez_vous',
            'properties' => ['statut' => 'Actif'],
        ]);

        Livewire::actingAs($admin)
            ->test(JournalActivite::class)
            ->assertSee($admin->name)
            ->assertSee('Administrateur')
            ->assertSee("Validation d'un rendez-vous")
            ->assertSee('Rendez-vous n°12')
            ->assertSee('Actif')
            ->assertDontSee($admin->email)
            ->assertDontSee('+221771234567');
    }

    public function test_description_avec_motif_prioritaire_non_affichee(): void
    {
        $admin = $this->admin();

        $this->journal([
            'event' => 'passage_prioritaire',
            'description' => 'Passage prioritaire motivé par une urgence cardiaque',
            'subject_type' => FileEntree::class,
            'subject_id' => 8,
        ]);

        Livewire::actingAs($admin)
            ->test(JournalActivite::class)
            ->assertSee('Passage prioritaire')
            ->assertDontSee('urgence cardiaque');
    }

    public function test_ancien_format_avec_email_dans_properties_non_affiche(): void
    {
        $admin = $this->admin();

        $this->journal([
            'event' => 'validation_compte_patient',
            'subject_type' => User::class,
            'subject_id' => 42,
            'properties' => [
                'patient_email' => 'ancien.patient@example.com',
                'traite_par' => 'secretaire@example.com',
                'statut' => 'Actif',
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(JournalActivite::class)
            ->assertSee('Compte patient n°42')
            ->assertSee('Actif')
            ->assertDontSee('ancien.patient@example.com')
            ->assertDontSee('secretaire@example.com');
    }

    public function test_auteur_supprime_affiche_auteur_inconnu(): void
    {
        $admin = $this->admin();

        $this->journal([
            'causer_type' => User::class,
            'causer_id' => 999999,
        ]);

        Livewire::actingAs($admin)
            ->test(JournalActivite::class)
            ->assertSee('Auteur inconnu')
            ->assertSee('—');
    }

    public function test_pagination_limite_quinze_lignes_par_page(): void
    {
        $admin = $this->admin();

        for ($id = 101; $id <= 116; $id++) {
            $this->journal([
                'subject_id' => $id,
                'created_at' => now()->addSeconds($id),
            ]);
        }

        $composant = Livewire::actingAs($admin)->test(JournalActivite::class);

        $composant
            ->assertViewHas('activites', fn ($activites) => $activites->count() === 15)
            ->assertSee('Rendez-vous n°116')
            ->assertDontSee('Rendez-vous n°101')
            ->call('gotoPage', 2)
            ->assertSee('Rendez-vous n°101')
            ->assertDontSee('Rendez-vous n°116');
    }

    public function test_filtre_par_date(): void
    {
        $admin = $this->admin();

        $this->journal([
            'subject_id' => 201,
            'created_at' => now()->startOfDay(),
        ]);
        $this->journal([
            'subject_id' => 202,
            'created_at' => now()->subDay()->startOfDay(),
        ]);

        Livewire::actingAs($admin)
            ->test(JournalActivite::class)
            ->set('date', now()->toDateString())
            ->assertSee('Rendez-vous n°201')
            ->assertDontSee('Rendez-vous n°202');
    }

    public function test_filtre_par_evenement(): void
    {
        $admin = $this->admin();

        $this->journal([
            'subject_id' => 301,
            'event' => 'creation_rendez_vous',
        ]);
        $this->journal([
            'subject_id' => 302,
            'event' => 'validation_rendez_vous',
        ]);

        Livewire::actingAs($admin)
            ->test(JournalActivite::class)
            ->set('event', 'creation_rendez_vous')
            ->assertSee('Rendez-vous n°301')
            ->assertDontSee('Rendez-vous n°302');
    }
}
