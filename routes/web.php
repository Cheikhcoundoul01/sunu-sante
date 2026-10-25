<?php

use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\HomeController;
use App\Livewire\Accueil\FileDuJour;
use App\Livewire\Admin\JournalActivite as AdminJournalActivite;
use App\Livewire\Admin\Personnel as AdminPersonnel;
use App\Livewire\Admin\Services as AdminServices;
use App\Livewire\Admin\Statistiques as AdminStatistiques;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterPatient;
use App\Livewire\Major\Statistiques as MajorStatistiques;
use App\Livewire\Medecin\PatientsDuJour;
use App\Livewire\Medecin\Planning;
use App\Livewire\Patient\Dashboard;
use App\Livewire\Patient\FileAttente;
use App\Livewire\Patient\MesRendezVous;
use App\Livewire\Patient\MonProfil;
use App\Livewire\Patient\PrendreRendezVous;
use App\Livewire\Secretaire\Disponibilites;
use App\Livewire\Secretaire\Patients;
use App\Livewire\Secretaire\RendezVousSecretaire;
use App\Livewire\Secretaire\Salles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/accueil', [HomeController::class, 'index'])->name('accueil');

Route::get('/connexion', Login::class)->name('login');
Route::get('/inscription', RegisterPatient::class)->name('register.patient');

Route::get('/demande-envoyee', function () {
    return view('patient.demande-envoyee');
})->name('patient.demande-envoyee');

/*
|--------------------------------------------------------------------------
| Déconnexion
|--------------------------------------------------------------------------
*/
Route::post('/deconnexion', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Mode démonstration
|--------------------------------------------------------------------------
*/
Route::middleware(['demo'])
    ->prefix('demo')
    ->name('demo.')
    ->group(function () {
        Route::get('/login/{role}', [DemoAuthController::class, 'login'])
            ->name('login')
            ->where('role', 'patient|secretaire|accueil|medecin|major|admin');
    });

/*
|--------------------------------------------------------------------------
| Espace patient
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'account.active', 'role:Patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('/tableau-de-bord', Dashboard::class)
            ->name('dashboard');
        Route::get('/rendez-vous/prendre', PrendreRendezVous::class)
            ->name('rdv.create');
        Route::get('/rendez-vous', MesRendezVous::class)
            ->name('rdv.index');
        Route::get('/file', FileAttente::class)
            ->name('file');
        Route::get('/profil', MonProfil::class)
            ->name('profil');
        Route::get('/compte-en-attente', fn () => view('patient.compte-en-attente'))
            ->name('compte-en-attente');
        Route::get('/inscription-non-retenue', fn () => view('patient.inscription-non-retenue'))
            ->name('inscription-non-retenue');
    });

/*
|--------------------------------------------------------------------------
| Espaces du personnel (placeholders — étapes suivantes)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'account.active', 'role:Secrétaire de service'])
    ->prefix('secretaire')->name('secretaire.')
    ->group(function () {
        Route::get('/tableau-de-bord', fn () => view('secretaire.tableau-de-bord', ['titre' => 'Espace secrétaire']))
            ->name('dashboard');

        Route::middleware('permission:patients.view')
            ->get('/patients', Patients::class)
            ->name('patients');

        Route::middleware('permission:disponibilites.gerer')
            ->get('/disponibilites', Disponibilites::class)
            ->name('disponibilites');

        Route::middleware('permission:rdv.voir')
            ->get('/rendez-vous', RendezVousSecretaire::class)
            ->name('rendez-vous');

        Route::middleware('permission:salles.gerer')
            ->get('/salles', Salles::class)
            ->name('salles');
    });

Route::middleware(['auth', 'account.active', "role:Agent d'accueil"])
    ->prefix('accueil-personnel')->name('accueil.')
    ->group(function () {
        Route::get('/tableau-de-bord', fn () => view('accueil.tableau-de-bord', ['titre' => 'File du jour']))
            ->name('dashboard');

        Route::middleware('permission:file.voir')
            ->get('/file', FileDuJour::class)
            ->name('file');
    });

Route::middleware(['auth', 'account.active', 'role:Médecin'])
    ->prefix('medecin')->name('medecin.')
    ->group(function () {
        Route::get('/tableau-de-bord', fn () => view('medecin.tableau-de-bord', ['titre' => 'Espace médecin']))
            ->name('dashboard');

        Route::middleware('permission:rdv.voir')
            ->get('/planning', Planning::class)
            ->name('planning');

        Route::middleware('permission:file.voir')
            ->get('/patients-du-jour', PatientsDuJour::class)
            ->name('patients-du-jour');
    });

Route::middleware(['auth', 'account.active', 'role:Major de service'])
    ->prefix('major')->name('major.')
    ->group(function () {
        Route::get('/tableau-de-bord', fn () => view('major.tableau-de-bord', ['titre' => 'Statistiques du service']))
            ->name('dashboard');

        Route::middleware('permission:stats.service')
            ->get('/statistiques', MajorStatistiques::class)
            ->name('statistiques');
    });

Route::middleware(['auth', 'account.active', 'role:Administrateur'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/tableau-de-bord', fn () => view('admin.tableau-de-bord', ['titre' => 'Espace administrateur']))
            ->name('dashboard');

        Route::middleware('permission:stats.global')
            ->get('/statistiques', AdminStatistiques::class)
            ->name('statistiques');

        Route::middleware('permission:services.gerer')
            ->get('/services', AdminServices::class)
            ->name('services');

        Route::middleware('permission:users.gerer')
            ->get('/personnel', AdminPersonnel::class)
            ->name('personnel');

        Route::middleware('can:journal.voir')
            ->get('/journal', AdminJournalActivite::class)
            ->name('journal');
    });
