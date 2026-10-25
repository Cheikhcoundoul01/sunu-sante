<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Rules\TelephoneSenegal;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class RegisterPatient extends Component
{
    public string $name = '';

    public string $email = '';

    public string $telephone = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'string', new TelephoneSenegal],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }

    /**
     * @var array<string, string>
     */
    protected $messages = [
        'telephone.required' => 'Le numéro de téléphone est obligatoire.',
    ];

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'telephone' => TelephoneSenegal::normaliser($this->telephone),
            'password' => $this->password,
            'statut_compte' => 'En attente',
        ]);

        $user->assignRole('Patient');

        $this->telephone = '';

        Session::regenerate();

        $this->redirect(route('patient.demande-envoyee'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register-patient')->layout('layouts.guest');
    }
}
