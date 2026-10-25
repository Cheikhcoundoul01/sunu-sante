<?php

namespace Tests\Feature\Patient;

use App\Livewire\Patient\MonProfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MonProfilTelephoneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributs
     */
    private function patient(array $attributs = []): User
    {
        return User::factory()->create(array_merge([
            'statut_compte' => 'Actif',
        ], $attributs));
    }

    public function test_telephone_valide_est_enregistre_normalise(): void
    {
        $patient = $this->patient();

        Livewire::actingAs($patient)
            ->test(MonProfil::class)
            ->set('telephone', '+221 77 123 45 67')
            ->call('mettreAJourProfil')
            ->assertHasNoErrors()
            ->assertSet('telephone', '771234567');

        $this->assertSame('771234567', $patient->fresh()->telephone);
    }

    public function test_telephone_invalide_est_refuse_et_non_enregistre(): void
    {
        $patient = $this->patient(['name' => 'Nom Initial']);

        Livewire::actingAs($patient)
            ->test(MonProfil::class)
            ->set('name', 'Nom Modifie')
            ->set('telephone', '77-123-45-67')
            ->call('mettreAJourProfil')
            ->assertHasErrors(['telephone']);

        $patient->refresh();
        $this->assertNull($patient->telephone);
        $this->assertSame('Nom Initial', $patient->name);
    }

    public function test_profil_sans_telephone_reste_modifiable(): void
    {
        $patient = $this->patient(['telephone' => null]);

        Livewire::actingAs($patient)
            ->test(MonProfil::class)
            ->set('name', 'Nouveau Nom')
            ->set('telephone', '')
            ->call('mettreAJourProfil')
            ->assertHasNoErrors();

        $patient->refresh();
        $this->assertSame('Nouveau Nom', $patient->name);
        $this->assertNull($patient->telephone);
    }

    public function test_mot_de_passe_modifiable_sans_telephone(): void
    {
        $patient = $this->patient([
            'telephone' => null,
            'password' => Hash::make('AncienMdp123'),
        ]);

        Livewire::actingAs($patient)
            ->test(MonProfil::class)
            ->set('currentPassword', 'AncienMdp123')
            ->set('password', 'NouveauMdp123')
            ->set('password_confirmation', 'NouveauMdp123')
            ->call('changerMotDePasse')
            ->assertHasNoErrors();

        $patient->refresh();
        $this->assertTrue(Hash::check('NouveauMdp123', $patient->password));
        $this->assertNull($patient->telephone);
    }

    public function test_telephone_vide_enregistre_null(): void
    {
        $patient = $this->patient(['telephone' => '771234567']);

        Livewire::actingAs($patient)
            ->test(MonProfil::class)
            ->set('telephone', '')
            ->call('mettreAJourProfil')
            ->assertHasNoErrors();

        $this->assertNull($patient->fresh()->telephone);
    }

    public function test_telephone_valide_remplace_l_ancien_numero(): void
    {
        $patient = $this->patient(['telephone' => '701234567']);

        Livewire::actingAs($patient)
            ->test(MonProfil::class)
            ->set('telephone', '78 123 45 67')
            ->call('mettreAJourProfil')
            ->assertHasNoErrors();

        $this->assertSame('781234567', $patient->fresh()->telephone);
    }
}
