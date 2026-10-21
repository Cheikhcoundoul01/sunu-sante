<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Personnel;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PersonnelListeTest extends TestCase
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

    protected function personnel(int $serviceId, string $role, array $attributs = []): User
    {
        $user = User::factory()->create(array_merge([
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ], $attributs));
        $user->assignRole($role);

        return $user;
    }

    public function test_recherche_par_nom_et_par_email(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'Awa Sagna', 'email' => 'awa.sagna@sunu-sante.sn']);
        $this->personnel($service->id, 'Médecin', ['name' => 'Boubacar Zzz', 'email' => 'boubacar.zzz@sunu-sante.sn']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('search', 'Awa Sagna')
            ->assertSee('Awa Sagna')
            ->assertDontSee('Boubacar Zzz');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('search', 'boubacar.zzz@sunu-sante.sn')
            ->assertSee('Boubacar Zzz')
            ->assertDontSee('Awa Sagna');
    }

    public function test_filtre_par_role(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'Medecin Un']);
        $this->personnel($service->id, 'Secrétaire de service', ['name' => 'Secretaire Deux']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('filtreRole', 'Secrétaire de service')
            ->assertSee('Secretaire Deux')
            ->assertDontSee('Medecin Un');
    }

    public function test_filtre_par_service(): void
    {
        $cardio = $this->service('Cardiologie');
        $pediatrie = $this->service('Pédiatrie');
        $this->personnel($cardio->id, 'Médecin', ['name' => 'Medecin Cardio']);
        $this->personnel($pediatrie->id, 'Médecin', ['name' => 'Medecin Pediatrie']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('filtreService', (string) $cardio->id)
            ->assertSee('Medecin Cardio')
            ->assertDontSee('Medecin Pediatrie');
    }

    public function test_filtre_par_etat(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'Compte Actif', 'statut_compte' => 'Actif']);
        $this->personnel($service->id, 'Médecin', ['name' => 'Compte Inactif', 'statut_compte' => 'Rejeté']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('filtreEtat', 'Rejeté')
            ->assertSee('Compte Inactif')
            ->assertDontSee('Compte Actif');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('filtreEtat', 'Actif')
            ->assertSee('Compte Actif')
            ->assertDontSee('Compte Inactif');
    }

    public function test_pagination_limitee_a_dix_par_page(): void
    {
        $service = $this->service();
        for ($i = 1; $i <= 12; $i++) {
            $this->personnel($service->id, 'Médecin', [
                'name' => sprintf('Medecin %02d', $i),
                'email' => sprintf('medecin%02d@sunu-sante.sn', $i),
            ]);
        }

        $composant = Livewire::actingAs($this->admin())->test(Personnel::class);

        // 10 comptes sur la 1re page, le 12e n'y figure pas.
        $composant->assertSee('Medecin 01')
            ->assertSee('Medecin 10')
            ->assertDontSee('Medecin 11')
            ->assertDontSee('Medecin 12')
            ->assertViewHas('totalComptes', 12);

        $composant->call('gotoPage', 2)
            ->assertSee('Medecin 11')
            ->assertSee('Medecin 12')
            ->assertDontSee('Medecin 01');
    }

    public function test_le_changement_de_filtre_reinitialise_la_pagination(): void
    {
        $service = $this->service();
        for ($i = 1; $i <= 12; $i++) {
            $this->personnel($service->id, 'Médecin', [
                'name' => sprintf('Medecin %02d', $i),
                'email' => sprintf('medecin%02d@sunu-sante.sn', $i),
            ]);
        }

        $composant = Livewire::actingAs($this->admin())->test(Personnel::class);
        $composant->call('gotoPage', 2);
        $this->assertSame(2, $composant->instance()->getPage());

        $composant->set('search', 'Medecin 01');
        $this->assertSame(1, $composant->instance()->getPage());
        $composant->assertSee('Medecin 01');
    }

    public function test_compteurs_total_et_actifs_excluent_les_patients(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'A Un', 'email' => 'a.un@sunu-sante.sn', 'statut_compte' => 'Actif']);
        $this->personnel($service->id, 'Médecin', ['name' => 'B Deux', 'email' => 'b.deux@sunu-sante.sn', 'statut_compte' => 'Actif']);
        $this->personnel($service->id, 'Médecin', ['name' => 'C Trois', 'email' => 'c.trois@sunu-sante.sn', 'statut_compte' => 'Rejeté']);

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->assertViewHas('totalComptes', 3)
            ->assertViewHas('totalActifs', 2)
            ->assertSee('comptes au total');
    }

    public function test_les_comptes_desactives_sont_affiches_apres_les_actifs(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'Zzz Actif', 'email' => 'z.actif@sunu-sante.sn', 'statut_compte' => 'Actif']);
        $this->personnel($service->id, 'Médecin', ['name' => 'Aaa Inactif', 'email' => 'a.inactif@sunu-sante.sn', 'statut_compte' => 'Rejeté']);

        $composant = Livewire::actingAs($this->admin())->test(Personnel::class);
        $html = $composant->html();

        // Malgré l'ordre alphabétique, le compte actif précède le compte désactivé.
        $this->assertLessThan(
            strpos($html, 'Aaa Inactif'),
            strpos($html, 'Zzz Actif')
        );
    }

    public function test_desactivation_protegee_cote_serveur_pour_un_compte_non_personnel(): void
    {
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
    }

    public function test_basculer_desactive_et_reactiver_un_compte_du_personnel(): void
    {
        $service = $this->service();
        $secretaire = $this->personnel($service->id, 'Secrétaire de service', ['name' => 'Secretaire Bascule']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('basculer', $secretaire->id);

        $this->assertSame('Rejeté', $secretaire->fresh()->statut_compte);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('basculer', $secretaire->id);

        $this->assertSame('Actif', $secretaire->fresh()->statut_compte);
    }

    public function test_reinitialiser_les_filtres_restaure_la_liste_complete(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'Medecin Un']);
        $this->personnel($service->id, 'Secrétaire de service', ['name' => 'Secretaire Deux']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('filtreRole', 'Médecin')
            ->assertSee('Medecin Un')
            ->assertDontSee('Secretaire Deux')
            ->call('reinitialiserFiltres')
            ->assertSet('filtreRole', 'Tous')
            ->assertSee('Medecin Un')
            ->assertSee('Secretaire Deux');
    }

    public function test_la_confirmation_est_presente_avant_desactivation(): void
    {
        $service = $this->service();
        $this->personnel($service->id, 'Médecin', ['name' => 'Medecin Confirm']);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->assertSee('wire:confirm', false);
    }
}
