<div class="max-w-5xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-teal-800">Disponibilités</h1>
    <p class="mt-1 text-sm text-slate-600">
        Gérez les disponibilités des médecins de votre service. Les créneaux affichés excluent les rendez-vous actifs
        et respectent l'horizon de réservation ({{ $horizonJours }} jours).
    </p>

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
        <form wire:submit="ajouter" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="medecinId" class="block text-sm font-medium text-slate-700">Médecin</label>
                    <select id="medecinId" wire:model.live="medecinId"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
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
            <div class="mt-4">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">
                    Ajouter la disponibilité
                </button>
            </div>
        </form>

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
                    @forelse ($disponibilites as $disponibilite)
                        <tr wire:key="dispo-{{ $disponibilite->id }}" class="{{ $disponibilite->active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-sm text-slate-900">{{ $disponibilite->medecin?->nomAffiche }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $disponibilite->jour?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::substr($disponibilite->heure_debut, 0, 5) }} –
                                {{ \Illuminate\Support\Str::substr($disponibilite->heure_fin, 0, 5) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @php $creneaux = $creneauxParDisponibilite[$disponibilite->id] ?? []; @endphp
                                @if (! $disponibilite->active)
                                    <span class="text-xs text-slate-400">Désactivée</span>
                                @elseif (empty($creneaux))
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
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="basculer({{ $disponibilite->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-medium disabled:opacity-50
                                        {{ $disponibilite->active
                                            ? 'border-red-300 text-red-700 hover:bg-red-50'
                                            : 'border-teal-300 text-teal-700 hover:bg-teal-50' }}">
                                    {{ $disponibilite->active ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Aucune disponibilité enregistrée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
