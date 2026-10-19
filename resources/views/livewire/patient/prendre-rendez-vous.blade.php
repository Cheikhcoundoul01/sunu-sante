<div class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-6xl">

        {{-- En-tête --}}
        <header>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Prendre un rendez-vous</h1>
            <p class="mt-1 text-sm text-slate-600">
                Choisissez votre service, votre médecin puis un créneau. Votre demande sera ensuite validée par une secrétaire.
            </p>
        </header>

        @php
            $serviceNom = $services->firstWhere('id', $serviceId)?->nom;
            $medecinNom = $medecins->firstWhere('id', $medecinId)?->nomAffiche;
            $dateAffichee = $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : null;
            $heureAffichee = $heureDebut ? \Carbon\Carbon::parse($heureDebut)->format('H:i') : null;

            $serviceChoisi = $serviceId !== null;
            $medecinChoisi = $medecinId !== null;

            $courant = $serviceId === null ? 0 : ($medecinId === null ? 1 : ($heureDebut === null ? 2 : 3));
            $etapes = [
                ['label' => 'Service', 'done' => $serviceChoisi],
                ['label' => 'Médecin', 'done' => $medecinChoisi],
                ['label' => 'Date et créneau', 'done' => $heureDebut !== null],
                ['label' => 'Confirmation', 'done' => false],
            ];

            $tousCreneaux = collect($creneauxDisponibles)
                ->flatMap(fn ($d) => $d['creneaux'])
                ->unique('debut')
                ->sortBy('debut')
                ->values();
            $groupesCreneaux = [
                ['label' => 'Matin', 'items' => $tousCreneaux->filter(fn ($c) => $c['debut'] < '12:00')->values()],
                ['label' => 'Après-midi', 'items' => $tousCreneaux->filter(fn ($c) => $c['debut'] >= '12:00')->values()],
            ];

            $champ = 'block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm transition-colors placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400';
            $iconeChamp = 'pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400';
        @endphp

        {{-- Progression --}}
        <div class="mt-5">
            <ol class="flex items-center" aria-label="Étapes de la prise de rendez-vous">
                @foreach ($etapes as $i => $etape)
                    <li class="flex items-center">
                        <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-sm font-semibold',
                                'border-teal-700 bg-teal-700 text-white' => $etape['done'],
                                'border-teal-700 bg-teal-700 text-white ring-2 ring-teal-600/40 ring-offset-2' => ! $etape['done'] && $i === $courant,
                                'border-slate-200 bg-white text-slate-400' => ! $etape['done'] && $i !== $courant,
                            ])
                            @if ($i === $courant && ! $etape['done']) aria-current="step" @endif>
                            @if ($etape['done'])
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span @class([
                                'ml-2 hidden text-sm font-medium sm:inline',
                                'text-teal-800' => $i === $courant,
                                'text-slate-700' => $i !== $courant && $etape['done'],
                                'text-slate-400' => $i !== $courant && ! $etape['done'],
                            ])>{{ $etape['label'] }}</span>
                    </li>
                    @unless ($loop->last)
                        <li aria-hidden="true" @class([
                                'mx-2 h-0.5 flex-1 rounded-full sm:mx-4',
                                'bg-teal-600' => $etape['done'],
                                'bg-slate-200' => ! $etape['done'],
                            ])></li>
                    @endunless
                @endforeach
            </ol>
            <p class="mt-2 text-sm text-slate-600 sm:hidden">
                Étape {{ $courant + 1 }} sur {{ count($etapes) }} · <span class="font-medium text-slate-700">{{ $etapes[$courant]['label'] }}</span>
            </p>
        </div>

        {{-- Messages --}}
        @if ($messageSucces)
            <div class="mt-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    {{ $messageSucces }}
                    @if ($rdvCree)
                        <p class="mt-1 font-medium">Jërëjëf !</p>
                    @endif
                </div>
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

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-3">

            {{-- Formulaire --}}
            <x-card class="min-w-0 p-5 sm:p-6 lg:col-span-2">
                <div class="space-y-5">

                    {{-- Service --}}
                    <div>
                        <x-label for="serviceId">Service</x-label>
                        <div class="relative mt-1">
                            <span class="{{ $iconeChamp }}" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                </svg>
                            </span>
                            <select id="serviceId" wire:model.live="serviceId" class="{{ $champ }} pr-8">
                                <option value="">— Choisir un service —</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('serviceId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Médecin --}}
                    <div>
                        <x-label for="medecinId">Médecin</x-label>
                        <div class="relative mt-1">
                            <span class="{{ $iconeChamp }}" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <select id="medecinId" wire:model.live="medecinId" @disabled(! $serviceChoisi || $medecins->isEmpty()) class="{{ $champ }} pr-8">
                                <option value="">
                                    @if (! $serviceChoisi)
                                        Sélectionnez d'abord un service
                                    @elseif ($medecins->isEmpty())
                                        Aucun médecin disponible dans ce service
                                    @else
                                        — Choisir un médecin —
                                    @endif
                                </option>
                                @foreach ($medecins as $medecin)
                                    <option value="{{ $medecin->id }}">{{ $medecin->nomAffiche }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('medecinId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Date --}}
                    <div>
                        <x-label for="date">Date</x-label>
                        <div class="relative mt-1">
                            <span class="{{ $iconeChamp }}" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input type="date" id="date" wire:model.live="date" @disabled(! $medecinChoisi)
                                   min="{{ \Carbon\Carbon::now()->addDay()->toDateString() }}"
                                   max="{{ \Carbon\Carbon::now()->addDays(\App\Models\Parametre::horizonReservationJours())->toDateString() }}"
                                   class="{{ $champ }}">
                        </div>
                        @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Créneaux --}}
                    <div>
                        <x-label>Créneaux disponibles</x-label>
                        @if ($tousCreneaux->isNotEmpty())
                            <div class="mt-2 space-y-4">
                                @foreach ($groupesCreneaux as $groupe)
                                    @if ($groupe['items']->isNotEmpty())
                                        <div>
                                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $groupe['label'] }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($groupe['items'] as $creneau)
                                                    @php $estSelectionne = $heureDebut === $creneau['debut']; @endphp
                                                    <button type="button"
                                                            wire:click="setHeureDebut('{{ $creneau['debut'] }}')"
                                                            wire:loading.attr="disabled"
                                                            aria-pressed="{{ $estSelectionne ? 'true' : 'false' }}"
                                                            @class([
                                                                'inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:cursor-not-allowed disabled:opacity-50',
                                                                'border-teal-600 bg-teal-50 text-teal-800 ring-2 ring-teal-600/40' => $estSelectionne,
                                                                'border-slate-300 bg-white text-slate-700 hover:border-teal-400 hover:bg-teal-50/60' => ! $estSelectionne,
                                                            ])>
                                                        @if ($estSelectionne)
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                        @endif
                                                        <span>{{ $creneau['debut'] }}<span class="font-normal text-slate-400">–</span>{{ $creneau['fin'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <input type="hidden" wire:model="heureDebut">
                        @else
                            <div class="mt-2 flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-500">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>
                                    @if ($medecinChoisi)
                                        Aucun créneau disponible ce jour, essayez une autre date.
                                    @else
                                        Sélectionnez un médecin et une date pour voir les créneaux.
                                    @endif
                                </p>
                            </div>
                        @endif
                        @error('heureDebut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Motif --}}
                    <div>
                        <x-label for="motif">Motif (optionnel)</x-label>
                        <div class="relative mt-1" x-data="{ n: {{ mb_strlen((string) $motif) }} }">
                            <span class="pointer-events-none absolute left-3 top-3 h-5 w-5 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </span>
                            <textarea id="motif" wire:model="motif" rows="2" maxlength="500" x-on:input="n = $event.target.value.length"
                                      placeholder="Ex : consultation de routine, douleur…"
                                      class="block w-full resize-none rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm transition-colors placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30"></textarea>
                            <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                                <span>Précisions facultatives.</span>
                                <span aria-hidden="true"><span x-text="n">0</span>/500</span>
                            </div>
                        </div>
                        @error('motif') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Action --}}
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <button type="button"
                            wire:click="reserver"
                            wire:loading.attr="disabled"
                            wire:target="reserver"
                            @disabled($heureDebut === null)
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:pointer-events-none disabled:opacity-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span wire:loading.remove wire:target="reserver">Confirmer le rendez-vous</span>
                        <span wire:loading wire:target="reserver">Enregistrement…</span>
                    </button>
                    @if ($heureDebut === null)
                        <p class="mt-2 text-center text-xs text-slate-500">Sélectionnez un créneau pour confirmer.</p>
                    @endif
                </div>
            </x-card>

            {{-- Résumé --}}
            <aside class="min-w-0 lg:sticky lg:top-24 lg:col-span-1" aria-labelledby="resume-heading">
                <x-card class="p-5">
                    <h2 id="resume-heading" class="text-base font-semibold text-slate-900">Votre rendez-vous</h2>
                    <dl class="mt-4 divide-y divide-slate-100 text-sm">
                        <div class="flex items-center justify-between gap-3 pb-3">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                </svg>
                                Service
                            </dt>
                            <dd class="max-w-[60%] truncate text-right {{ $serviceNom ? 'font-medium text-slate-900' : 'text-slate-400' }}">
                                {{ $serviceNom ?? 'À sélectionner' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Médecin
                            </dt>
                            <dd class="max-w-[60%] min-w-0 whitespace-normal break-words text-right {{ $medecinNom ? 'font-medium text-slate-900' : 'text-slate-400' }}">
                                {{ $medecinNom ?? 'À sélectionner' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Date
                            </dt>
                            <dd class="text-right {{ $dateAffichee ? 'font-medium text-slate-900' : 'text-slate-400' }}">
                                {{ $dateAffichee ?? 'À sélectionner' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-3">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Heure
                            </dt>
                            <dd class="text-right {{ $heureAffichee ? 'font-semibold text-teal-800' : 'text-slate-400' }}">
                                {{ $heureAffichee ?? 'À sélectionner' }}
                            </dd>
                        </div>
                    </dl>
                </x-card>
            </aside>
        </div>
    </div>
</div>
