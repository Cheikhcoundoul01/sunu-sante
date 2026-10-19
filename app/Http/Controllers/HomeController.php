<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Accueil public (visiteurs) ou redirection selon le rôle.
     */
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return $this->redirectByRole();
        }

        return view('accueil');
    }

    /**
     * Rediriger l'utilisateur connecté vers son espace selon son rôle.
     */
    public function redirectByRole(): RedirectResponse
    {
        $route = auth()->user()->routeSelonRole();

        if ($route === null) {
            abort(403);
        }

        return redirect()->route($route);
    }
}
