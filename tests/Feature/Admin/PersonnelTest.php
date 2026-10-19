<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Personnel;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PersonnelTest extends TestCase
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
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Administrateur');

        return $user;
    }

    protected function service(string $nom = 'Cardiologie', bool $actif = true): Service
    {
        return Service::create(['nom' => $nom, 'actif' => $actif]);
    }

    /**
     * @return array<int, string>
     */
    protected function roles(): array
    {
        return ['Médecin', 'Secrétaire de service', "Agent d'accueil", 'Major de service'];
    }

    public function test_admin_peut_acceder_a_la_gestion_du_personnel(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/personnel')
            ->assertStatus(200)
            ->assertSee('Gestion du personnel');
    }

    public function test_les_autres_roles_ne_peuvent_pas_acceder(): void
    {
        $service = $this->service();

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get('/admin/personnel')->assertStatus(403);

        foreach ($this->roles() as $role) {
            $staff = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
            $staff->assignRole($role);
            $this->actingAs($staff)->get('/admin/personnel')->assertStatus(403);
        }
    }

    public function test_admin_peut_creer_les_quatre_types_de_comptes_rattaches_a_leur_service(): void
    {
        $service = $this->service();

        foreach ($this->roles() as $index => $role) {
            Livewire::actingAs($this->admin())
                ->test(Personnel::class)
                ->set('name', 'Agent '.$index)
                ->set('email', 'agent'.$index.'@sunu-sante.sn')
                ->set('role', $role)
                ->set('service_id', $service->id)
                ->set('password', 'motdepasse123')
                ->set('password_confirmation', 'motdepasse123')
                ->call('enregistrer')
                ->assertHasNoErrors();

            $utilisateur = User::where('email', 'agent'.$index.'@sunu-sante.sn')->first();

            $this->assertNotNull($utilisateur);
            $this->assertTrue($utilisateur->hasRole($role));
            $this->assertSame($service->id, $utilisateur->service_id);
            $this->assertSame('Actif', $utilisateur->statut_compte);
        }
    }

    public function test_le_mot_de_passe_est_hache_et_jamais_stocke_en_clair(): void
    {
        $service = $this->service();

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Docteur Test')
            ->set('email', 'docteur@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $service->id)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $utilisateur = User::where('email', 'docteur@sunu-sante.sn')->firstOrFail();

        $this->assertNotSame('motdepasse123', $utilisateur->password);
        $this->assertTrue(Hash::check('motdepasse123', $utilisateur->password));
    }

    public function test_aucun_email_n_est_envoye_lors_de_la_creation(): void
    {
        $service = $this->service();
        Mail::fake();

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Docteur Sans Mail')
            ->set('email', 'sansmail@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $service->id)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasNoErrors();

        Mail::assertNothingSent();
    }

    public function test_email_duplique_est_refuse(): void
    {
        $service = $this->service();
        User::factory()->create(['email' => 'deja@sunu-sante.sn', 'statut_compte' => 'Actif']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Doublon')
            ->set('email', 'deja@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $service->id)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('Cette adresse e-mail est déjà utilisée.');

        $this->assertSame(1, User::where('email', 'deja@sunu-sante.sn')->count());
    }

    public function test_service_obligatoire_et_actif(): void
    {
        $inactif = $this->service('Service fermé', false);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Sans Service')
            ->set('email', 'sansservice@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', null)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasErrors(['service_id' => 'required']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Service Inactif')
            ->set('email', 'inactif@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $inactif->id)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasErrors(['service_id' => 'exists']);

        $this->assertDatabaseMissing('users', ['email' => 'sansservice@sunu-sante.sn']);
        $this->assertDatabaseMissing('users', ['email' => 'inactif@sunu-sante.sn']);
    }

    public function test_role_non_autorise_est_refuse(): void
    {
        $service = $this->service();

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Faux Rôle')
            ->set('email', 'fauxrole@sunu-sante.sn')
            ->set('role', 'Administrateur')
            ->set('service_id', $service->id)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasErrors(['role' => 'in']);

        $this->assertDatabaseMissing('users', ['email' => 'fauxrole@sunu-sante.sn']);
    }

    public function test_mot_de_passe_trop_court_est_refuse(): void
    {
        $service = $this->service();

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Court')
            ->set('email', 'court@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $service->id)
            ->set('password', '123')
            ->set('password_confirmation', '123')
            ->call('enregistrer')
            ->assertHasErrors(['password' => 'min']);

        $this->assertDatabaseMissing('users', ['email' => 'court@sunu-sante.sn']);
    }

    public function test_admin_peut_modifier_un_compte_sans_ecraser_le_mot_de_passe_si_vide(): void
    {
        $service = $this->service();
        $medecin = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'medecin@sunu-sante.sn',
            'password' => Hash::make('motdepasse123'),
            'statut_compte' => 'Actif',
            'service_id' => $service->id,
        ]);
        $medecin->assignRole('Médecin');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('editer', $medecin->id)
            ->assertSet('name', 'Ancien Nom')
            ->set('name', 'Nouveau Nom')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $medecin->refresh();
        $this->assertSame('Nouveau Nom', $medecin->name);
        $this->assertTrue(Hash::check('motdepasse123', $medecin->password));
    }

    public function test_admin_peut_desactiver_puis_reactiver_un_compte_du_personnel(): void
    {
        $service = $this->service();
        $secretaire = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $secretaire->assignRole('Secrétaire de service');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('basculer', $secretaire->id);

        $this->assertSame('Rejeté', $secretaire->fresh()->statut_compte);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('basculer', $secretaire->id);

        $this->assertSame('Actif', $secretaire->fresh()->statut_compte);
    }

    public function test_desactivation_ne_touche_pas_un_compte_patient(): void
    {
        $service = $this->service();
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($this->admin())
                ->test(Personnel::class)
                ->call('basculer', $patient->id);
            $this->fail('Une réponse 403 était attendue pour un compte non membre du personnel.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame('Actif', $patient->fresh()->statut_compte);
        $this->assertSame('Patient', $patient->fresh()->getRoleNames()->first());
    }

    public function test_le_formulaire_affiche_des_libelles_visibles_et_des_placeholders(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/personnel')
            ->assertStatus(200)
            ->assertSee('Nom complet')
            ->assertSee('Adresse e-mail')
            ->assertSee('Rôle')
            ->assertSee('Service')
            ->assertSee('Mot de passe initial')
            ->assertSee('Confirmation du mot de passe')
            ->assertSee('Ex. Aminata Ndiaye')
            ->assertSee('nom@exemple.sn')
            ->assertSee('Au moins 8 caractères')
            ->assertSee('Ressaisir le mot de passe');

        // Les libellés ne doivent plus être transmis comme un attribut `value` ignoré.
        $this->actingAs($this->admin())
            ->get('/admin/personnel')
            ->assertDontSee('value="Nom complet"', false);
    }

    public function test_aucune_erreur_de_validation_au_chargement_du_formulaire(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->assertHasNoErrors()
            ->assertDontSee('Le nom complet est obligatoire.')
            ->assertDontSee("L'adresse e-mail est obligatoire.")
            ->assertDontSee('Veuillez sélectionner un rôle.')
            ->assertDontSee('Veuillez sélectionner un service.')
            ->assertDontSee('Le mot de passe initial est obligatoire.');
    }

    public function test_les_erreurs_n_apparaissent_qu_apres_soumission(): void
    {
        $component = Livewire::actingAs($this->admin())->test(Personnel::class);

        $component->assertHasNoErrors();

        $component->call('enregistrer');

        $component->assertHasErrors(['name', 'email', 'role', 'service_id', 'password']);
        $component->assertSee('Le nom complet est obligatoire.');
    }

    public function test_apres_creation_le_message_de_confirmation_et_le_formulaire_sont_reinitialises(): void
    {
        $service = $this->service();

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Docteur Reinit')
            ->set('email', 'reinit@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $service->id)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasNoErrors()
            ->assertSee('Compte du personnel créé.')
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('role', '')
            ->assertSet('service_id', null)
            ->assertSet('password', '')
            ->assertSet('password_confirmation', '')
            ->assertSet('editingId', null);
    }
}
