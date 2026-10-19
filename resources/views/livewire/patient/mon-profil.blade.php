<div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
    <header class="mb-6">
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-[#115E59] to-[#0F766E] text-white shadow-sm">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.12),transparent_70%)]"></div>
            <div class="relative flex flex-col items-center gap-5 px-4 py-6 sm:flex-row sm:items-center sm:gap-6 sm:px-6 sm:py-8">
                @php
                    $user = auth()->user();
                    $nom = trim($user->name ?? '');
                    $mots = preg_split('/\s+/u', $nom, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $initiales = '';
                    foreach (array_slice($mots, 0, 2) as $mot) {
                        $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
                    }
                    if ($initiales === '') {
                        $initiales = 'P';
                    }
                @endphp
                <span class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-white text-2xl font-semibold text-[#115E59] ring-4 ring-white/30 shadow-sm sm:h-[88px] sm:w-[88px]" aria-hidden="true">
                    {{ $initiales }}
                </span>
                <div class="flex w-full min-w-0 flex-col items-center text-center sm:items-start sm:text-left">
                    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Mon profil</h1>
                    <p class="mt-1 text-sm leading-6 text-teal-50">Gérez vos informations personnelles et votre mot de passe.</p>
                    <div class="mt-3 flex min-w-0 flex-col items-center gap-1 sm:items-start">
                        <p class="max-w-full truncate text-base font-semibold leading-tight sm:text-lg">{{ $nom !== '' ? $nom : 'Patient' }}</p>
                        <p class="max-w-full break-words text-sm text-teal-100">{{ $user->email ?? '' }}</p>
                    </div>
                </div>
            </div>
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

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2 lg:gap-6">
        {{-- Informations personnelles --}}
        <x-card class="flex flex-col p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#CCFBF1] text-[#115E59]" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        <path d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-[#0F172A]">Informations personnelles</h2>
                    <p class="mt-1 text-sm text-[#475569]">Mettez à jour votre nom complet et votre adresse e-mail.</p>
                </div>
            </div>

            <div class="mt-4 border-t border-slate-200"></div>

            <form wire:submit.prevent="mettreAJourProfil" class="mt-5 flex flex-1 flex-col space-y-5">
                <div class="space-y-5">
                    <div>
                        <x-label for="name">Nom complet</x-label>
                        <div class="relative mt-2">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    <path d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" id="name" wire:model="name" required
                                   class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 py-3 text-base text-[#0F172A] placeholder:text-slate-500 transition duration-150 ease-out focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/20 motion-reduce:transition-none sm:text-sm"
                                   placeholder="Votre nom complet">
                        </div>
                        @error('name') <p class="mt-1 text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-label for="email">Email</x-label>
                        <div class="relative mt-2">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" id="email" wire:model="email" required
                                   class="block w-full min-w-0 rounded-lg border border-slate-300 bg-white pl-10 pr-3 py-3 text-base text-[#0F172A] placeholder:text-slate-500 transition duration-150 ease-out focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/20 motion-reduce:transition-none sm:text-sm"
                                   placeholder="votre@email.sn">
                        </div>
                        @error('email') <p class="mt-1 text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-auto flex w-full justify-stretch pt-2 sm:justify-end">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="mettreAJourProfil"
                            class="inline-flex h-11 min-h-[44px] w-full items-center justify-center rounded-lg bg-[#0F766E] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 ease-out hover:bg-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none sm:w-auto">
                        <span wire:loading.remove wire:target="mettreAJourProfil">Enregistrer</span>
                        <span wire:loading wire:target="mettreAJourProfil" aria-live="polite">Enregistrement...</span>
                    </button>
                </div>
            </form>
        </x-card>

        {{-- Changement de mot de passe --}}
        <x-card class="flex flex-col p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#CCFBF1] text-[#115E59]" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-[#0F172A]">Changer le mot de passe</h2>
                    <p class="mt-1 text-sm text-[#475569]">Modifiez votre mot de passe en toute sécurité.</p>
                </div>
            </div>

            <div class="mt-4 border-t border-slate-200"></div>

            <form wire:submit.prevent="changerMotDePasse" class="mt-5 flex flex-1 flex-col space-y-5">
                <div class="space-y-5">
                    <div>
                        <x-label for="currentPassword">Mot de passe actuel</x-label>
                        <div class="relative mt-2">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" id="currentPassword" wire:model="currentPassword" required autocomplete="current-password"
                                   class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 py-3 text-base text-[#0F172A] placeholder:text-slate-500 transition duration-150 ease-out focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/20 motion-reduce:transition-none sm:text-sm"
                                   placeholder="Mot de passe actuel">
                        </div>
                        @error('currentPassword') <p class="mt-1 text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-label for="password">Nouveau mot de passe</x-label>
                        <div class="relative mt-2">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 11V7a5 5 0 1110 0v4M5 11h14v10H5V11z" />
                                </svg>
                            </div>
                            <input type="password" id="password" wire:model="password" required autocomplete="new-password"
                                   class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 py-3 text-base text-[#0F172A] placeholder:text-slate-500 transition duration-150 ease-out focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/20 motion-reduce:transition-none sm:text-sm"
                                   placeholder="Nouveau mot de passe">
                        </div>
                        @error('password') <p class="mt-1 text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-label for="password_confirmation">Confirmer le nouveau mot de passe</x-label>
                        <div class="relative mt-2">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <input type="password" id="password_confirmation" wire:model="password_confirmation" required autocomplete="new-password"
                                   class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 py-3 text-base text-[#0F172A] placeholder:text-slate-500 transition duration-150 ease-out focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/20 motion-reduce:transition-none sm:text-sm"
                                   placeholder="Confirmer le nouveau mot de passe">
                        </div>
                        @error('password_confirmation') <p class="mt-1 text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-auto flex w-full justify-stretch pt-2 sm:justify-end">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="changerMotDePasse"
                            class="inline-flex h-11 min-h-[44px] w-full items-center justify-center rounded-lg bg-[#0F766E] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 ease-out hover:bg-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] disabled:opacity-50 motion-reduce:transition-none sm:w-auto">
                        <span wire:loading.remove wire:target="changerMotDePasse">Modifier le mot de passe</span>
                        <span wire:loading wire:target="changerMotDePasse" aria-live="polite">Modification...</span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>