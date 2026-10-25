<?php

namespace Tests\Feature;

use App\Livewire\Auth\RegisterPatient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTelephoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function formatsValides(): array
    {
        return [
            'local' => ['771234567', '771234567'],
            'espaces' => ['77 123 45 67', '771234567'],
            'prefixe' => ['+221771234567', '771234567'],
            'prefixe avec espaces' => ['+221 77 123 45 67', '771234567'],
        ];
    }

    #[DataProvider('formatsValides')]
    public function test_les_formats_valides_sont_enregistres_normalises(string $saisie, string $attendu): void
    {
        Livewire::test(RegisterPatient::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa@example.com')
            ->set('telephone', $saisie)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('patient.demande-envoyee'));

        $this->assertSame($attendu, User::where('email', 'awa@example.com')->value('telephone'));
    }

    public function test_le_telephone_est_obligatoire(): void
    {
        Livewire::test(RegisterPatient::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa@example.com')
            ->set('telephone', '')
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('register')
            ->assertHasErrors(['telephone' => 'required']);

        $this->assertDatabaseMissing('users', ['email' => 'awa@example.com']);
    }

    public function test_le_message_du_telephone_obligatoire_est_en_francais(): void
    {
        $component = Livewire::test(RegisterPatient::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa@example.com')
            ->set('telephone', '')
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('register');

        $this->assertSame(
            'Le numéro de téléphone est obligatoire.',
            $component->errors()->first('telephone')
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function formatsInvalides(): array
    {
        return [
            'prefixe inconnu' => ['691234567'],
            'huit chiffres' => ['77123456'],
            'dix chiffres' => ['7712345678'],
            'lettres' => ['77abc4567'],
            'tirets' => ['77-123-45-67'],
            'points' => ['77.123.45.67'],
            'prefixe sans plus' => ['221771234567'],
            'indicatif etranger' => ['+225771234567'],
            'espaces seulement' => ['   '],
        ];
    }

    #[DataProvider('formatsInvalides')]
    public function test_les_formats_invalides_sont_refuses(string $saisie): void
    {
        Livewire::test(RegisterPatient::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa@example.com')
            ->set('telephone', $saisie)
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('register')
            ->assertHasErrors(['telephone']);

        $this->assertDatabaseMissing('users', ['email' => 'awa@example.com']);
    }

    public function test_le_message_du_telephone_invalide_est_en_francais(): void
    {
        $component = Livewire::test(RegisterPatient::class)
            ->set('name', 'Awa Diop')
            ->set('email', 'awa@example.com')
            ->set('telephone', '77-123-45-67')
            ->set('password', 'motdepasse123')
            ->set('password_confirmation', 'motdepasse123')
            ->call('register');

        $this->assertSame(
            'Le numéro de téléphone doit contenir 9 chiffres et commencer par 70, 75, 76, 77 ou 78.',
            $component->errors()->first('telephone')
        );
    }

    public function test_le_telephone_est_absent_de_la_serialisation_du_modele(): void
    {
        $patient = User::factory()->create(['telephone' => '771234567']);

        $this->assertArrayNotHasKey('telephone', $patient->toArray());
        $this->assertStringNotContainsString('771234567', $patient->toJson());
    }

    public function test_les_comptes_crees_sans_telephone_restent_valides(): void
    {
        $utilisateur = User::create([
            'name' => 'Agent Sans Telephone',
            'email' => 'agent@example.com',
            'password' => 'motdepasse123',
            'statut_compte' => 'Actif',
        ]);

        $this->assertNull($utilisateur->telephone);
    }
}
