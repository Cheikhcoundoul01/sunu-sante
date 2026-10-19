<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Services;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesTest extends TestCase
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

    protected function personnelRole(string $role, ?int $serviceId = null): User
    {
        $user = User::factory()->create([
            'statut_compte' => 'Actif',
            'service_id' => $serviceId,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_peut_acceder_a_la_gestion_des_services(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/services')
            ->assertStatus(200)
            ->assertSee('Gestion des services');
    }

    public function test_les_autres_roles_ne_peuvent_pas_acceder(): void
    {
        $service = Service::create(['nom' => 'Cardiologie', 'actif' => true]);

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get('/admin/services')->assertStatus(403);

        foreach (['Médecin', 'Secrétaire de service', "Agent d'accueil", 'Major de service'] as $role) {
            $this->actingAs($this->personnelRole($role, $service->id))
                ->get('/admin/services')
                ->assertStatus(403);
        }
    }

    public function test_admin_peut_creer_un_service(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Services::class)
            ->set('nom', 'Pédiatrie')
            ->set('description', 'Service de pédiatrie')
            ->set('actif', true)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'nom' => 'Pédiatrie',
            'description' => 'Service de pédiatrie',
            'actif' => 1,
        ]);
    }

    public function test_le_nom_du_service_doit_etre_unique(): void
    {
        Service::create(['nom' => 'Cardiologie', 'actif' => true]);

        Livewire::actingAs($this->admin())
            ->test(Services::class)
            ->set('nom', 'Cardiologie')
            ->call('enregistrer')
            ->assertHasErrors(['nom' => 'unique']);

        $this->assertSame(1, Service::where('nom', 'Cardiologie')->count());
    }

    public function test_admin_peut_modifier_un_service(): void
    {
        $service = Service::create(['nom' => 'Cardiologie', 'actif' => true]);

        Livewire::actingAs($this->admin())
            ->test(Services::class)
            ->call('editer', $service->id)
            ->assertSet('nom', 'Cardiologie')
            ->set('nom', 'Cardiologie adulte')
            ->set('description', 'Mise à jour')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'nom' => 'Cardiologie adulte',
            'description' => 'Mise à jour',
        ]);
    }

    public function test_admin_peut_desactiver_puis_reactiver_un_service(): void
    {
        $service = Service::create(['nom' => 'Cardiologie', 'actif' => true]);

        Livewire::actingAs($this->admin())
            ->test(Services::class)
            ->call('basculer', $service->id);

        $this->assertFalse($service->fresh()->actif);

        Livewire::actingAs($this->admin())
            ->test(Services::class)
            ->call('basculer', $service->id);

        $this->assertTrue($service->fresh()->actif);
    }
}
