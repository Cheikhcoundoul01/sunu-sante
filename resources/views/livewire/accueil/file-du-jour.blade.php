<div class="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6">
    <header class="mb-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-semibold tracking-tight text-[#0F172A] sm:text-2xl">File du jour</h1>
                <p class="mt-1 text-sm leading-6 text-[#475569]">
                    File d'attente du jour pour {{ $service ?: 'votre service' }} — {{ now()->locale('fr')->translatedFormat('l j F Y') }}.
                </p>
            </div>
            <button type="button"
                    wire:click="generer"
                    wire:loading.attr="disabled"
                    wire:target="generer"
                    class="inline-flex h-11 min-h-[44px] items-center justify-center gap-2 rounded-lg bg-[#0F766E] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 ease-out hover:bg-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none sm:self-start">
                <span wire:loading.remove wire:target="generer">Générer la file du jour</span>
                <span wire:loading wire:target="generer" class="flex items-center gap-2" aria-live="polite" aria-hidden="true">
                    <svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Génération...
                </span>
            </button>
        </div>
    </header>

    @if ($messageSucces)
        <div class="mb-4 rounded-lg border border-[#166534] bg-[#DCFCE7] px-4 py-3 text-sm text-[#166534] motion-reduce:transition-none" role="status">
            {{ $messageSucces }}
        </div>
    @endif

    @if ($messageErreur)
        <div class="mb-4 rounded-lg border border-[#991B1B] bg-[#FEE2E2] px-4 py-3 text-sm text-[#991B1B] motion-reduce:transition-none" role="alert">
            {{ $messageErreur }}
        </div>
    @endif

    @php
        $attente = $file->where('statut', 'En attente')->count();
        $encours = $file->where('statut', 'En cours')->count();
        $termines = $file->where('statut', 'Terminé')->count();
        $absents = $file->where('statut', 'Absent')->count();
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4" aria-label="Statistiques de la file d'attente">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-[#475569]">En attente</p>
            <p class="mt-2 text-2xl font-semibold text-[#0F172A] sm:text-3xl">{{ $attente }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-[#475569]">En cours</p>
            <p class="mt-2 text-2xl font-semibold text-[#0F172A] sm:text-3xl">{{ $encours }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-[#475569]">Terminés</p>
            <p class="mt-2 text-2xl font-semibold text-[#0F172A] sm:text-3xl">{{ $termines }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-[#475569]">Absents</p>
            <p class="mt-2 text-2xl font-semibold text-[#0F172A] sm:text-3xl">{{ $absents }}</p>
        </div>
    </div>

    @if ($file->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm sm:p-8">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#CCFBF1]" aria-hidden="true">
                <svg class="h-6 w-6 text-[#115E59]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <h2 class="mt-3 text-lg font-semibold text-[#0F172A]">File d'attente vide pour aujourd'hui</h2>
            <p class="mt-1 text-sm text-[#475569]">Aucun patient en attente, en cours, terminé ou absent pour ce service et cette date.</p>
            <p class="mt-2 text-sm text-[#475569]">Vous pouvez générer la file du jour depuis ce tableau de bord.</p>
        </div>
    @else
        <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-[#475569]">N°</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-[#475569]">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-[#475569]">Médecin</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-[#475569]">Heure RDV</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-[#475569]">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-[#475569]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($file as $entree)
                        <tr wire:key="file-{{ $entree->id }}" class="{{ $entree->priorite ? 'bg-amber-50' : ($entree->statut === 'En cours' ? 'bg-blue-50/50' : '') }} transition duration-150 ease-out motion-reduce:transition-none">
                            <td class="px-4 py-3 text-sm font-medium text-[#0F172A]">{{ $entree->position }}</td>
                            <td class="px-4 py-3 text-sm text-[#0F172A]">
                                <span class="break-words">{{ $entree->patient->name }}</span>
                                @if ($entree->priorite)
                                    <span class="ml-2 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">Prioritaire</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-[#475569] break-words">{{ $entree->rendezVous->medecin->nomAffiche }}</td>
                            <td class="px-4 py-3 text-sm text-[#475569]">{{ \Carbon\Carbon::parse($entree->rendezVous->heure_debut)->format('H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($entree->statut === 'En attente')
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">En attente</span>
                                @elseif ($entree->statut === 'En cours')
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-800">En cours</span>
                                @elseif ($entree->statut === 'Terminé')
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-[#334155]">Terminé</span>
                                @elseif ($entree->statut === 'Absent')
                                    <span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-800">Absent</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($entree->statut === 'En attente')
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <button type="button"
                                                wire:click="demarrer({{ $entree->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="demarrer({{ $entree->id }})"
                                                class="inline-flex h-9 min-h-[44px] items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white transition duration-150 ease-out hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                            Démarrer
                                        </button>
                                        <button type="button"
                                                wire:click="marquerAbsent({{ $entree->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="marquerAbsent({{ $entree->id }})"
                                                class="inline-flex h-9 min-h-[44px] items-center justify-center rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 transition duration-150 ease-out hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                            Absent
                                        </button>
                                        <button type="button"
                                                wire:click="ouvrirPriorite({{ $entree->id }})"
                                                class="inline-flex h-9 min-h-[44px] items-center justify-center rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 transition duration-150 ease-out hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                                            Priorité
                                        </button>
                                    </div>
                                @elseif ($entree->statut === 'En cours')
                                    <button type="button"
                                            wire:click="terminer({{ $entree->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="terminer({{ $entree->id }})"
                                            class="inline-flex h-9 min-h-[44px] items-center justify-center rounded-lg bg-[#0F766E] px-3 py-1.5 text-xs font-medium text-white transition duration-150 ease-out hover:bg-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                        Terminer
                                    </button>
                                @elseif ($entree->statut === 'Absent')
                                    <button type="button"
                                            wire:click="remettreEnAttente({{ $entree->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="remettreEnAttente({{ $entree->id }})"
                                            class="inline-flex h-9 min-h-[44px] items-center justify-center rounded-lg border border-teal-300 px-3 py-1.5 text-xs font-medium text-teal-700 transition duration-150 ease-out hover:bg-teal-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                        Remettre en attente
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="sm:hidden flex flex-col gap-3">
            @foreach ($file as $entree)
                <div wire:key="file-mobile-{{ $entree->id }}" class="{{ $entree->priorite ? 'bg-amber-50' : ($entree->statut === 'En cours' ? 'bg-blue-50/50' : 'bg-white') }} rounded-2xl border border-slate-200 p-4 shadow-sm transition duration-150 ease-out motion-reduce:transition-none">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-[#0F172A]">{{ $entree->position }}</span>
                                <p class="text-sm font-semibold text-[#0F172A] break-words">{{ $entree->patient->name }}</p>
                                @if ($entree->priorite)
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">Prioritaire</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm text-[#475569] break-words">Médecin : {{ $entree->rendezVous->medecin->nomAffiche }}</p>
                            <p class="mt-1 text-sm text-[#475569]">Heure RDV : {{ \Carbon\Carbon::parse($entree->rendezVous->heure_debut)->format('H:i') }}</p>
                            <div class="mt-2">
                                @if ($entree->statut === 'En attente')
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">En attente</span>
                                @elseif ($entree->statut === 'En cours')
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-800">En cours</span>
                                @elseif ($entree->statut === 'Terminé')
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-[#334155]">Terminé</span>
                                @elseif ($entree->statut === 'Absent')
                                    <span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-800">Absent</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col gap-2">
                        @if ($entree->statut === 'En attente')
                            <button type="button"
                                    wire:click="demarrer({{ $entree->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="demarrer({{ $entree->id }})"
                                    class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition duration-150 ease-out hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                Démarrer
                            </button>
                            <button type="button"
                                    wire:click="marquerAbsent({{ $entree->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="marquerAbsent({{ $entree->id }})"
                                    class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition duration-150 ease-out hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                Absent
                            </button>
                            <button type="button"
                                    wire:click="ouvrirPriorite({{ $entree->id }})"
                                    class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700 transition duration-150 ease-out hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                                Priorité
                            </button>
                        @elseif ($entree->statut === 'En cours')
                            <button type="button"
                                    wire:click="terminer({{ $entree->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="terminer({{ $entree->id }})"
                                    class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg bg-[#0F766E] px-4 py-2 text-sm font-medium text-white transition duration-150 ease-out hover:bg-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                Terminer
                            </button>
                        @elseif ($entree->statut === 'Absent')
                            <button type="button"
                                    wire:click="remettreEnAttente({{ $entree->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="remettreEnAttente({{ $entree->id }})"
                                    class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg border border-teal-300 px-4 py-2 text-sm font-medium text-teal-700 transition duration-150 ease-out hover:bg-teal-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                                Remettre en attente
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($prioritaireId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" wire:ignore.self role="dialog" aria-modal="true" aria-labelledby="priorite-titre">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-sm">
                <h2 id="priorite-titre" class="text-lg font-semibold text-[#0F172A]">Passage prioritaire</h2>
                <p class="mt-2 text-sm text-[#475569]">Motif obligatoire pour le passage prioritaire :</p>

                @if ($messageErreur)
                    <div class="mt-3 rounded-lg border border-[#991B1B] bg-[#FEE2E2] px-3 py-2 text-sm text-[#991B1B]">
                        {{ $messageErreur }}
                    </div>
                @endif

                <form wire:submit.prevent="confirmerPriorite" class="mt-4 space-y-4">
                    <div>
                        <x-label for="motifPriorite">Motif</x-label>
                        <textarea id="motifPriorite" wire:model="motifPriorite" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-[#0F172A] transition duration-150 ease-out focus:border-[#0F766E] focus:ring-2 focus:ring-[#0F766E]/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none" required placeholder="Ex. : Urgence médicale, personne âgée, etc."></textarea>
                        @error('motifPriorite') <p class="mt-1 text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="$set('prioritaireId', null)" class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition duration-150 ease-out hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                            Annuler
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="confirmerPriorite" class="inline-flex h-11 min-h-[44px] items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition duration-150 ease-out hover:bg-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none">
                            <span wire:loading.remove wire:target="confirmerPriorite">Confirmer la priorité</span>
                            <span wire:loading wire:target="confirmerPriorite" aria-live="polite">Confirmation...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>