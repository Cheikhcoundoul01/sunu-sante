<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAccountActive;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

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
        // Ré-applique les contrôles d'accès sur les requêtes Livewire
        // (endpoint /livewire/update), qui ne rejouent pas les middlewares de route par défaut.
        Livewire::addPersistentMiddleware(EnsureAccountActive::class);
        Livewire::addPersistentMiddleware(RoleMiddleware::class);
        Livewire::addPersistentMiddleware(PermissionMiddleware::class);
        Livewire::addPersistentMiddleware(RoleOrPermissionMiddleware::class);
    }
}
