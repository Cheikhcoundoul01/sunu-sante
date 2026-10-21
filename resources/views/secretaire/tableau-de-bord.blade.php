@php
    $user = auth()->user();
    $salutation = (int) now()->format('G') < 18 ? 'Bonjour' : 'Bonsoir';

    // Nom affiché : masque le suffixe de démonstration « (Secrétaire Démo) » s'il est présent.
    $nomAffiche = preg_replace('/\s*\(Secrétaire Démo\)\s*$/u', '', trim($user->name)) ?? trim($user->name);
    if ($nomAffiche === '') {
        $nomAffiche = trim($user->name);
    }

    $prenom = explode(' ', $nomAffiche)[0] ?? $nomAffiche;

    $mots = preg_split('/\s+/u', $nomAffiche, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initiales = '';
    foreach (array_slice($mots, 0, 2) as $mot) {
        $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
    }
    if ($initiales === '') {
        $initiales = 'S';
    }

    $servicesAutorises = $user->servicesAutorisesIds();

    $patientsAValider = \App\Models\User::role('Patient')
        ->where('statut_compte', 'En attente')
        ->count();

    $rdvDuJour = empty($servicesAutorises)
        ? 0
        : \App\Models\RendezVous::whereIn('service_id', $servicesAutorises)
            ->whereDate('date', now()->toDateString())
            ->count();

    $rdvEnAttente = empty($servicesAutorises)
        ? 0
        : \App\Models\RendezVous::whereIn('service_id', $servicesAutorises)
            ->where('statut', \App\Models\RendezVous::STATUT_DEMANDE)
            ->count();

    $cartes = [
        [
            'titre' => 'Patients',
            'route' => 'secretaire.patients',
            'description' => "Validez les inscriptions des nouveaux patients de la plateforme.",
            'icone' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        ],
        [
            'titre' => 'Rendez-vous',
            'route' => 'secretaire.rendez-vous',
            'description' => "Validez ou rejetez les demandes de rendez-vous de votre service.",
            'icone' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        ],
        [
            'titre' => 'Disponibilités',
            'route' => 'secretaire.disponibilites',
            'description' => "Gérez les disponibilités des médecins de votre service.",
            'icone' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'titre' => 'Salles',
            'route' => 'secretaire.salles',
            'description' => "Gérez les salles d'intervention et leurs disponibilités.",
            'icone' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        ],
    ];

    $statistiques = [
        [
            'libelle' => 'Patients à valider',
            'valeur' => $patientsAValider,
            'route' => 'secretaire.patients',
        ],
        [
            'libelle' => 'Rendez-vous du jour',
            'valeur' => $rdvDuJour,
            'route' => 'secretaire.rendez-vous',
        ],
        [
            'libelle' => 'Rendez-vous en attente de validation',
            'valeur' => $rdvEnAttente,
            'route' => 'secretaire.rendez-vous',
        ],
    ];
@endphp

<x-app-layout>
    {{-- En-tête : avatar, nom sans suffixe, rôle et déconnexion --}}
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0F766E]">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#0F766E] text-white" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                </span>
                <span class="text-lg font-semibold text-[#115E59]">Sunu Santé</span>
            </a>

            <div class="flex items-center gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-teal-50 text-sm font-semibold text-[#115E59] ring-1 ring-inset ring-teal-100" aria-hidden="true">
                        {{ $initiales }}
                    </span>
                    <div class="min-w-0">
                        <p class="break-words text-sm font-semibold leading-tight text-slate-900">{{ $nomAffiche }}</p>
                        <p class="text-xs text-slate-500">Secrétaire</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex h-10 items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition duration-150 hover:border-red-300 hover:bg-red-50 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-6 sm:px-6 sm:py-8">

        {{-- Bannière de bienvenue : fine, dégradé vert pétrole, date discrète, sans cercle décoratif --}}
        <section class="rounded-2xl bg-gradient-to-br from-[#0F766E] to-[#0B3B38] px-5 py-6 text-white shadow-sm sm:px-8 sm:py-7" aria-label="Bienvenue dans votre espace secrétaire">
            <p class="text-xs font-medium uppercase tracking-wide text-[#99F6E4]">
                {{ now()->locale('fr')->translatedFormat('l j F Y') }}
            </p>
            <h1 class="mt-2 text-xl font-semibold tracking-tight sm:text-2xl">{{ $salutation }}, {{ $prenom }}</h1>
            <p class="mt-1 text-sm text-teal-50">Espace secrétaire</p>
        </section>

        {{-- Accès rapides --}}
        <section aria-label="Accès rapide">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">Accès rapides</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

        {{-- Utile pour la secrétaire --}}
        <section aria-label="Utile pour la secrétaire">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">Utile pour la secrétaire</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($statistiques as $stat)
                    <a href="{{ route($stat['route']) }}"
                       class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                        <span class="text-sm font-medium leading-5 text-slate-600">{{ $stat['libelle'] }}</span>
                        <span class="mt-3 text-3xl font-semibold text-[#0F766E]">{{ $stat['valeur'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
