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

            @if ($role === 'Secrétaire de service')
                <div>
                    <x-label for="servicesSupplementaires">Services supplémentaires (facultatif)</x-label>
                    <p class="mt-1 text-sm text-slate-500">Le secrétaire pourra gérer les rendez-vous, disponibilités et salles de son service principal et des services cochés ci-dessous.</p>
                    <div id="servicesSupplementaires" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2"
                         @if ($errors->has('servicesSupplementaires')) aria-invalid="true" aria-describedby="servicesSupplementaires-error" @endif>
                        @foreach ($services as $service)
                            @if ((int) $service->id !== (int) $service_id)
                                <label class="flex min-h-[44px] items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-[#0F766E]">
                                    <input type="checkbox" value="{{ $service->id }}" wire:model="servicesSupplementaires"
                                           class="h-4 w-4 rounded border-slate-300 text-[#0F766E] focus:ring-[#0F766E]">
                                    {{ $service->nom }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <p id="servicesSupplementaires-error" class="mt-1 min-h-[1.25rem] text-sm text-red-700" @if (! $errors->has('servicesSupplementaires')) aria-hidden="true" @endif>
                        @error('servicesSupplementaires') {{ $message }} @enderror
                    </p>
                </div>
            @endif

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
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2">
            <h2 class="text-lg font-semibold text-[#115E59]">Comptes du personnel existants</h2>
            <p class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ $totalComptes }}</span> {{ $totalComptes > 1 ? 'comptes' : 'compte' }} au total
                <span class="mx-1 text-slate-300" aria-hidden="true">·</span>
                <span class="font-semibold text-[#0F766E]">{{ $totalActifs }}</span> {{ $totalActifs > 1 ? 'actifs' : 'actif' }}
            </p>
        </div>

        {{-- Recherche et filtres --}}
        <form wire:submit.prevent="render" class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-label for="search">Recherche</x-label>
                <input type="text" id="search" wire:model.live.debounce.300ms="search"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]"
                       placeholder="Nom ou e-mail...">
            </div>

            <div>
                <x-label for="filtreRole">Rôle</x-label>
                <select id="filtreRole" wire:model.live="filtreRole"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]">
                    <option value="Tous">Tous les rôles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-label for="filtreService">Service</x-label>
                <select id="filtreService" wire:model.live="filtreService"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]">
                    <option value="Tous">Tous les services</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-label for="filtreEtat">État</x-label>
                <select id="filtreEtat" wire:model.live="filtreEtat"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]">
                    @foreach ($etats as $etat)
                        <option value="{{ $etat }}">{{ $etat === 'Tous' ? 'Tous les états' : $etat }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($personnel->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Aucun compte du personnel trouvé</p>
                <p class="mt-1 text-sm text-slate-500">Ajustez la recherche ou les filtres pour élargir les résultats.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Membre du personnel</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Rôle</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Service</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">État</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($personnel as $utilisateur)
                            @php
                                $mots = preg_split('/\s+/u', \Illuminate\Support\Str::ascii($utilisateur->nomAffiche), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                $initiales = '';
                                foreach (array_slice($mots, 0, 2) as $mot) {
                                    $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
                                }
                                $initiales = $initiales !== '' ? $initiales : 'A';
                            @endphp
                            <tr @class(['bg-slate-50/60' => ! $utilisateur->estActif()])>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-teal-50 text-xs font-semibold text-[#115E59] ring-1 ring-inset ring-teal-100 {{ $utilisateur->estActif() ? '' : 'opacity-70' }}" aria-hidden="true">{{ $initiales }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium {{ $utilisateur->estActif() ? 'text-slate-900' : 'text-slate-500' }}">{{ $utilisateur->nomAffiche }}</p>
                                            <p class="truncate text-xs {{ $utilisateur->estActif() ? 'text-slate-500' : 'text-slate-400' }}">{{ $utilisateur->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center whitespace-nowrap rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-[#115E59] ring-1 ring-inset ring-teal-100">{{ $utilisateur->getRoleNames()->first() }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm {{ $utilisateur->estActif() ? 'text-slate-700' : 'text-slate-400' }}">
                                    <span>{{ $utilisateur->service?->nom ?? '—' }}</span>
                                    @if ($utilisateur->servicesSupplementaires->isNotEmpty())
                                        <span class="mt-1 flex flex-wrap gap-1">
                                            @foreach ($utilisateur->servicesSupplementaires as $supp)
                                                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">+ {{ $supp->nom }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($utilisateur->estActif())
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-800 ring-1 ring-inset ring-teal-100">Actif</span>
                                    @else
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-100">Désactivé</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="editer({{ $utilisateur->id }})"
                                                class="inline-flex min-h-[44px] items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors hover:border-[#0F766E] hover:text-[#0F766E] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                                            Modifier
                                        </button>
                                        @if ($utilisateur->estActif())
                                            <button type="button" wire:click="basculer({{ $utilisateur->id }})"
                                                    wire:confirm="Désactiver le compte de {{ $utilisateur->nomAffiche }} ? Le personnel concerné ne pourra plus se connecter."
                                                    class="inline-flex min-h-[44px] items-center rounded-full border border-red-300 px-4 text-sm font-medium text-red-700 transition-colors hover:bg-red-50 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                                                Désactiver
                                            </button>
                                        @else
                                            <button type="button" wire:click="basculer({{ $utilisateur->id }})"
                                                    wire:confirm="Réactiver le compte de {{ $utilisateur->nomAffiche }} ?"
                                                    class="inline-flex min-h-[44px] items-center rounded-full border border-teal-300 px-4 text-sm font-medium text-[#115E59] transition-colors hover:bg-teal-50 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                                                Réactiver
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($personnel->hasPages())
                <div class="mt-4 border-t border-slate-200 pt-4">
                    {{ $personnel->links() }}
                </div>
            @endif
        @endif
    </x-card>
</div>
