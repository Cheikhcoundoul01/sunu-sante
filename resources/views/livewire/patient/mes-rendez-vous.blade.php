<div class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-6xl">

        @php
            $maintenant = now('Africa/Dakar');
            $momentRdv = fn ($rdv) => \Carbon\Carbon::parse($rdv->date->format('Y-m-d').' '.$rdv->heure_debut, 'Africa/Dakar');
            $tsRdv = fn ($rdv) => $momentRdv($rdv)->getTimestamp();
            $estAVenir = fn ($rdv) => $rdv->estActif() && $momentRdv($rdv)->greaterThanOrEqualTo($maintenant);

            $aVenir = $rdvs->filter($estAVenir)->sortBy($tsRdv)->values();
            $passes = $rdvs->filter(fn ($rdv) => $rdv->estActif() && $momentRdv($rdv)->lessThan($maintenant))->sortByDesc($tsRdv)->values();
            $annules = $rdvs->filter(fn ($rdv) => $rdv->statut === \App\Models\RendezVous::STATUT_ANNULE)->sortByDesc($tsRdv)->values();

            $onglets = [
                ['cle' => 'a-venir', 'label' => 'À venir', 'count' => $aVenir->count()],
                ['cle' => 'passes', 'label' => 'Passés', 'count' => $passes->count()],
                ['cle' => 'annules', 'label' => 'Annulés', 'count' => $annules->count()],
            ];

            $liste = match ($onglet) {
                'passes' => $passes,
                'annules' => $annules,
                default => $aVenir,
            };

            $libelleVide = match ($onglet) {
                'passes' => 'Aucun rendez-vous passé',
                'annules' => 'Aucun rendez-vous annulé',
                default => 'Aucun rendez-vous à venir',
            };

            $badge = function ($rdv) use ($estAVenir) {
                if ($rdv->statut === \App\Models\RendezVous::STATUT_ANNULE) {
                    return ['label' => 'Annulé', 'classes' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200'];
                }
                if ($rdv->statut === \App\Models\RendezVous::STATUT_VALIDE) {
                    return ['label' => 'Validé', 'classes' => 'bg-green-50 text-green-800 ring-1 ring-inset ring-green-200'];
                }

                return $estAVenir($rdv)
                    ? ['label' => 'En attente de validation', 'classes' => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200']
                    : ['label' => 'Non confirmé', 'classes' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200'];
            };

            $aucunRdv = $rdvs->isEmpty();

            $champ = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30';
            $boutonPrincipal = 'inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50';
            $boutonSecondaire = 'inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]';
        @endphp

        {{-- En-tête --}}
        <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Mes rendez-vous</h1>
                <p class="mt-1 text-sm text-slate-600">
                    @if ($aVenir->isNotEmpty())
                        Vous avez {{ $aVenir->count() }} rendez-vous à venir.
                    @else
                        Gérez vos rendez-vous.
                    @endif
                </p>
            </div>

            @unless ($aucunRdv)
                <a href="{{ route('patient.rdv.create') }}" class="{{ $boutonPrincipal }} w-full shrink-0 sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Prendre un rendez-vous
                </a>
            @endunless
        </header>

        {{-- Messages --}}
        @if ($messageSucces)
            <div class="mt-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>{{ $messageSucces }}</p>
            </div>
        @endif

        @if ($messageErreur)
            <div class="mt-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p>{{ $messageErreur }}</p>
            </div>
        @endif

        @if ($aucunRdv)
            {{-- État vide global --}}
            <x-card class="mt-6 p-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 ring-1 ring-inset ring-teal-100">
                    <svg class="h-7 w-7 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">Aucun rendez-vous</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-600">
                    Vous n'avez pas encore pris de rendez-vous. Programmez votre première consultation dès maintenant.
                </p>
                <div class="mt-5">
                    <a href="{{ route('patient.rdv.create') }}" class="{{ $boutonPrincipal }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Prendre un rendez-vous
                    </a>
                </div>
            </x-card>
        @else
            {{-- Onglets --}}
            <div class="mt-5 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Filtrer les rendez-vous">
                @foreach ($onglets as $o)
                    <button type="button"
                            role="tab"
                            wire:click="setOnglet('{{ $o['cle'] }}')"
                            aria-selected="{{ $onglet === $o['cle'] ? 'true' : 'false' }}"
                            class="-mb-px inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                                   {{ $onglet === $o['cle']
                                       ? 'border-teal-700 text-teal-700'
                                       : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        {{ $o['label'] }}
                        <span class="rounded-full px-1.5 text-xs font-semibold {{ $onglet === $o['cle'] ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $o['count'] }}
                        </span>
                    </button>
                @endforeach
            </div>

            @if ($liste->isEmpty())
                {{-- Onglet vide --}}
                <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                    <p class="text-sm text-slate-500">{{ $libelleVide }}</p>
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($liste as $rdv)
                        @php
                            $b = $badge($rdv);
                            $dateRdv = \Carbon\Carbon::parse($rdv->date);
                        @endphp

                        <article wire:key="rdv-{{ $rdv->id }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                            @if ($editingId === $rdv->id)
                                {{-- Mode édition --}}
                                <form wire:submit.prevent="sauvegarderModification" class="space-y-4">
                                    <p class="text-sm font-semibold text-slate-900">Modifier le rendez-vous</p>

                                    <div>
                                        <x-label for="editMedecinId">Médecin</x-label>
                                        <select id="editMedecinId" wire:model="editMedecinId" class="{{ $champ }}">
                                            <option value="">— Choisir —</option>
                                            @foreach ($rdv->service->utilisateurs()->whereHas('roles', fn($q) => $q->where('name', 'Médecin'))->get() as $medecin)
                                                <option value="{{ $medecin->id }}">{{ $medecin->nomAffiche }}</option>
                                            @endforeach
                                        </select>
                                        @error('editMedecinId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <x-label for="editDate">Date</x-label>
                                        <input type="date" id="editDate" wire:model="editDate" class="{{ $champ }}" min="{{ \Carbon\Carbon::now()->addDay()->toDateString() }}" max="{{ \Carbon\Carbon::now()->addDays(\App\Models\Parametre::horizonReservationJours())->toDateString() }}">
                                        @error('editDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <x-label>Créneaux</x-label>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @php
                                                $creneaux = app(\App\Services\CreneauService::class)->creneauxLibresDuJour($editMedecinId ?? $rdv->medecin_id, \Carbon\Carbon::parse($editDate ?? $rdv->date), null);
                                            @endphp
                                            @foreach ($creneaux as $dispo)
                                                @foreach ($dispo['creneaux'] as $creneau)
                                                    <button type="button"
                                                            wire:click="setEditHeureDebut('{{ $creneau['debut'] }}')"
                                                            class="inline-flex min-h-[40px] items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                                                                   {{ $editHeureDebut === $creneau['debut']
                                                                       ? 'border-teal-600 bg-teal-50 text-teal-700'
                                                                       : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                                                        {{ $creneau['debut'] }}–{{ $creneau['fin'] }}
                                                    </button>
                                                @endforeach
                                            @endforeach
                                        </div>
                                        <input type="hidden" wire:model="editHeureDebut">
                                        @error('editHeureDebut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <x-label for="editMotif">Motif (optionnel)</x-label>
                                        <textarea id="editMotif" wire:model="editMotif" rows="2" maxlength="500" class="{{ $champ }}"></textarea>
                                        @error('editMotif') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex flex-col gap-2 pt-1 sm:flex-row">
                                        <button type="submit"
                                                wire:loading.attr="disabled"
                                                wire:target="sauvegarderModification"
                                                class="{{ $boutonPrincipal }} flex-1">
                                            <span wire:loading.remove wire:target="sauvegarderModification">Enregistrer</span>
                                            <span wire:loading wire:target="sauvegarderModification">Enregistrement…</span>
                                        </button>
                                        <button type="button"
                                                wire:click="annulerModification"
                                                class="{{ $boutonSecondaire }} flex-1">
                                            Annuler la modification
                                        </button>
                                    </div>
                                </form>
                            @else
                                {{-- Affichage normal --}}
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    {{-- Jour et mois --}}
                                    <div class="flex items-center gap-2 sm:w-16 sm:shrink-0 sm:flex-col sm:gap-0 sm:rounded-xl sm:border sm:border-slate-200 sm:bg-teal-50 sm:py-3">
                                        <span class="text-2xl font-semibold leading-none text-teal-800">
                                            {{ $dateRdv->locale('fr')->translatedFormat('d') }}
                                        </span>
                                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ $dateRdv->locale('fr')->translatedFormat('M') }}
                                        </span>
                                    </div>

                                    {{-- Heure, médecin, service, motif --}}
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ \Carbon\Carbon::parse($rdv->heure_debut)->format('H:i') }}
                                        </p>
                                        <h3 class="mt-0.5 break-words text-base font-medium text-slate-900">
                                            {{ $rdv->medecin->nomAffiche }}
                                        </h3>
                                        <p class="break-words text-sm text-slate-600">{{ $rdv->service->nom }}</p>
                                        @if ($rdv->motif)
                                            <p class="mt-1 break-words text-sm text-slate-500">Motif : {{ $rdv->motif }}</p>
                                        @endif
                                    </div>

                                    {{-- Statut et actions --}}
                                    <div class="flex flex-wrap items-center gap-2 sm:flex-col sm:items-end sm:shrink-0">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $b['classes'] }}">
                                            {{ $b['label'] }}
                                        </span>

                                        @if ($estAVenir($rdv))
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button"
                                                        wire:click="ouvrirModification({{ $rdv->id }})"
                                                        class="inline-flex items-center justify-center rounded-lg border border-teal-600 px-3 py-2 text-xs font-medium text-teal-700 transition-colors hover:bg-teal-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                                                    Modifier
                                                </button>
                                                <button type="button"
                                                        wire:click="annuler({{ $rdv->id }})"
                                                        wire:confirm="Voulez-vous vraiment annuler ce rendez-vous ? Cette action est définitive et libère le créneau."
                                                        wire:loading.attr="disabled"
                                                        wire:target="annuler"
                                                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B91C1C] disabled:opacity-50">
                                                    Annuler
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
