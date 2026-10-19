@php
    $user = auth()->user();
    $salut = (int) now()->format('G') < 18 ? 'Bonjour' : 'Bonsoir';

    // [rôle => liste de [libellé, nom de route, principal ?]]
    // Les routes ne sont résolues que pour les rôles de l'utilisateur.
    $groupes = [
        'Secrétaire de service' => [
            ['Gérer les patients', 'secretaire.patients', true],
            ['Gérer les disponibilités', 'secretaire.disponibilites', false],
            ['Gérer les rendez-vous', 'secretaire.rendez-vous', false],
            ['Gérer les salles', 'secretaire.salles', false],
        ],
        "Agent d'accueil" => [
            ['File du jour', 'accueil.file', true],
        ],
        'Médecin' => [
            ['Mon planning', 'medecin.planning', true],
            ['Patients du jour', 'medecin.patients-du-jour', false],
        ],
        'Major de service' => [
            ['Statistiques du service', 'major.statistiques', true],
        ],
        'Administrateur' => [
            ['Statistiques globales', 'admin.statistiques', true],
        ],
    ];
@endphp

<x-app-layout>
    @unless ($user->hasRole('Administrateur'))
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0F766E]">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#0F766E] text-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <span class="text-lg font-semibold text-[#115E59]">Sunu Santé</span>
                </a>
                <div class="flex items-center gap-3">
                    <span class="hidden max-w-[12rem] truncate text-sm font-medium text-slate-700 sm:inline">{{ $user->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex h-10 items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors hover:border-red-300 hover:bg-red-50 hover:text-red-700 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </header>
    @endunless

    @if ($user->hasRole('Administrateur'))
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
            <x-admin.nav />
        </div>
    @endif

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">

        {{-- Bandeau de bienvenue --}}
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0F766E] to-[#0B3B38] px-6 py-9 text-white shadow-sm sm:px-10">
            <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-white/10" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-sm font-medium uppercase tracking-wide text-[#99F6E4]">
                    {{ now()->locale('fr')->translatedFormat('l j F Y') }}
                </p>
                <h1 class="mt-2 text-3xl font-semibold sm:text-4xl">{{ $salut }}, {{ $user->name }}</h1>
                <p class="mt-2 text-base text-teal-50">{{ $titre ?? 'Espace' }}</p>
            </div>
        </section>

        {{-- Accès rapide, par rôle --}}
        @foreach ($groupes as $role => $liens)
            @if ($user->hasRole($role))
                <section class="mt-8" aria-label="Accès rapide">
                    <h2 class="mb-3 text-lg font-semibold text-[#115E59]">Accès rapide</h2>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($liens as [$label, $nomRoute, $principal])
                            <a href="{{ route($nomRoute) }}"
                               class="group flex min-h-[64px] items-center justify-between gap-3 rounded-2xl border px-5 py-4 text-base font-semibold shadow-sm transition hover:-translate-y-0.5 hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                                      {{ $principal ? 'border-[#0F766E] bg-[#0F766E] text-white hover:bg-[#115E59]' : 'border-slate-200 bg-white text-slate-800 hover:border-[#0F766E] hover:text-[#0F766E]' }}">
                                {{ $label }}
                                <svg class="h-5 w-5 shrink-0 transition-transform group-hover:translate-x-1 motion-reduce:transform-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</x-app-layout>