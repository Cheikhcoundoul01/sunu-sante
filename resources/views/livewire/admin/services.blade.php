<div class="mx-auto max-w-7xl px-4 pb-10 sm:px-6">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold text-[#115E59]">Gestion des services</h1>
        <p class="mt-1 text-sm text-slate-600">Créez, modifiez et activez ou désactivez les services de l'hôpital.</p>
    </header>

    @if ($messageSucces)
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">
            {{ $messageSucces }}
        </div>
    @endif

    @if ($messageErreur)
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
            {{ $messageErreur }}
        </div>
    @endif

    {{-- Formulaire de création / modification --}}
    <x-card class="mb-8 p-6">
        <h2 class="mb-4 text-lg font-semibold text-[#115E59]">
            {{ $editingId ? 'Modifier le service' : 'Nouveau service' }}
        </h2>

        <form wire:submit="enregistrer" class="space-y-5">
            <div>
                <x-label for="nom">
                    Nom du service <span class="text-red-600" aria-hidden="true">*</span>
                </x-label>
                <input id="nom" type="text" wire:model="nom" placeholder="Ex. : Cardiologie" autocomplete="off"
                       aria-required="true"
                       @if ($errors->has('nom')) aria-invalid="true" aria-describedby="nom-error" @endif
                       @class([
                           'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                           'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('nom'),
                           'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('nom'),
                       ]) />
                <p id="nom-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('nom')) aria-hidden="true" @endif>
                    @error('nom') {{ $message }} @enderror
                </p>
            </div>

            <div>
                <x-label for="description">Description (optionnel)</x-label>
                <textarea id="description" wire:model="description" rows="3" placeholder="Brève description du service"
                          @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif
                          @class([
                              'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                              'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('description'),
                              'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('description'),
                          ])></textarea>
                <p id="description-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('description')) aria-hidden="true" @endif>
                    @error('description') {{ $message }} @enderror
                </p>
            </div>

            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                <input type="checkbox" wire:model="actif"
                       class="h-5 w-5 rounded border-slate-300 text-[#0F766E] focus:ring-2 focus:ring-[#0F766E]">
                Service actif
            </label>
            @error('actif') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit">
                    {{ $editingId ? 'Enregistrer les modifications' : 'Créer le service' }}
                </x-button>

                @if ($editingId)
                    <button type="button" wire:click="annulerEdition"
                            class="inline-flex min-h-[44px] items-center rounded-full border border-slate-300 px-5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                        Annuler
                    </button>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Liste des services --}}
    <x-card class="p-6">
        <h2 class="mb-4 text-lg font-semibold text-[#115E59]">Services existants</h2>

        @if ($services->isEmpty())
            <p class="text-sm text-slate-500">Aucun service enregistré pour le moment.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Service</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Personnel</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Rendez-vous</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">État</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($services as $service)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-slate-900">{{ $service->nom }}</p>
                                    @if ($service->description)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $service->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700">{{ $service->utilisateurs_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700">{{ $service->rendez_vous_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($service->actif)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">Actif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">Inactif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="editer({{ $service->id }})"
                                                class="inline-flex min-h-[40px] items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors hover:border-[#0F766E] hover:text-[#0F766E] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                                            Modifier
                                        </button>
                                        <button type="button" wire:click="basculer({{ $service->id }})"
                                                class="inline-flex min-h-[40px] items-center rounded-full border px-4 text-sm font-medium transition-colors motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                                                       {{ $service->actif ? 'border-red-300 text-red-700 hover:bg-red-50' : 'border-green-300 text-green-800 hover:bg-green-50' }}">
                                            {{ $service->actif ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
