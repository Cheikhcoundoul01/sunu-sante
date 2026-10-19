@php
    $user = auth()->user();
    $salutation = (int) now()->format('G') < 18 ? 'Bonjour' : 'Bonsoir';

    // Nom affiché : masque le suffixe de démonstration « (Agent Démo) » s'il est présent.
    $nomAffiche = preg_replace('/\s*\(Agent Démo\)\s*$/u', '', trim($user->name)) ?? trim($user->name);
    if ($nomAffiche === '') {
        $nomAffiche = '';
    }

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

    // Service : déterminé uniquement à partir du compte connecté,
    // comme dans App\Livewire\Accueil\FileDuJour (auth()->user()->service_id).
    $serviceId = $user->service_id;

    $entrees = $serviceId
        ? app(\App\Services\FileAttenteService::class)->getFileDuJour($serviceId)
        : collect();

    $comptes = $entrees->countBy('statut');

    $statistiques = [
        [
            'libelle' => 'En attente',
            'valeur' => (int) $comptes->get(\App\Models\FileEntree::STATUT_EN_ATTENTE, 0),
        ],
        [
            'libelle' => 'En cours',
            'valeur' => (int) $comptes->get(\App\Models\FileEntree::STATUT_EN_COURS, 0),
        ],
        [
            'libelle' => 'Terminés',
            'valeur' => (int) $comptes->get(\App\Models\FileEntree::STATUT_TERMINE, 0),
        ],
    ];

    $carte = [
        'titre' => 'File du jour',
        'route' => 'accueil.file',
        'description' => "Suivez et gérez la file d'attente du jour.",
        'icone' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z',
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
                        <p class="break-words text-sm font-semibold leading-tight text-slate-900">{{ $nomAffiche !== '' ? $nomAffiche : "Agent d'accueil" }}</p>
                        <p class="text-xs text-slate-500">Agent d'accueil</p>
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
        <section class="rounded-2xl bg-gradient-to-br from-[#0F766E] to-[#0B3B38] px-5 py-6 text-white shadow-sm sm:px-8 sm:py-7" aria-label="Bienvenue dans votre espace agent d'accueil">
            <p class="text-xs font-medium uppercase tracking-wide text-[#99F6E4]">
                {{ now()->locale('fr')->translatedFormat('l j F Y') }}
            </p>
            <h1 class="mt-2 text-xl font-semibold tracking-tight sm:text-2xl">{{ $salutationComplete }}</h1>
            <p class="mt-1 text-sm text-teal-50">Espace agent d'accueil</p>
        </section>

        {{-- Accès rapide --}}
        <section aria-label="Accès rapide">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">Accès rapide</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
            </div>
        </section>

        {{-- File d'attente aujourd'hui --}}
        <section aria-label="File d'attente aujourd'hui">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">File d'attente aujourd'hui</h2>

            @if ($serviceId)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ($statistiques as $stat)
                        <a href="{{ route('accueil.file') }}"
                           class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                            <span class="text-sm font-medium leading-5 text-slate-600">{{ $stat['libelle'] }}</span>
                            <span class="mt-3 text-3xl font-semibold text-[#0F766E]">{{ $stat['valeur'] }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm leading-5 text-slate-600">
                        Le service de votre compte n'est pas défini : les statistiques de la file ne peuvent pas être calculées.
                    </p>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
