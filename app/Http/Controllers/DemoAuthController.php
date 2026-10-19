<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DemoAuthController extends Controller
{
    /**
     * Connexion directe en mode démo pour un rôle donné.
     */
    public function login(Request $request, string $role)
    {
        if (! config('demo.enabled')) {
            abort(404);
        }

        $accounts = config('demo.accounts');
        $key = strtolower($role);

        if (! isset($accounts[$key])) {
            abort(404);
        }

        $account = $accounts[$key];
        $user = User::where('email', $account['email'])->first();

        if (! $user) {
            abort(404, 'Compte de démonstration non trouvé.');
        }

        if (! $user->is_demo) {
            abort(403, 'Ce compte n\'est pas un compte de démonstration.');
        }

        Auth::login($user);
        Session::regenerate();

        $route = $user->routeSelonRole();
        if ($route) {
            return redirect()->route($route);
        }

        abort(403, 'Rôle inconnu pour ce compte de démonstration.');
    }
}