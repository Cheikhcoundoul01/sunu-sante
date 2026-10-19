<div class="mx-auto max-w-7xl px-4 pb-10 sm:px-6">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold text-[#115E59]">Gestion du personnel</h1>
        <p class="mt-1 text-sm text-slate-600">Créez, modifiez et activez ou désactivez les comptes du personnel (Médecin, Secrétaire de service, Agent d'accueil, Major de service).</p>
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
            {{ $editingId ? 'Modifier un compte du personnel' : 'Nouveau compte du personnel' }}
        </h2>

        <form wire:submit="enregistrer" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-label for="name">Nom complet</x-label>
                    <input id="name" type="text" wire:model="name" placeholder="Ex. Aminata Ndiaye" autocomplete="name"
                           @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
                           @class([
                               'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                               'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('name'),
                               'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('name'),
                           ]) />
                    <p id="name-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('name')) aria-hidden="true" @endif>
                        @error('name') {{ $message }} @enderror
                    </p>
                </div>

                <div>
                    <x-label for="email">Adresse e-mail</x-label>
                    <input id="email" type="email" wire:model="email" placeholder="nom@exemple.sn" autocomplete="email"
                           @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                           @class([
                               'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                               'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('email'),
                               'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('email'),
                           ]) />
                    <p id="email-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('email')) aria-hidden="true" @endif>
                        @error('email') {{ $message }} @enderror
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-label for="role">Rôle</x-label>
                    <select id="role" wire:model="role"
                            @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif
                            @class([
                                'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                                'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('role'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('role'),
                            ])>
                        <option value="">-- Sélectionner un rôle --</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                    <p id="role-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('role')) aria-hidden="true" @endif>
                        @error('role') {{ $message }} @enderror
                    </p>
                </div>

                <div>
                    <x-label for="service_id">Service</x-label>
                    <select id="service_id" wire:model="service_id"
                            @if ($errors->has('service_id')) aria-invalid="true" aria-describedby="service_id-error" @endif
                            @class([
                                'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                                'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('service_id'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('service_id'),
                            ])>
                        <option value="">-- Sélectionner un service --</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->nom }}</option>
                        @endforeach
                    </select>
                    <p id="service_id-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('service_id')) aria-hidden="true" @endif>
                        @error('service_id') {{ $message }} @enderror
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-label for="password">{{ $editingId ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe initial' }}</x-label>
                    <input id="password" type="password" wire:model="password"
                           placeholder="{{ $editingId ? 'Laisser vide pour ne pas modifier' : 'Au moins 8 caractères' }}"
                           autocomplete="new-password"
                           @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                           @class([
                               'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                               'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('password'),
                               'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('password'),
                           ]) />
                    <p id="password-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('password')) aria-hidden="true" @endif>
                        @error('password') {{ $message }} @enderror
                    </p>
                </div>

                <div>
                    <x-label for="password_confirmation">Confirmation du mot de passe</x-label>
                    <input id="password_confirmation" type="password" wire:model="password_confirmation"
                           placeholder="Ressaisir le mot de passe" autocomplete="new-password"
                           @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="password_confirmation-error" @endif
                           @class([
                               'mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:outline-none focus:ring-2 motion-reduce:transition-none',
                               'border-slate-300 focus:border-[#0F766E] focus:ring-[#0F766E]' => ! $errors->has('password_confirmation'),
                               'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $errors->has('password_confirmation'),
                           ]) />
                    <p id="password_confirmation-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('password_confirmation')) aria-hidden="true" @endif>
                        @error('password_confirmation') {{ $message }} @enderror
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit">
                    {{ $editingId ? 'Enregistrer les modifications' : 'Créer le compte' }}
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

    {{-- Liste du personnel --}}
    <x-card class="p-6">
        <h2 class="mb-4 text-lg font-semibold text-[#115E59]">Comptes du personnel existants</h2>

        @if ($personnel->isEmpty())
            <p class="text-sm text-slate-500">Aucun compte du personnel enregistré pour le moment.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nom complet</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">E-mail</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Rôle</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Service</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">État</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($personnel as $utilisateur)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $utilisateur->nomAffiche }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $utilisateur->email }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $utilisateur->getRoleNames()->first() }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $utilisateur->service?->nom ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($utilisateur->statut_compte === 'Actif')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">Actif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">Rejeté</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="editer({{ $utilisateur->id }})"
                                                class="inline-flex min-h-[40px] items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors hover:border-[#0F766E] hover:text-[#0F766E] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                                            Modifier
                                        </button>
                                        <button type="button" wire:click="basculer({{ $utilisateur->id }})"
                                                class="inline-flex min-h-[40px] items-center rounded-full border px-4 text-sm font-medium transition-colors motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                                                       {{ $utilisateur->statut_compte === 'Actif' ? 'border-red-300 text-red-700 hover:bg-red-50' : 'border-green-300 text-green-800 hover:bg-green-50' }}">
                                            {{ $utilisateur->statut_compte === 'Actif' ? 'Désactiver' : 'Réactiver' }}
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
