<div class="max-w-7xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-teal-800">Rendez-vous</h1>
    <p class="mt-1 text-sm text-slate-600">Validez ou rejetez les demandes de rendez-vous de votre service.</p>

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

    {{-- Filtres --}}
    <x-card class="mt-6 p-4">
        <form wire:submit.prevent="render" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <x-label for="filtreStatut">Statut</x-label>
                <select id="filtreStatut" wire:model.live="filtreStatut" class="mt-1 block w-full">
                    @foreach ($statuts as $statut)
                        <option value="{{ $statut }}">{{ $statut }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-label for="search">Recherche patient</x-label>
                <input type="text" id="search" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Nom ou email...">
            </div>

            <div>
                <x-label for="dateDebut">Date début</x-label>
                <input type="date" id="dateDebut" wire:model.live="dateDebut" class="mt-1 block w-full">
            </div>

            <div>
                <x-label for="dateFin">Date fin</x-label>
                <input type="date" id="dateFin" wire:model.live="dateFin" class="mt-1 block w-full">
            </div>
        </form>
    </x-card>

    {{-- Liste des rendez-vous --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Médecin</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Date / Heure</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Motif</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rdvs as $rdv)
                    <tr wire:key="rdv-{{ $rdv->id }}">
                        <td class="px-4 py-3 text-sm text-slate-900">
                            {{ $rdv->patient->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $rdv->medecin->nomAffiche }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $rdv->date->format('d/m/Y') }} à {{ \Carbon\Carbon::parse($rdv->heure_debut)->format('H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $rdv->statut === 'Validé' ? 'bg-green-50 text-green-800'
                                    : ($rdv->statut === 'Demandé' ? 'bg-amber-50 text-amber-800'
                                    : 'bg-red-50 text-red-800') }}">
                                {{ $rdv->statut }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $rdv->motif ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($rdv->statut === 'Demandé')
                                <button type="button" wire:click="valider({{ $rdv->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-50">
                                    Valider
                                </button>
                                <button type="button" wire:click="rejeter({{ $rdv->id }})"
                                    wire:loading.attr="disabled"
                                    class="ml-2 inline-flex items-center justify-center rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:opacity-50">
                                    Rejeter
                                </button>
                            @else
                                <span class="text-xs text-slate-400">
                                    Action non possible (statut : {{ $rdv->statut }})
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                            Aucun rendez-vous trouvé pour ces critères.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if ($rdvs->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $rdvs->links() }}
            </div>
        @endif
    </div>
</div>