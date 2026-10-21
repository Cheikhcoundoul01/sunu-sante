<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Personnel;
use App\Livewire\Secretaire\Disponibilites;
use App\Livewire\Secretaire\RendezVousSecretaire;
use App\Livewire\Secretaire\Salles;
use App\Models\Disponibilite;
use App\Models\DisponibiliteSalle;
use App\Models\RendezVous;
use App\Models\Salle;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PersonnelMultiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
    }

    protected function admin(): User
    {
        $user = User::factory()->create(['statut_compte' => 'Actif']);
        $user->assignRole('Administrateur');

        return $user;
    }

    protected function service(string $nom): Service
    {
        return Service::create(['nom' => $nom, 'actif' => true]);
    }

    protected function secretaire(Service ...$services): User
    {
        $principal = array_shift($services);

        $user = User::factory()->create([
            'statut_compte' => 'Actif',
            'service_id' => $principal->id,
        ]);
        $user->assignRole('Secrétaire de service');

        if (! empty($services)) {
            $user->servicesSupplementaires()->sync(
                collect($services)->map(fn (Service $s) => $s->id)->all()
            );
        }

        return $user;
    }

    public function test_admin_peut_creer_une_secretaire_avec_deux_services(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa.diop@sunu-sante.sn')
            ->set('role', 'Secrétaire de service')
            ->set('service_id', $principal->id)
            ->set('servicesSupplementaires', [$supplementaire->id])
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $utilisateur = User::where('email', 'awa.diop@sunu-sante.sn')->firstOrFail();

        $this->assertSame($principal->id, $utilisateur->service_id);
        $this->assertTrue($utilisateur->servicesSupplementaires->contains($supplementaire->id));
        $this->assertEqualsCanonicalizing(
            [$principal->id, $supplementaire->id],
            $utilisateur->servicesAutorisesIds()
        );

        // Le pivot ne contient que le service supplémentaire : le principal
        // reste porté par `users.service_id`.
        $this->assertDatabaseHas('service_user', [
            'user_id' => $utilisateur->id,
            'service_id' => $supplementaire->id,
        ]);
        $this->assertDatabaseMissing('service_user', [
            'user_id' => $utilisateur->id,
            'service_id' => $principal->id,
        ]);
    }

    public function test_le_bloc_services_supplementaires_est_affiche_pour_le_role_secretaire(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('role', 'Secrétaire de service')
            ->assertSee('Services supplémentaires');
    }

    public function test_le_bloc_services_supplementaires_est_masque_pour_les_autres_roles(): void
    {
        foreach (['Médecin', "Agent d'accueil", 'Major de service'] as $role) {
            Livewire::actingAs($this->admin())
                ->test(Personnel::class)
                ->set('role', $role)
                ->assertDontSee('Services supplémentaires');
        }
    }

    public function test_les_selecteurs_de_role_et_de_service_principal_sont_reactifs(): void
    {
        // Sans `.live`, le navigateur n'enverrait pas le rôle sélectionné et le
        // bloc « Services supplémentaires » resterait invisible.
        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->assertSee('wire:model.live="role"', false)
            ->assertSee('wire:model.live="service_id"', false);
    }

    public function test_le_service_principal_est_retire_des_services_supplementaires_lors_du_changement(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('editer', $secretaire->id)
            ->assertSet('service_id', $principal->id)
            ->assertSet('servicesSupplementaires', [$supplementaire->id])
            // Le service supplémentaire devient le principal : il doit quitter
            // la liste des services supplémentaires (pas de doublon).
            ->set('service_id', $supplementaire->id)
            ->assertSet('servicesSupplementaires', [])
            ->set('name', 'Awa Diop')
            ->set('email', 'awa.diop@sunu-sante.sn')
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $secretaire->refresh();

        $this->assertSame($supplementaire->id, $secretaire->service_id);
        $this->assertTrue($secretaire->servicesSupplementaires->isEmpty());
    }

    public function test_un_service_principal_inchangee_conserve_les_services_supplementaires(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('editer', $secretaire->id)
            ->set('service_id', $principal->id)
            ->assertSet('servicesSupplementaires', [$supplementaire->id]);
    }

    public function test_le_service_principal_ne_peut_pas_etre_repete_en_supplementaire(): void
    {
        $principal = $this->service('Cardiologie');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa.diop@sunu-sante.sn')
            ->set('role', 'Secrétaire de service')
            ->set('service_id', $principal->id)
            ->set('servicesSupplementaires', [$principal->id])
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasErrors('servicesSupplementaires');

        $this->assertDatabaseMissing('users', ['email' => 'awa.diop@sunu-sante.sn']);
    }

    public function test_les_services_supplementaires_sont_refuses_pour_un_autre_role(): void
    {
        $principal = $this->service('Cardiologie');
        $autre = $this->service('Pédiatrie');

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->set('name', 'Moussa Fall')
            ->set('email', 'moussa.fall@sunu-sante.sn')
            ->set('role', 'Médecin')
            ->set('service_id', $principal->id)
            ->set('servicesSupplementaires', [$autre->id])
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('enregistrer')
            ->assertHasErrors('servicesSupplementaires');
    }

    public function test_modification_preserve_le_service_principal_et_remplace_les_supplementaires(): void
    {
        $principal = $this->service('Cardiologie');
        $supp1 = $this->service('Pédiatrie');
        $supp2 = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal, $supp1);

        Livewire::actingAs($this->admin())
            ->test(Personnel::class)
            ->call('editer', $secretaire->id)
            ->assertSet('service_id', $principal->id)
            ->set('servicesSupplementaires', [$supp2->id])
            ->call('enregistrer')
            ->assertHasNoErrors();

        $secretaire->refresh();

        $this->assertSame($principal->id, $secretaire->service_id);
        $this->assertTrue($secretaire->servicesSupplementaires->contains($supp2->id));
        $this->assertFalse($secretaire->servicesSupplementaires->contains($supp1->id));
    }

    public function test_un_secretaire_sans_service_supplementaire_reste_mono_service(): void
    {
        $principal = $this->service('Cardiologie');

        $secretaire = $this->secretaire($principal);

        $this->assertSame([$principal->id], $secretaire->servicesAutorisesIds());
    }

    public function test_la_secretaire_multi_service_voit_les_rendez_vous_de_ses_deux_services(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $medecinPrincipal = $this->creerMedecin($principal);
        $medecinSupp = $this->creerMedecin($supplementaire);
        $medecinAutre = $this->creerMedecin($autre);

        $rdvPrincipal = $this->creerRdv($patient, $medecinPrincipal, $principal, '2026-11-02');
        $rdvSupp = $this->creerRdv($patient, $medecinSupp, $supplementaire, '2026-11-03');
        $rdvAutre = $this->creerRdv($patient, $medecinAutre, $autre, '2026-11-04');

        Livewire::actingAs($secretaire)
            ->test(RendezVousSecretaire::class)
            ->assertViewHas('rdvs', function ($rdvs) use ($rdvPrincipal, $rdvSupp, $rdvAutre) {
                $ids = collect($rdvs->items())->pluck('id');

                return $ids->contains($rdvPrincipal->id)
                    && $ids->contains($rdvSupp->id)
                    && ! $ids->contains($rdvAutre->id);
            });
    }

    public function test_la_secretaire_multi_service_peut_valider_un_rdv_d_un_service_supplementaire(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $medecin = $this->creerMedecin($supplementaire);
        $rdv = $this->creerRdv($patient, $medecin, $supplementaire, '2026-11-05');

        Livewire::actingAs($secretaire)
            ->test(RendezVousSecretaire::class)
            ->call('valider', $rdv->id)
            ->assertHasNoErrors();

        $this->assertSame(RendezVous::STATUT_VALIDE, $rdv->fresh()->statut);
    }

    public function test_la_secretaire_ne_peut_pas_valider_un_rdv_hors_perimetre(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $medecin = $this->creerMedecin($autre);
        $rdv = $this->creerRdv($patient, $medecin, $autre, '2026-11-06');

        Livewire::actingAs($secretaire)
            ->test(RendezVousSecretaire::class)
            ->call('valider', $rdv->id);

        $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->fresh()->statut);
    }

    public function test_la_secretaire_multi_service_voit_les_salles_et_disponibilites_de_ses_services(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        $sallePrincipale = Salle::create(['service_id' => $principal->id, 'nom' => 'Salle A', 'active' => true]);
        $salleSupp = Salle::create(['service_id' => $supplementaire->id, 'nom' => 'Salle B', 'active' => true]);
        $salleAutre = Salle::create(['service_id' => $autre->id, 'nom' => 'Salle C', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(Salles::class)
            ->assertViewHas('salles', function ($salles) use ($sallePrincipale, $salleSupp, $salleAutre) {
                $ids = collect($salles)->pluck('id');

                return $ids->contains($sallePrincipale->id)
                    && $ids->contains($salleSupp->id)
                    && ! $ids->contains($salleAutre->id);
            });
    }

    public function test_la_secretaire_ne_peut_pas_basculer_une_salle_hors_perimetre(): void
    {
        $principal = $this->service('Cardiologie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal);

        $salle = Salle::create(['service_id' => $autre->id, 'nom' => 'Salle X', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(Salles::class)
            ->call('basculerSalle', $salle->id);

        $this->assertTrue($salle->fresh()->active);
    }

    public function test_la_secretaire_multi_service_peut_ajouter_une_disponibilite_a_un_medecin_supplementaire(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');

        $secretaire = $this->secretaire($principal, $supplementaire);
        $medecin = $this->creerMedecin($supplementaire);

        $jour = now()->addDays(2)->toDateString();

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('medecinId', $medecin->id)
            ->set('jour', $jour)
            ->set('heure_debut', '09:00')
            ->set('heure_fin', '10:00')
            ->call('ajouter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('disponibilites', [
            'medecin_id' => $medecin->id,
            'service_id' => $supplementaire->id,
        ]);
    }

    public function test_la_secretaire_ne_peut_pas_ajouter_une_disponibilite_a_un_medecin_hors_perimetre(): void
    {
        $principal = $this->service('Cardiologie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal);
        $medecin = $this->creerMedecin($autre);

        $jour = now()->addDays(2)->toDateString();

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->set('medecinId', $medecin->id)
            ->set('jour', $jour)
            ->set('heure_debut', '09:00')
            ->set('heure_fin', '10:00')
            ->call('ajouter');

        $this->assertSame(0, Disponibilite::where('medecin_id', $medecin->id)->count());
    }

    public function test_les_autres_roles_restent_mono_service(): void
    {
        $service = $this->service('Cardiologie');

        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');

        $this->assertSame([$service->id], $medecin->servicesAutorisesIds());
        $this->assertTrue($medecin->servicesSupplementaires->isEmpty());
    }

    public function test_la_secretaire_ne_peut_pas_basculer_une_disponibilite_de_salle_hors_perimetre(): void
    {
        $principal = $this->service('Cardiologie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal);

        $salleAutre = Salle::create(['service_id' => $autre->id, 'nom' => 'Salle Y', 'active' => true]);
        $dispoAutre = DisponibiliteSalle::create([
            'salle_id' => $salleAutre->id,
            'service_id' => $autre->id,
            'jour_semaine' => 1,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
            'active' => true,
        ]);

        Livewire::actingAs($secretaire)
            ->test(Salles::class)
            ->call('basculerDisponibilite', $dispoAutre->id);

        $this->assertTrue($dispoAutre->fresh()->active);
    }

    public function test_la_secretaire_ne_peut_pas_rejeter_un_rdv_hors_perimetre(): void
    {
        $principal = $this->service('Cardiologie');
        $supplementaire = $this->service('Pédiatrie');
        $autre = $this->service('Radiologie');

        $secretaire = $this->secretaire($principal, $supplementaire);

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $medecin = $this->creerMedecin($autre);
        $rdv = $this->creerRdv($patient, $medecin, $autre, '2026-11-07');

        Livewire::actingAs($secretaire)
            ->test(RendezVousSecretaire::class)
            ->call('rejeter', $rdv->id);

        $this->assertSame(RendezVous::STATUT_DEMANDE, $rdv->fresh()->statut);
    }

    public function test_l_admin_ne_peut_pas_editer_un_utilisateur_non_personnel(): void
    {
        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $this->withoutExceptionHandling();

        try {
            Livewire::actingAs($this->admin())
                ->test(Personnel::class)
                ->call('editer', $patient->id);
            $this->fail('Une réponse 403 était attendue pour un compte non membre du personnel.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame('Actif', $patient->fresh()->statut_compte);
    }

    public function test_un_ancien_secretaire_sans_pivot_conserve_l_acces_a_son_service_principal(): void
    {
        $principal = $this->service('Cardiologie');
        $autre = $this->service('Radiologie');

        $secretaire = User::factory()->create([
            'statut_compte' => 'Actif',
            'service_id' => $principal->id,
        ]);
        $secretaire->assignRole('Secrétaire de service');

        $this->assertTrue($secretaire->servicesSupplementaires()->get()->isEmpty());
        $this->assertSame([$principal->id], $secretaire->servicesAutorisesIds());

        $patient = User::factory()->create(['statut_compte' => 'Actif']);
        $patient->assignRole('Patient');

        $medecinPrincipal = $this->creerMedecin($principal);
        $medecinAutre = $this->creerMedecin($autre);

        $rdvPrincipal = $this->creerRdv($patient, $medecinPrincipal, $principal, '2026-11-08');
        $rdvAutre = $this->creerRdv($patient, $medecinAutre, $autre, '2026-11-09');

        Livewire::actingAs($secretaire)
            ->test(RendezVousSecretaire::class)
            ->assertViewHas('rdvs', function ($rdvs) use ($rdvPrincipal, $rdvAutre) {
                $ids = collect($rdvs->items())->pluck('id');

                return $ids->contains($rdvPrincipal->id) && ! $ids->contains($rdvAutre->id);
            });

        $sallePrincipale = Salle::create(['service_id' => $principal->id, 'nom' => 'Salle P', 'active' => true]);
        $salleAutre = Salle::create(['service_id' => $autre->id, 'nom' => 'Salle Q', 'active' => true]);

        Livewire::actingAs($secretaire)
            ->test(Salles::class)
            ->assertViewHas('salles', function ($salles) use ($sallePrincipale, $salleAutre) {
                $ids = collect($salles)->pluck('id');

                return $ids->contains($sallePrincipale->id) && ! $ids->contains($salleAutre->id);
            });

        Livewire::actingAs($secretaire)
            ->test(Disponibilites::class)
            ->assertViewHas('medecins', function ($medecins) use ($medecinPrincipal, $medecinAutre) {
                $ids = collect($medecins)->pluck('id');

                return $ids->contains($medecinPrincipal->id) && ! $ids->contains($medecinAutre->id);
            });
    }

    protected function creerMedecin(Service $service): User
    {
        $medecin = User::factory()->create(['statut_compte' => 'Actif', 'service_id' => $service->id]);
        $medecin->assignRole('Médecin');

        return $medecin;
    }

    protected function creerRdv(User $patient, User $medecin, Service $service, string $date): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'service_id' => $service->id,
            'date' => $date,
            'heure_debut' => '09:00',
            'statut' => RendezVous::STATUT_DEMANDE,
        ]);
    }
}
