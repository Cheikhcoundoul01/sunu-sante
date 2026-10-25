<?php

namespace App\Livewire\Patient;

use App\Models\User;
use App\Rules\TelephoneSenegal;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class MonProfil extends Component
{
    public string $name = '';
    public string $email = '';
    public ?string $telephone = null;
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
        $this->telephone = $user->telephone;
    }

    public function mettreAJourProfil(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(),
            'telephone' => ['nullable', 'string', new TelephoneSenegal],
        ]);

        $this->messageErreur = null;
        $this->messageSucces = null;

        try {
            auth()->user()->update([
                'name' => $this->name,
                'email' => $this->email,
                'telephone' => TelephoneSenegal::normaliser($this->telephone),
            ]);

            $this->telephone = auth()->user()->telephone;
            $this->messageSucces = 'Profil mis à jour.';
        } catch (\Exception $e) {
            report($e);
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
            report($e);
            $this->messageErreur = 'Erreur lors du changement de mot de passe.';
        }
    }

    public function render()
    {
        return view('livewire.patient.mon-profil')->layout('layouts.patient');
    }
}