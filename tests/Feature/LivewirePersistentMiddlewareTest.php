<?php

namespace Tests\Feature;

use App\Livewire\Admin\Personnel;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LivewirePersistentMiddlewareTest extends TestCase
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

    /**
     * Extrait le snapshot Livewire tel qu'émis dans le HTML initial.
     */
    protected function snapshotFrom(string $html): string
    {
        $raw = (string) str($html)->betweenFirst('wire:snapshot="', '"');

        return htmlspecialchars_decode($raw, ENT_QUOTES | ENT_SUBSTITUTE);
    }

    /**
     * @return array<string, mixed>
     */
    protected function updatePayload(string $snapshot, int $serviceId): array
    {
        return [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [[
                    'path' => '',
                    'method' => 'basculer',
                    'params' => [$serviceId],
                ]],
            ]],
        ];
    }

    /**
     * C. Une requête Livewire (/livewire/update) est refusée après désactivation,
     * alors que la session était déjà authentifiée au moment du rendu.
     */
    public function test_une_requete_livewire_est_refusee_apres_desactivation_du_compte(): void
    {
        $service = Service::create(['nom' => 'Cardiologie', 'actif' => true]);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get('/admin/services')->assertStatus(200)->getContent();
        $snapshot = $this->snapshotFrom($html);

        // Le compte est désactivé APRÈS le rendu, sans invalider la session en cours.
        $admin->update(['statut_compte' => 'Rejeté']);

        $response = $this->postJson(
            '/livewire/update',
            $this->updatePayload($snapshot, $service->id),
            ['X-Livewire' => 'true']
        );

        $response->assertRedirect(route('login'));

        $this->assertTrue(
            $service->fresh()->actif,
            "L'action Livewire ne doit pas s'exécuter quand le compte est désactivé."
        );
        $this->assertGuest();
    }

    /** Contrôle positif : le même appel réussit pour un compte actif. */
    public function test_une_requete_livewire_reussit_pour_un_compte_actif(): void
    {
        $service = Service::create(['nom' => 'Cardiologie', 'actif' => true]);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get('/admin/services')->assertStatus(200)->getContent();
        $snapshot = $this->snapshotFrom($html);

        $response = $this->postJson(
            '/livewire/update',
            $this->updatePayload($snapshot, $service->id),
            ['X-Livewire' => 'true']
        );

        $response->assertOk();

        $this->assertFalse($service->fresh()->actif);
    }

    /** D. Les autorisations existantes restent appliquées sur les requêtes Livewire. */
    public function test_les_autorisations_existantes_sont_preservees(): void
    {
        $service = Service::create(['nom' => 'Cardiologie', 'actif' => true]);

        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');

        $this->actingAs($medecin)->get('/admin/personnel')->assertStatus(403);

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($medecin)->test(Personnel::class);
            $this->fail('Une réponse 403 était attendue pour un compte sans la permission requise.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
