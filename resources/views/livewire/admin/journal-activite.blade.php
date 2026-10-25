<div class="mx-auto max-w-7xl px-4 pb-10 sm:px-6">
    <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-[#115E59]">Journal d'activité</h1>
            <p class="mt-1 text-sm text-slate-600">Consultation en lecture seule des actions enregistrées sur la plateforme.</p>
        </div>
        <span class="inline-flex min-h-[32px] items-center rounded-full bg-[#F0FDFA] px-3 text-xs font-semibold uppercase tracking-wide text-[#0F766E]">
            Lecture seule
        </span>
    </header>

    <x-card class="p-5 sm:p-6">
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,16rem)_minmax(0,20rem)_auto]">
            <div>
                <x-label for="date">Date</x-label>
                <input id="date" type="date" wire:model.live="date"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]">
            </div>

            <div>
                <x-label for="event">Événement</x-label>
                <select id="event" wire:model.live="event"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]">
                    <option value="">Tous les événements</option>
                    @foreach ($evenements as $valeur => $libelle)
                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="reinitialiserFiltres"
                        class="inline-flex min-h-[44px] items-center rounded-full border border-slate-300 px-5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                    Réinitialiser
                </button>
            </div>
        </div>

        @if ($activites->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Aucune activité trouvée.</p>
                <p class="mt-1 text-sm text-slate-500">Modifiez les filtres pour élargir la recherche.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Date</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Auteur</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Ressource</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Détail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($activites as $activite)
                            <tr wire:key="activite-{{ $activite['id'] }}">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $activite['date'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-medium text-slate-900">{{ $activite['auteur'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $activite['role'] }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $activite['action'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $activite['ressource'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $activite['detail'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($activites->hasPages())
                <div class="mt-4">
                    {{ $activites->links() }}
                </div>
            @endif
        @endif
    </x-card>
</div>
