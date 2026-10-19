@php
    $user = auth()->user();
    $salutation = (int) now()->format('G') < 18 ? 'Bonjour' : 'Bonsoir';

    // Nom affiché : masque le suffixe de démonstration « (Admin Démo) » s'il est présent.
    $nomAffiche = preg_replace('/\s*\(Admin Démo\)\s*$/u', '', trim($user->name)) ?? trim($user->name);

    $mots = preg_split('/\s+/u', $nomAffiche, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $prenom = $mots[0] ?? '';

    $initiales = '';
    foreach (array_slice($mots, 0, 2) as $mot) {
        $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
    }
    if ($initiales === '') {
        $initiales = 'A';
    }

    $salutationComplete = $prenom !== '' ? "$salutation, $prenom." : $salutation . '.';

    // Chiffres clés globaux (ensemble de l'établissement).
    $nbServices = \App\Models\Service::count();

    $nbPersonnelActif = \App\Models\User::role(\App\Models\User::ROLES_PERSONNEL)
        ->where('statut_compte', 'Actif')
        ->count();

    $nbPatientsInscrits = \App\Models\User::role('Patient')->count();

    // Rendez-vous du jour : uniquement les rendez-vous actifs (Demandé + Validé),
    // les rendez-vous annulés ne sont pas comptés.
    $nbRdvJour = \App\Models\RendezVous::whereDate('date', now()->toDateString())
        ->whereIn('statut', \App\Models\RendezVous::statutsActifs())
        ->count();

    $cartes = [
        [
            'titre' => 'Statistiques globales',
            'route' => 'admin.statistiques',
            'description' => "Vue d'ensemble de l'activité de l'établissement.",
            'icone' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        ],
        [
            'titre' => 'Gestion des services',
            'route' => 'admin.services',
            'description' => "Créez et gérez les services de l'établissement.",
            'icone' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        ],
        [
            'titre' => 'Gestion du personnel',
            'route' => 'admin.personnel',
            'description' => "Créez et gérez les comptes du personnel.",
            'icone' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        ],
    ];

    $chiffres = [
        ['libelle' => 'Services', 'valeur' => $nbServices, 'route' => 'admin.services'],
        ['libelle' => 'Personnel actif', 'valeur' => $nbPersonnelActif, 'route' => 'admin.personnel'],
        ['libelle' => 'Patients inscrits', 'valeur' => $nbPatientsInscrits, 'route' => null],
        ['libelle' => 'Rendez-vous du jour', 'valeur' => $nbRdvJour, 'route' => 'admin.statistiques'],
    ];
@endphp

<x-app-layout>
    {{-- Barre de navigation administrateur (composant partagé, non modifié) --}}
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
        <x-admin.nav />
    </div>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-6 sm:px-6 sm:py-8">

        {{-- Bannière de bienvenue : fine, dégradé vert pétrole, date discrète, sans cercle décoratif --}}
        <section class="rounded-2xl bg-gradient-to-br from-[#0F766E] to-[#0B3B38] px-5 py-6 text-white shadow-sm sm:px-8 sm:py-7" aria-label="Bienvenue dans votre espace administrateur">
            <div class="flex items-center gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/15 text-sm font-semibold text-white ring-1 ring-inset ring-white/25" aria-hidden="true">{{ $initiales }}</span>
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-[#99F6E4]">
                        {{ now()->locale('fr')->translatedFormat('l j F Y') }}
                    </p>
                    <h1 class="mt-1 text-xl font-semibold tracking-tight sm:text-2xl">{{ $salutationComplete }}</h1>
                    <p class="mt-1 text-sm text-teal-50">Espace administrateur</p>
                </div>
            </div>
        </section>

        {{-- Accès rapides --}}
        <section aria-label="Accès rapide">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">Accès rapides</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cartes as $carte)
                    <a href="{{ route($carte['route']) }}"
                       class="group flex min-w-0 items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-[#0F766E]" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="{{ $carte['icone'] }}" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-base font-semibold text-slate-900 transition-colors duration-150 group-hover:text-[#0F766E]">{{ $carte['titre'] }}</span>
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-[#0F766E] motion-reduce:transform-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </span>
                            <span class="mt-1 block text-sm leading-5 text-slate-600">{{ $carte['description'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Chiffres clés --}}
        <section aria-label="Chiffres clés">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">Chiffres clés</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($chiffres as $chiffre)
                    @if ($chiffre['route'])
                        <a href="{{ route($chiffre['route']) }}"
                           class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                            <span class="text-sm font-medium leading-5 text-slate-600">{{ $chiffre['libelle'] }}</span>
                            <span class="mt-3 text-3xl font-semibold text-[#0F766E]">{{ $chiffre['valeur'] }}</span>
                        </a>
                    @else
                        <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <span class="text-sm font-medium leading-5 text-slate-600">{{ $chiffre['libelle'] }}</span>
                            <span class="mt-3 text-3xl font-semibold text-[#0F766E]">{{ $chiffre['valeur'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
