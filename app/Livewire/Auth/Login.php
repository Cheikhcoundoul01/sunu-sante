<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(): void
    {
        $this->validate();

        $throttleKey = 'login:'.strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (! $user->estActif()) {
            Auth::logout();
            Session::regenerate();

            throw ValidationException::withMessages([
                'email' => $user->messageCompteInactif(),
            ]);
        }

        Session::regenerate();

        $route = $user->routeSelonRole();

        $this->redirect($route !== null ? route($route) : route('home'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
