<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAccountActive;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ré-applique le contrôle du statut de compte sur les requêtes Livewire
        // (endpoint /livewire/update), qui ne rejouent pas les middlewares de route par défaut.
        Livewire::addPersistentMiddleware(EnsureAccountActive::class);
    }
}
