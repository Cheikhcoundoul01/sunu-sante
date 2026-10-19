<div class="fixed inset-0 z-50 overflow-y-auto bg-white">
    <style>
        .font-display { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; }
    </style>

    <div class="grid min-h-full lg:grid-cols-2">

        {{-- ===== Colonne formulaire ===== --}}
        <div class="flex flex-col px-6 py-8 sm:px-12 lg:px-16">
            <header class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0F766E]">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#0F766E] text-white" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <span class="text-xl font-semibold text-[#115E59]">Sunu Santé</span>
                </a>

                <a href="{{ route('home') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-full px-3 text-sm font-medium text-slate-700 transition-colors hover:text-[#0F766E] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                    Accueil
                </a>
            </header>

            <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
                <h1 class="font-display text-5xl leading-none text-[#115E59]">Se connecter</h1>
                <p class="mt-3 text-base text-slate-600">Accédez à votre espace Sunu Santé.</p>

                <form wire:submit="login" novalidate class="mt-8 space-y-5" x-data="{ voir: false }">

                    @error('email')
                        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $message }}</div>
                    @enderror

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Adresse email</label>
                        <input id="email" type="email" wire:model="email" autocomplete="username" autofocus required
                               placeholder="nom@exemple.com"
                               class="mt-2 h-14 w-full rounded-xl border bg-slate-50 px-4 text-base text-slate-900 placeholder:text-slate-500 focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/30 @error('email') border-red-600 @else border-slate-300 @enderror">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Mot de passe</label>
                        <div class="relative mt-2">
                            <input id="password" :type="voir ? 'text' : 'password'" wire:model="password" autocomplete="current-password" required
                                   placeholder="Votre mot de passe"
                                   class="h-14 w-full rounded-xl border bg-slate-50 pl-4 pr-28 text-base text-slate-900 placeholder:text-slate-500 focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/30 @error('password') border-red-600 @else border-slate-300 @enderror">
                            <button type="button" @click="voir = !voir" :aria-pressed="voir"
                                    class="absolute inset-y-0 right-2 my-2 rounded-lg px-3 text-sm font-semibold text-[#0F766E] hover:bg-[#F0FDFA] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0F766E]">
                                <span x-text="voir ? 'Masquer' : 'Afficher'">Afficher</span>
                            </button>
                        </div>
                        @error('password')
                            <p role="alert" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" wire:model="remember" class="h-5 w-5 rounded border-slate-400 text-[#0F766E] focus:ring-[#0F766E]">
                        Se souvenir de moi
                    </label>

                    <button type="submit" wire:loading.attr="disabled" wire:target="login"
                            class="inline-flex h-14 w-full items-center justify-center rounded-full bg-[#0F766E] text-base font-semibold text-white transition-colors hover:bg-[#115E59] disabled:opacity-70 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                        <span wire:loading.remove wire:target="login">Se connecter</span>
                        <span wire:loading wire:target="login">Connexion…</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-base text-slate-700">
                    Pas encore de compte ?
                    <a href="{{ route('register.patient') }}" class="font-semibold text-[#0F766E] underline underline-offset-4 hover:text-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">Créer un compte patient</a>
                </p>

                <p class="mt-6 rounded-xl border border-[#CCFBF1] bg-[#F0FDFA] px-4 py-3 text-sm leading-relaxed text-[#115E59]">
                    Personnel de l'hôpital : votre compte est créé par l'administrateur.
                </p>
            </main>
        </div>

        {{-- ===== Colonne photo (ordinateur uniquement) ===== --}}
        <div class="relative hidden self-start overflow-hidden bg-[#0B3B38] lg:sticky lg:top-0 lg:block lg:h-screen">
            @if (file_exists(public_path('images/connexion-panneau.webp')))
                <img src="{{ asset('images/connexion-panneau.webp') }}"
                     alt="Médecin souriant tenant un dossier dans un cabinet lumineux"
                     class="absolute inset-y-0 right-0 h-full w-[85%] origin-left scale-110 object-cover object-[0%_20%] [mask-image:linear-gradient(to_right,transparent,black_30%)]"
                     width="1200" height="900" loading="eager">
            @endif
            <div class="absolute inset-0 bg-[linear-gradient(0deg,#0B3B38_0%,rgba(11,59,56,0.6)_30%,rgba(11,59,56,0)_65%)]" aria-hidden="true"></div>
            <div class="absolute inset-x-0 bottom-0 p-8 xl:p-12">
                <p class="font-display text-4xl italic leading-tight text-white xl:text-5xl">Dalal ak jàmm ci Sunu Santé</p>
                <p class="mt-3 text-lg text-[#99F6E4]">Vos rendez-vous, sans file inutile.</p>
            </div>
        </div>
    </div>
</div>