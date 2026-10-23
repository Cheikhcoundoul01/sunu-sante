<div class="max-w-5xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-teal-800">Disponibilités</h1>
    <p class="mt-1 text-sm text-slate-600">
        Gérez les disponibilités des médecins de votre service. Les créneaux affichés excluent les rendez-vous actifs
        et respectent l'horizon de réservation ({{ $horizonJours }} jours).
    </p>

    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" wire:click="$set('onglet', 'principal')" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $onglet === 'principal' ? 'bg-teal-600 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
            Actuels et à venir
        </button>
        <button type="button" wire:click="$set('onglet', 'historique')" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $onglet === 'historique' ? 'bg-teal-600 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
            Historique
        </button>
    </div>

    @if ($messageSucces)
        <div class="mt-4 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ $messageSucces }}
        </div>
    @endif

    @if ($messageErreur)
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $messageErreur }}
        </div>
    @endif

    @if ($medecins->isEmpty())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500 shadow-sm">
            Aucun médecin n'est rattaché à votre service.
        </div>
    @else
        @unless ($estHistorique)
            <form wire:submit="ajouter" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-medium text-slate-800">{{ $dispoId ? 'Modifier la disponibilité' : 'Ajouter une disponibilité' }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="medecinId" class="block text-sm font-medium text-slate-700">Médecin</label>
                        <select id="medecinId" wire:model.live="medecinId" @if($dispoId) disabled @endif
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                            <option value="">— Sélectionner —</option>
                            @foreach ($medecins as $medecin)
                                <option value="{{ $medecin->id }}">{{ $medecin->nomAffiche }}</option>
                            @endforeach
                        </select>
                        @error('medecinId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="jour" class="block text-sm font-medium text-slate-700">Date</label>
                        <input type="date" id="jour" wire:model="jour"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('jour') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="heure_debut" class="block text-sm font-medium text-slate-700">Début</label>
                        <input type="time" id="heure_debut" wire:model="heure_debut"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('heure_debut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="heure_fin" class="block text-sm font-medium text-slate-700">Fin</label>
                        <input type="time" id="heure_fin" wire:model="heure_fin"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('heure_fin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">
                        {{ $dispoId ? 'Enregistrer les modifications' : 'Ajouter la disponibilité' }}
                    </button>
                    @if ($dispoId)
                        <button type="button" wire:click="annulerModification" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                            Annuler
                        </button>
                    @endif
                </div>
            </form>
        @endunless

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Médecin</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Jour</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Plage</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Créneaux libres</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $items = $disponibilites instanceof \Illuminate\Pagination\LengthAwarePaginator ? $disponibilites->getCollection() : $disponibilites;
                    @endphp
                    @forelse ($items as $disponibilite)
                        @php
                            $jourDispo = $disponibilite->jour instanceof \Carbon\Carbon ? $disponibilite->jour->copy()->startOfDay() : \Carbon\Carbon::parse($disponibilite->jour)->startOfDay();
                            $maintenant = \Carbon\Carbon::now(config('app.timezone'));
                            $finDispo = null;
                            if (is_string($disponibilite->heure_fin)) {
                                $partiesFin = explode(':', $disponibilite->heure_fin);
                                if (count($partiesFin) >= 2) {
                                    $finDispo = $jourDispo->copy()->setTime((int) $partiesFin[0], (int) $partiesFin[1], 0);
                                }
                            }
                            $estPassee = $finDispo ? $finDispo->lt($maintenant) : $jourDispo->endOfDay()->lt($maintenant);
                            $priseParRdvActif = false;
                            if (! $estHistorique && ! $estPassee && $disponibilite->medecin) {
                                $debutM = (int)explode(':', (string)$disponibilite->heure_debut)[0]*60 + (int)(explode(':', (string)$disponibilite->heure_debut)[1] ?? 0);
                                $finM = (int)explode(':', (string)$disponibilite->heure_fin)[0]*60 + (int)(explode(':', (string)$disponibilite->heure_fin)[1] ?? 0);
                                $rdvsActifs = \App\Models\RendezVous::where('medecin_id', $disponibilite->medecin_id)->whereDate('date', $jourDispo->toDateString())->whereIn('statut', \App\Models\RendezVous::statutsActifs())->get();
                                foreach ($rdvsActifs as $rdv) {
                                    $rd = (int)explode(':', (string)$rdv->heure_debut)[0]*60 + (int)(explode(':', (string)$rdv->heure_debut)[1] ?? 0);
                                    $rdf = $rd + ($rdv->duree_prevue ?: 30);
                                    if ($rd < $finM && $debutM < $rdf) { $priseParRdvActif = true; break; }
                                }
                            }
                        @endphp
                        <tr wire:key="dispo-{{ $disponibilite->id }}" class="{{ $disponibilite->active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-sm text-slate-900">{{ $disponibilite->medecin?->nomAffiche }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $jourDispo->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::substr($disponibilite->heure_debut, 0, 5) }} –
                                {{ \Illuminate\Support\Str::substr($disponibilite->heure_fin, 0, 5) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($estHistorique)
                                    <span class="text-xs text-slate-400">Lecture seule</span>
                                @elseif (! $disponibilite->active)
                                    <span class="text-xs text-slate-400">Désactivée</span>
                                @else
                                    @php $creneaux = $creneauxParDisponibilite[$disponibilite->id] ?? []; @endphp
                                    @if (empty($creneaux))
                                        <span class="text-xs text-slate-400">Aucun créneau libre</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($creneaux as $creneau)
                                                <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-800">
                                                    {{ $creneau['debut'] }}–{{ $creneau['fin'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($estHistorique)
                                    <span class="text-xs text-slate-400">—</span>
                                @else
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="modifier({{ $disponibilite->id }})" wire:loading.attr="disabled"
                                            @if($estPassee || $priseParRdvActif) disabled @endif
                                            title="{{ $estPassee ? 'Créneau passé' : ($priseParRdvActif ? 'Ce créneau est déjà pris par un patient' : 'Modifier') }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed
                                                {{ ($estPassee || $priseParRdvActif) ? 'text-slate-400 bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
                                            Modifier
                                        </button>
                                        <button type="button" wire:click="basculer({{ $disponibilite->id }})"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-medium disabled:opacity-50
                                                {{ $disponibilite->active
                                                    ? 'border-red-300 text-red-700 hover:bg-red-50'
                                                    : 'border-teal-300 text-teal-700 hover:bg-teal-50' }}">
                                            {{ $disponibilite->active ? 'Désactiver' : 'Réactiver' }}
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                {{ $estHistorique ? "Aucune disponibilité dans l'historique." : 'Aucune disponibilité enregistrée.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($disponibilites instanceof \Illuminate\Pagination\LengthAwarePaginator && $estHistorique)
                <div class="px-4 py-3">
                    {{ $disponibilites->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
