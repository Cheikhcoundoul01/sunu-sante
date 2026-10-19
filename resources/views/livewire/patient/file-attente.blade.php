@php
    $btnPrimaire = 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none';
    $btnSecondaire = 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none';
@endphp

<div class="w-full px-4 py-6 sm:px-6 lg:px-8" wire:poll.5s>
    <div class="mx-auto w-full max-w-6xl">

        {{-- En-tête --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Ma file d'attente</h1>
                <p class="mt-1 text-sm text-slate-600">Suivez votre position dans la file d'attente du jour.</p>
            </div>
            <span class="inline-flex shrink-0 items-center gap-2 self-start rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 sm:self-auto">
                <span class="relative flex h-2 w-2" aria-hidden="true">
                    <span class="absolute inline-flex h-full w-full animate-pulse rounded-full bg-emerald-400 opacity-70 motion-reduce:animate-none"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-600"></span>
                </span>
                Actualisation automatique
            </span>
        </header>

        @if ($situation === 'erreur')
            {{-- D — Erreur technique --}}
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" role="alert">
                <div class="flex flex-col items-center px-6 py-10 text-center sm:py-12">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-700">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900 sm:text-xl">Une erreur est survenue</h2>
                    <p class="mt-1.5 max-w-md text-sm leading-6 text-slate-600">Réessayez dans un instant.</p>
                </div>
            </div>
        @elseif ($situation === 'entree')
            @php
                $statut = $entree->statut;
                $badge = match ($statut) {
                    \App\Models\FileEntree::STATUT_EN_ATTENTE => [
                        'label' => 'En attente',
                        'classes' => 'bg-amber-100 text-amber-800',
                        'd' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    \App\Models\FileEntree::STATUT_EN_COURS => [
                        'label' => 'En cours',
                        'classes' => 'bg-blue-100 text-blue-800',
                        'd' => 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z',
                    ],
                    \App\Models\FileEntree::STATUT_TERMINE => [
                        'label' => 'Terminé',
                        'classes' => 'bg-slate-100 text-slate-700',
                        'd' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    \App\Models\FileEntree::STATUT_ABSENT => [
                        'label' => 'Absent',
                        'classes' => 'bg-purple-100 text-purple-800',
                        'd' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 1115 0v.75H4.5v-.75z',
                    ],
                    default => [
                        'label' => $statut,
                        'classes' => 'bg-slate-100 text-slate-700',
                        'd' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                };
                $avecPosition = in_array($statut, [\App\Models\FileEntree::STATUT_EN_ATTENTE, \App\Models\FileEntree::STATUT_EN_COURS], true);
                $enCours = $statut === \App\Models\FileEntree::STATUT_EN_COURS;
                $devant = max((int) $entree->position - 1, 0);
                $heureRdv = $entree->rendezVous?->heure_debut
                    ? \Carbon\Carbon::parse($entree->rendezVous->heure_debut)->format('H:i')
                    : null;
                $medecinNom = $entree->rendezVous?->medecin?->nomAffiche;
                $serviceNom = $entree->service?->nom;

                // Progression réelle : En attente → En cours → Terminé.
                // « Absent » est une branche (retour possible en attente), pas une étape de progression.
                $etapesFile = [
                    ['statut' => \App\Models\FileEntree::STATUT_EN_ATTENTE, 'label' => 'En attente'],
                    ['statut' => \App\Models\FileEntree::STATUT_EN_COURS, 'label' => 'En cours'],
                    ['statut' => \App\Models\FileEntree::STATUT_TERMINE, 'label' => 'Terminé'],
                ];
                $indexStatut = array_search($statut, array_column($etapesFile, 'statut'), true);
                $aProgression = $indexStatut !== false;
            @endphp

            {{-- Carte principale de suivi --}}
            <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="position-heading">
                <h2 id="position-heading" class="sr-only">Votre position dans la file d'attente</h2>

                {{-- Zone focale : position --}}
                <div class="bg-gradient-to-b from-teal-50 to-white px-6 py-9 sm:py-12">
                    <div class="flex flex-col items-center text-center">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset ring-black/5 transition-colors duration-200 motion-reduce:transition-none {{ $badge['classes'] }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['d'] }}" />
                            </svg>
                            {{ $badge['label'] }}
                        </span>

                        @if ($avecPosition)
                            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-teal-700">Votre position</p>
                            <div class="mt-3 flex h-36 w-36 items-center justify-center rounded-full bg-white/70 shadow-sm ring-1 ring-inset ring-teal-100 transition-all duration-200 motion-reduce:transition-none sm:h-44 sm:w-44">
                                <span class="text-[64px] font-bold leading-none tracking-tight text-teal-700 tabular-nums sm:text-7xl lg:text-8xl">{{ $entree->position }}</span>
                            </div>
                            <p class="mt-4 text-base font-medium text-slate-700">dans la file</p>
                        @else
                            <div class="mt-6 flex h-36 w-36 items-center justify-center rounded-full bg-white/70 shadow-sm ring-1 ring-inset transition-colors duration-200 motion-reduce:transition-none sm:h-44 sm:w-44
                                {{ $statut === \App\Models\FileEntree::STATUT_TERMINE ? 'text-teal-700 ring-teal-100' : 'text-purple-700 ring-purple-100' }}">
                                @if ($statut === \App\Models\FileEntree::STATUT_TERMINE)
                                    <svg class="h-14 w-14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0" />
                                    </svg>
                                @else
                                    <svg class="h-14 w-14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 1115 0v.75H4.5v-.75z" />
                                    </svg>
                                @endif
                            </div>
                            <p class="mt-4 max-w-md text-base font-medium text-slate-700">
                                @if ($statut === \App\Models\FileEntree::STATUT_TERMINE)
                                    Votre consultation est terminée.
                                @else
                                    Vous avez été marqué absent. Rapprochez-vous de l'accueil.
                                @endif
                            </p>
                        @endif

                        {{-- Message d'appel : uniquement lorsque le patient est réellement en cours --}}
                        @if ($enCours)
                            <div class="mt-6 flex w-full max-w-md items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-left" role="status">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-blue-900">C'est votre tour</p>
                                    <p class="mt-0.5 text-sm text-blue-800">En cours de consultation — présentez-vous auprès du service.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Étapes de progression (statuts réels uniquement) --}}
                @if ($aProgression)
                    <div class="border-t border-slate-100 px-6 py-6 sm:px-8">
                        <ol class="flex items-center" aria-label="Progression dans la file d'attente">
                            @foreach ($etapesFile as $i => $etape)
                                @php
                                    $fait = $i < $indexStatut || ($statut === \App\Models\FileEntree::STATUT_TERMINE && $i === $indexStatut);
                                    $courant = $i === $indexStatut && $statut !== \App\Models\FileEntree::STATUT_TERMINE;
                                @endphp
                                <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                    <div class="flex flex-col items-center">
                                        <span @class([
                                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-semibold transition-colors duration-200 motion-reduce:transition-none',
                                                'border-teal-700 bg-teal-700 text-white' => $fait,
                                                'border-blue-700 bg-blue-700 text-white ring-2 ring-blue-600/40 ring-offset-2' => $courant,
                                                'border-slate-200 bg-white text-slate-400' => ! $fait && ! $courant,
                                            ])
                                            @if ($courant) aria-current="step" @endif>
                                            @if ($fait)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @else
                                                {{ $i + 1 }}
                                            @endif
                                        </span>
                                        <span @class([
                                                'mt-2 text-center text-xs font-medium sm:text-sm',
                                                'text-teal-800' => $fait,
                                                'text-blue-800' => $courant,
                                                'text-slate-500' => ! $fait && ! $courant,
                                            ])>{{ $etape['label'] }}</span>
                                    </div>
                                    @unless ($loop->last)
                                        <span class="mx-2 mb-6 h-0.5 flex-1 rounded-full transition-colors duration-200 motion-reduce:transition-none sm:mx-3 {{ $i < $indexStatut ? 'bg-teal-600' : 'bg-slate-200' }}"
                                              aria-hidden="true"></span>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                {{-- Grille des informations secondaires --}}
                @if ($serviceNom || $medecinNom || $heureRdv || $avecPosition)
                    <dl class="grid grid-cols-1 gap-px border-t border-slate-100 bg-slate-100 sm:grid-cols-2">
                        @if ($serviceNom)
                            <div class="flex items-start gap-3 bg-white p-4 sm:p-5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Service</dt>
                                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $serviceNom }}</dd>
                                </div>
                            </div>
                        @endif

                        @if ($medecinNom)
                            <div class="flex items-start gap-3 bg-white p-4 sm:p-5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 1115 0v.75H4.5v-.75z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Médecin</dt>
                                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $medecinNom }}</dd>
                                </div>
                            </div>
                        @endif

                        @if ($heureRdv)
                            <div class="flex items-start gap-3 bg-white p-4 sm:p-5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Heure du rendez-vous</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $heureRdv }}</dd>
                                </div>
                            </div>
                        @endif

                        @if ($avecPosition)
                            <div class="flex items-start gap-3 bg-white p-4 sm:p-5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Personnes devant</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $devant }} personne(s) devant vous</dd>
                                </div>
                            </div>
                        @endif
                    </dl>
                @endif
            </section>
        @elseif ($situation === 'rdv_sans_entree')
            {{-- B — Rendez-vous aujourd'hui, file pas encore générée --}}
            @php
                $heureB = $rdvDuJour?->heure_debut
                    ? \Carbon\Carbon::parse($rdvDuJour->heure_debut)->format('H:i')
                    : null;
            @endphp
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col items-center px-6 py-10 text-center sm:py-12">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900 sm:text-xl">Votre rendez-vous est prévu aujourd'hui</h2>
                    <p class="mt-1.5 max-w-md text-sm leading-6 text-slate-600">
                        @if ($heureB)
                            Votre rendez-vous est prévu à {{ $heureB }}. La file d'attente sera générée par l'accueil.
                        @else
                            La file d'attente sera générée par l'accueil.
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('patient.rdv.index') }}" class="{{ $btnSecondaire }}">
                            Voir mes rendez-vous
                        </a>
                    </div>
                </div>
            </div>
        @elseif ($situation === 'indetermine')
            {{-- C — Situation indéterminée (avertissement) --}}
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col items-center px-6 py-10 text-center sm:py-12">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900 sm:text-xl">Votre situation n'a pas pu être déterminée</h2>
                    <p class="mt-1.5 max-w-md text-sm leading-6 text-slate-600">Veuillez contacter l'accueil.</p>
                    <div class="mt-6">
                        <a href="{{ route('patient.rdv.index') }}" class="{{ $btnSecondaire }}">
                            Voir mes rendez-vous
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- A — Aucun rendez-vous aujourd'hui --}}
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col items-center px-6 py-12 text-center sm:py-14">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900 sm:text-xl">Aucun rendez-vous aujourd'hui</h2>
                    <p class="mt-1.5 max-w-md text-sm leading-6 text-slate-600">Vous n'avez pas de rendez-vous prévu aujourd'hui.</p>

                    <div class="mt-7 flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:items-center">
                        <a href="{{ route('patient.rdv.create') }}" class="{{ $btnPrimaire }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Prendre un rendez-vous
                        </a>
                        <a href="{{ route('patient.rdv.index') }}" class="{{ $btnSecondaire }}">
                            Voir mes rendez-vous
                        </a>
                    </div>

                    {{-- Prochain rendez-vous à venir, un autre jour (si réellement présent) --}}
                    @if ($prochainRdv)
                        @php
                            $dateProchain = \Carbon\Carbon::parse($prochainRdv->date)->locale('fr')->translatedFormat('l j F Y');
                            $heureProchain = $prochainRdv->heure_debut
                                ? \Carbon\Carbon::parse($prochainRdv->heure_debut)->format('H:i')
                                : null;
                        @endphp
                        <div class="mt-8 flex w-full flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Prochain rendez-vous</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ ucfirst($dateProchain) }}@if ($heureProchain) à {{ $heureProchain }}@endif
                                        @if ($prochainRdv->service?->nom)
                                            <span class="font-normal text-slate-600">· {{ $prochainRdv->service->nom }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('patient.rdv.index') }}"
                               class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-teal-700 transition-colors duration-150 hover:text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                                Mes rendez-vous
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
