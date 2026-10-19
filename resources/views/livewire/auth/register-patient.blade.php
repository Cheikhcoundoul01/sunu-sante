@php
    $photo = file_exists(public_path('images/hero-acceuil.webp'))
        ? 'images/hero-acceuil.webp'
        : (file_exists(public_path('images/hero-accueil.webp')) ? 'images/hero-accueil.webp' : null);
@endphp

<div class="fixed inset-0 z-50 overflow-y-auto bg-white">
    <style>
        .font-display { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; }
        [x-cloak] { display: none !important; }
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
                <h1 class="font-display text-5xl leading-none text-[#115E59]">Créer un compte</h1>
                <p class="mt-3 text-base text-slate-600">Inscrivez-vous en moins d'une minute pour prendre rendez-vous.</p>

                <form wire:submit="register" novalidate class="mt-8 space-y-5"
                      x-data="{
                          voir: false,
                          pw: '',
                          conf: '',
                          get score() {
                              let s = 0;
                              if (this.pw.length >= 8) s++;
                              if (/[A-Z]/.test(this.pw) && /[a-z]/.test(this.pw)) s++;
                              if (/[0-9]/.test(this.pw)) s++;
                              if (/[^A-Za-z0-9]/.test(this.pw)) s++;
                              return s;
                          },
                          get libelle() { return ['Trop court', 'Faible', 'Moyen', 'Bon', 'Excellent'][this.score]; }
                      }">

                    {{-- Nom complet --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nom complet</label>
                        <input id="name" type="text" wire:model="name" autocomplete="name" autofocus required
                               placeholder="Prénom et nom"
                               class="mt-2 h-14 w-full rounded-xl border bg-slate-50 px-4 text-base text-slate-900 placeholder:text-slate-500 focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/30 @error('name') border-red-600 @else border-slate-300 @enderror">
                        @error('name')
                            <p role="alert" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Adresse email</label>
                        <input id="email" type="email" wire:model="email" autocomplete="username" required
                               placeholder="nom@exemple.com"
                               class="mt-2 h-14 w-full rounded-xl border bg-slate-50 px-4 text-base text-slate-900 placeholder:text-slate-500 focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/30 @error('email') border-red-600 @else border-slate-300 @enderror">
                        @error('email')
                            <p role="alert" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Mot de passe --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Mot de passe</label>
                        <div class="relative mt-2">
                            <input id="password" :type="voir ? 'text' : 'password'" wire:model="password"
                                   x-model="pw" autocomplete="new-password" required
                                   placeholder="Au moins 8 caractères"
                                   class="h-14 w-full rounded-xl border bg-slate-50 pl-4 pr-28 text-base text-slate-900 placeholder:text-slate-500 focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/30 @error('password') border-red-600 @else border-slate-300 @enderror">
                            <button type="button" @click="voir = !voir" :aria-pressed="voir"
                                    class="absolute inset-y-0 right-2 my-2 rounded-lg px-3 text-sm font-semibold text-[#0F766E] hover:bg-[#F0FDFA] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0F766E]">
                                <span x-text="voir ? 'Masquer' : 'Afficher'">Afficher</span>
                            </button>
                        </div>

                        {{-- Indicateur de force --}}
                        <div class="mt-3" x-show="pw.length > 0" x-cloak>
                            <div class="flex gap-1.5" aria-hidden="true">
                                <template x-for="i in 4" :key="i">
                                    <span class="h-1.5 flex-1 rounded-full transition-colors motion-reduce:transition-none"
                                          :class="i <= score
                                              ? (score <= 1 ? 'bg-red-500' : (score === 2 ? 'bg-amber-500' : 'bg-[#0F766E]'))
                                              : 'bg-slate-200'"></span>
                                </template>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-600">Force : <span class="font-semibold" x-text="libelle"></span></p>
                        </div>

                        @error('password')
                            <p role="alert" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirmation --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
                        <input id="password_confirmation" :type="voir ? 'text' : 'password'" wire:model="password_confirmation"
                               x-model="conf" autocomplete="new-password" required
                               placeholder="Retapez votre mot de passe"
                               class="mt-2 h-14 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-base text-slate-900 placeholder:text-slate-500 focus:border-[#0F766E] focus:outline-none focus:ring-2 focus:ring-[#0F766E]/30">
                        <p class="mt-2 text-sm font-medium text-[#0F766E]" x-show="conf.length > 0 && pw === conf" x-cloak>
                            ✓ Les mots de passe correspondent
                        </p>
                        <p class="mt-2 text-sm font-medium text-amber-700" x-show="conf.length > 0 && pw !== conf" x-cloak>
                            Les mots de passe ne correspondent pas encore
                        </p>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="register"
                            class="inline-flex h-14 w-full items-center justify-center rounded-full bg-[#0F766E] text-base font-semibold text-white transition-colors hover:bg-[#115E59] disabled:opacity-70 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                        <span wire:loading.remove wire:target="register">Créer mon compte</span>
                        <span wire:loading wire:target="register">Création du compte…</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-base text-slate-700">
                    Déjà un compte ?
                    <a href="{{ route('login') }}" class="font-semibold text-[#0F766E] underline underline-offset-4 hover:text-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">Se connecter</a>
                </p>
            </main>
        </div>

        {{-- ===== Colonne photo (ordinateur uniquement) ===== --}}
        <div class="relative hidden self-start overflow-hidden bg-[#0B3B38] lg:sticky lg:top-0 lg:block lg:h-screen">
            @if ($photo)
                <img src="{{ asset($photo) }}"
                     alt="Médecin souriant tenant une tablette dans son cabinet"
                     class="absolute inset-0 h-full w-full object-cover object-[50%_30%]"
                     width="1551" height="1014" loading="eager">
            @endif
            <div class="absolute inset-0 bg-[linear-gradient(0deg,#0B3B38_0%,rgba(11,59,56,0.6)_30%,rgba(11,59,56,0)_65%)]" aria-hidden="true"></div>
            <div class="absolute inset-x-0 bottom-0 p-8 xl:p-12">
                <p class="font-display text-4xl italic leading-tight text-white xl:text-5xl">Dalal ak jàmm ci Sunu Santé</p>
                <p class="mt-3 text-lg text-[#99F6E4]">Créez votre compte et prenez rendez-vous sans file d'attente.</p>
            </div>
        </div>
    </div>
</div>