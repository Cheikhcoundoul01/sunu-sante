<?php

namespace App\Livewire\Patient;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class MonProfil extends Component
{
    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $password = '';
    public string $password_confirmation = '';

    public ?string $messageSucces = null;
    public ?string $messageErreur = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function mettreAJourProfil(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(),
        ]);

        $this->messageErreur = null;
        $this->messageSucces = null;

        try {
            auth()->user()->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            $this->messageSucces = 'Profil mis à jour.';
        } catch (\Exception $e) {
            $this->messageErreur = 'Erreur lors de la mise à jour.';
        }
    }

    public function changerMotDePasse(): void
    {
        $this->validate([
            'currentPassword' => 'required|string',
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        $this->messageErreur = null;
        $this->messageSucces = null;

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->messageErreur = 'Mot de passe actuel incorrect.';
            return;
        }

        try {
            auth()->user()->update([
                'password' => Hash::make($this->password),
            ]);

            $this->currentPassword = '';
            $this->password = '';
            $this->password_confirmation = '';
            $this->messageSucces = 'Mot de passe modifié.';
        } catch (\Exception $e) {
            $this->messageErreur = 'Erreur lors du changement de mot de passe.';
        }
    }

    public function render()
    {
        return view('livewire.patient.mon-profil')->layout('layouts.patient');
    }
}