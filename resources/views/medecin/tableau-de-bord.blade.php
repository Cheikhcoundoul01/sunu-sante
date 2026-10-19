@php
    $user = auth()->user();
    $salutation = (int) now()->format('G') < 18 ? 'Bonjour' : 'Bonsoir';

    // Nom affiché : masque le suffixe de démonstration « (Médecin Démo) » s'il est présent.
    $brut = trim($user->name);
    $nomSansSuffixe = trim(preg_replace('/\s*\(Médecin Démo\)\s*$/u', '', $brut) ?? $brut);
    if ($nomSansSuffixe === '') {
        $nomSansSuffixe = $brut;
    }

    // Titre et mots utiles (retire un éventuel « Dr. » pour les initiales et la salutation).
    $aTitre = false;
    $mots = [];
    foreach (preg_split('/\s+/u', $nomSansSuffixe, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $mot) {
        if (preg_match('/^Dr\.?$/u', $mot)) {
            $aTitre = true;
            continue;
        }
        $mots[] = $mot;
    }

    $initiales = '';
    foreach (array_slice($mots, 0, 2) as $mot) {
        $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
    }
    if ($initiales === '') {
        $initiales = 'M';
    }

    // « Dr. » → titre + nom de famille ; sinon prénom ; sinon aucune nomination.
    if (count($mots) >= 1) {
        $nomSalutation = $aTitre ? 'Dr. '.end($mots) : $mots[0];
    } else {
        $nomSalutation = '';
    }

    $salutationComplete = $nomSalutation !== ''
        ? $salutation.', '.$nomSalutation.'.'
        : $salutation.'.';

    // Statistiques limitées au médecin connecté.
    $medecinId = $user->id;
    $aujourdHui = now()->toDateString();
    $maintenant = now();

    $rdvDuJour = \App\Models\RendezVous::query()
        ->where('medecin_id', $medecinId)
        ->whereDate('date', $aujourdHui)
        ->whereIn('statut', \App\Models\RendezVous::statutsActifs())
        ->count();

    $prochainRdv = \App\Models\RendezVous::query()
        ->where('medecin_id', $medecinId)
        ->whereIn('statut', \App\Models\RendezVous::statutsActifs())
        ->whereDate('date', '>=', $aujourdHui)
        ->with('patient')
        ->orderBy('date')
        ->orderBy('heure_debut')
        ->get()
        ->first(function (\App\Models\RendezVous $rdv) use ($maintenant) {
            $moment = \Carbon\Carbon::parse($rdv->date->toDateString().' '.$rdv->heure_debut);

            return $moment->greaterThanOrEqualTo($maintenant);
        });

    // « Terminé » est un statut fiable posé par l'agent d'accueil (file d'attente),
    // rattaché au médecin via le rendez-vous. Jamais déduit de l'heure.
    $rdvTermines = \App\Models\FileEntree::query()
        ->where('statut', \App\Models\FileEntree::STATUT_TERMINE)
        ->whereDate('date', $aujourdHui)
        ->whereHas('rendezVous', function ($q) use ($medecinId) {
            $q->where('medecin_id', $medecinId);
        })
        ->count();

    $cartes = [
        [
            'titre' => 'Mon planning',
            'route' => 'medecin.planning',
            'description' => "Visualisez vos créneaux libres et vos rendez-vous du jour.",
            'icone' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        ],
        [
            'titre' => 'Patients du jour',
            'route' => 'medecin.patients-du-jour',
            'description' => "Consultez vos patients du jour et leur position dans la file d'attente.",
            'icone' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
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
                        <p class="break-words text-sm font-semibold leading-tight text-slate-900">{{ $nomSansSuffixe }}</p>
                        <p class="text-xs text-slate-500">Médecin</p>
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
        <section class="rounded-2xl bg-gradient-to-br from-[#0F766E] to-[#0B3B38] px-5 py-6 text-white shadow-sm sm:px-8 sm:py-7" aria-label="Bienvenue dans votre espace médecin">
            <p class="text-xs font-medium uppercase tracking-wide text-[#99F6E4]">
                {{ now()->locale('fr')->translatedFormat('l j F Y') }}
            </p>
            <h1 class="mt-2 text-xl font-semibold tracking-tight sm:text-2xl">
                {{ $salutationComplete }}
            </h1>
            <p class="mt-1 text-sm text-teal-50">Espace médecin</p>
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

        {{-- Ma journée --}}
        <section aria-label="Ma journée">
            <h2 class="mb-4 text-lg font-semibold tracking-tight text-slate-900">Ma journée</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <a href="{{ route('medecin.planning') }}"
                   class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                    <span class="text-sm font-medium leading-5 text-slate-600">Rendez-vous du jour</span>
                    <span class="mt-3 text-3xl font-semibold text-[#0F766E]">{{ $rdvDuJour }}</span>
                </a>

                <a href="{{ route('medecin.planning') }}"
                   class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                    <span class="text-sm font-medium leading-5 text-slate-600">Prochain rendez-vous</span>
                    @if ($prochainRdv)
                        <span class="mt-3">
                            <span class="block text-3xl font-semibold text-[#0F766E]">{{ \Carbon\Carbon::parse($prochainRdv->heure_debut)->format('H:i') }}</span>
                            <span class="mt-1 block break-words text-sm text-slate-600">{{ $prochainRdv->patient->name ?? '' }}</span>
                        </span>
                    @else
                        <span class="mt-3 block text-sm font-medium text-slate-500">Aucun rendez-vous à venir</span>
                    @endif
                </a>

                <a href="{{ route('medecin.patients-du-jour') }}"
                   class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transform-none motion-reduce:transition-none">
                    <span class="text-sm font-medium leading-5 text-slate-600">Rendez-vous terminés aujourd'hui</span>
                    <span class="mt-3 text-3xl font-semibold text-[#0F766E]">{{ $rdvTermines }}</span>
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
