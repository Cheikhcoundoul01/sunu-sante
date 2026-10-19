<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class RegisterPatient extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'statut_compte' => 'En attente',
        ]);

        $user->assignRole('Patient');

        Session::regenerate();

        $this->redirect(route('patient.demande-envoyee'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register-patient')->layout('layouts.guest');
    }
}
