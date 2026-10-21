<div class="max-w-7xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-teal-800">Salles d'intervention</h1>
    <p class="mt-1 text-sm text-slate-600">Gérez les salles et leurs disponibilités hebdomadaires pour les interventions.</p>

    @if ($messageSucces)
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
            {{ $messageSucces }}
        </div>
    @endif

    @if ($messageErreur)
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {{ $messageErreur }}
        </div>
    @endif

    @if ($salles->isEmpty())
        <x-card class="mt-6 p-8 text-center">
            <svg class="mx-auto w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h2 class="mt-3 text-lg font-medium text-slate-900">Aucune salle</h2>
            <p class="mt-1 text-sm text-slate-600">Ajoutez votre première salle d'intervention.</p>
        </x-card>
    @endif

    {{-- Formulaire ajout salle --}}
    <x-card class="mt-6 p-4">
        <h2 class="text-lg font-medium text-slate-900">Ajouter une salle</h2>
        <form wire:submit.prevent="ajouterSalle" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-label for="nom">Nom de la salle</x-label>
                <input type="text" id="nom" wire:model="nom" class="mt-1 block w-full" required maxlength="100" placeholder="Ex: Bloc opératoire 1">
                @error('nom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <x-label for="description">Description (optionnel)</x-label>
                <textarea id="description" wire:model="description" rows="2" class="mt-1 block w-full" placeholder="Ex: Salle de chirurgie cardiaque..."></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="active" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" checked>
                    Active
                </label>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">
                    Ajouter la salle
                </button>
            </div>
        </form>
    </x-card>

    @if (! $salles->isEmpty())
        {{-- Liste des salles --}}
        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nom</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($salles as $salle)
                        <tr wire:key="salle-{{ $salle->id }}" class="{{ $salle->active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $salle->nom }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $salle->description ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($salle->active)
                                    <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="basculerSalle({{ $salle->id }})" wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-medium disabled:opacity-50
                                        {{ $salle->active
                                            ? 'border-red-300 text-red-700 hover:bg-red-50'
                                            : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                                    {{ $salle->active ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Aucune salle.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Disponibilités hebdomadaires --}}
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-teal-800">Disponibilités hebdomadaires</h2>
            <p class="mt-1 text-sm text-slate-600">Définissez les plages horaires d'ouverture de chaque salle par jour de la semaine.</p>
        </div>

        {{-- Formulaire ajout disponibilité --}}
        <x-card class="mt-4 p-4">
            <h3 class="text-lg font-medium text-slate-900">Ajouter une disponibilité</h3>
            <form wire:submit.prevent="ajouterDisponibilite" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-5">
                <div>
                    <x-label for="dispoSalleId">Salle</x-label>
                    <select id="dispoSalleId" wire:model="dispoSalleId" class="mt-1 block w-full" required>
                        <option value="">— Choisir —</option>
                        @foreach ($salles as $salle)
                            @if ($salle->active)
                                <option value="{{ $salle->id }}">{{ $salle->nom }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('dispoSalleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="dispoJourSemaine">Jour</x-label>
                    <select id="dispoJourSemaine" wire:model="dispoJourSemaine" class="mt-1 block w-full" required>
                        <option value="">— Choisir —</option>
                        @foreach ($jours as $num => $nom)
                            <option value="{{ $num }}">{{ $nom }}</option>
                        @endforeach
                    </select>
                    @error('dispoJourSemaine') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="dispoHeureDebut">Début</x-label>
                    <input type="time" id="dispoHeureDebut" wire:model="dispoHeureDebut" class="mt-1 block w-full" required>
                    @error('dispoHeureDebut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="dispoHeureFin">Fin</x-label>
                    <input type="time" id="dispoHeureFin" wire:model="dispoHeureFin" class="mt-1 block w-full" required>
                    @error('dispoHeureFin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="dispoActive" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" checked>
                        Active
                    </label>
                </div>
                <div class="sm:col-span-5">
                    <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">
                        Ajouter la disponibilité
                    </button>
                </div>
            </form>
        </x-card>

        {{-- Liste des disponibilités --}}
        @if ($disponibilites->isEmpty())
            <x-card class="mt-4 p-8 text-center">
                <svg class="mx-auto w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h2 class="mt-3 text-lg font-medium text-slate-900">Aucune disponibilité</h2>
                <p class="mt-1 text-sm text-slate-600">Ajoutez des plages horaires pour vos salles.</p>
            </x-card>
        @else
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Salle</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Jour</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Plage horaire</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($disponibilites as $dispo)
                            <tr wire:key="dispo-{{ $dispo->id }}" class="{{ $dispo->active ? '' : 'opacity-50' }}">
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $dispo->salle->nom }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $jours[$dispo->jour_semaine] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ \Illuminate\Support\Str::substr($dispo->heure_debut, 0, 5) }} –
                                    {{ \Illuminate\Support\Str::substr($dispo->heure_fin, 0, 5) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($dispo->active)
                                        <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="basculerDisponibilite({{ $dispo->id }})" wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-medium disabled:opacity-50
                                            {{ $dispo->active
                                                ? 'border-red-300 text-red-700 hover:bg-red-50'
                                                : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                                        {{ $dispo->active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Aucune disponibilité.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>