<x-app-layout>
    <style>
        .font-display { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; }
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>

    {{-- ===== HERO ===== --}}
    <section class="relative isolate flex min-h-[100svh] flex-col overflow-hidden bg-[#0B3B38]">

        {{-- Photo : côté droit, son bord gauche se fond dans le fond teal --}}
        <div class="absolute inset-y-0 right-0 w-full bg-[#CCFBF1] lg:w-[64%]">
            @if (file_exists(public_path('images/inscription-panneau.webp')))
                <img src="{{ asset('images/inscription-panneau.webp') }}"
                     alt="Médecin souriant en blouse blanche, stéthoscope autour du cou"
                     class="h-full w-full object-cover object-[50%_8%]"
                     width="1551" height="1014" loading="eager" fetchpriority="high">
            @endif
            <div class="absolute inset-0 bg-[#0F766E]/10" aria-hidden="true"></div>
            <div class="absolute inset-0 hidden bg-[linear-gradient(90deg,#0B3B38_0%,#0B3B38_14%,rgba(11,59,56,0.7)_28%,rgba(11,59,56,0)_50%)] lg:block" aria-hidden="true"></div>
        </div>

        {{-- Dégradé mobile : texte lisible en bas --}}
        <div class="absolute inset-0 bg-[linear-gradient(0deg,#0B3B38_0%,#0B3B38_48%,rgba(11,59,56,0.55)_64%,rgba(11,59,56,0)_88%)] lg:hidden" aria-hidden="true"></div>

        {{-- Navigation : volontairement minimale pour laisser respirer la photo --}}
        <header class="relative z-20">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-6 sm:px-12 sm:py-9">
                <div class="flex flex-wrap items-center gap-3 sm:gap-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-[#0F766E]" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                        <span class="text-xl font-semibold text-white sm:text-2xl">Sunu Santé</span>
                    </a>

                    <a href="#comment" class="group inline-flex min-h-[44px] items-center gap-1.5 rounded-full border border-white/30 bg-[#0B3B38]/90 px-4 py-2 text-sm font-medium text-white underline-offset-4 backdrop-blur-sm transition duration-150 hover:bg-[#99F6E4] hover:text-[#115E59] hover:underline focus-visible:bg-[#99F6E4] focus-visible:text-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white motion-reduce:transition-none sm:text-base">
                        Comment ça marche
                        <svg class="h-4 w-4 shrink-0 transition-transform duration-150 group-hover:translate-y-0.5 group-focus-visible:translate-y-0.5 motion-reduce:group-hover:translate-y-0 motion-reduce:group-focus-visible:translate-y-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </a>
                </div>

                <a href="{{ route('login') }}" class="inline-flex min-h-[48px] items-center justify-center rounded-full border-2 border-white bg-[#0F766E] px-7 text-base font-semibold text-white transition-colors hover:bg-[#115E59] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Se connecter</a>
            </div>
        </header>

        {{-- Contenu --}}
        <div class="relative z-10 mt-auto px-5 pb-14 pt-12 sm:px-12 sm:pb-16 lg:max-w-[62%]">
            <p class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/5 px-4 py-2 text-sm font-medium text-white">
                <span class="h-2 w-2 rounded-full bg-[#99F6E4]" aria-hidden="true"></span>
                Rendez-vous médicaux en ligne
            </p>

            <h1 class="font-display mt-7 text-4xl leading-[1.05] text-white sm:text-5xl lg:max-w-[15em] lg:text-6xl">
                <span class="italic text-[#99F6E4]">Votre santé</span>
                mérite un rendez-vous simple, sans déplacement inutile.
            </h1>

            <div class="mt-10 flex flex-wrap items-center gap-3 sm:flex-nowrap">
                <a href="{{ route('login') }}" class="inline-flex h-16 shrink-0 items-center gap-4 rounded-full bg-white pl-7 pr-2 text-lg font-semibold text-[#115E59] transition-colors hover:bg-[#F0FDFA] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Prendre rendez-vous
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </span>
                </a>

                <div class="inline-flex h-16 shrink-0 items-center gap-3 rounded-full border border-white/30 bg-white/5 pl-5 pr-2 text-white">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#99F6E4]" aria-hidden="true"></span>
                    <span class="text-lg font-semibold">File d'attente</span>
                    <span class="inline-flex h-12 items-center rounded-full bg-[#99F6E4] px-5 text-base font-semibold text-[#0B3B38]">Exemple</span>
                </div>
            </div>

        </div>

        {{-- Bandeau wolof statique --}}
        <div class="relative z-10 border-t border-b border-white/20 bg-[#0B3B38]/90 py-4 sm:py-6">
            <div class="flex flex-wrap items-center justify-center gap-4 px-5 text-center sm:gap-6">
                <span class="font-display text-2xl italic text-[#99F6E4] sm:text-3xl">Nanga def</span>
                <svg class="h-6 w-12 shrink-0 text-[#99F6E4] sm:h-8 sm:w-16" viewBox="0 0 80 40" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 22h18l6-14 10 28 8-20 5 6h29"/></svg>
                <span class="font-display text-2xl italic text-white sm:text-3xl">Dalal ak jàmm ci Sunu Santé</span>
            </div>
        </div>
    </section>

    {{-- ===== COMMENT ÇA MARCHE ===== --}}
    <section id="comment" class="mx-auto max-w-6xl scroll-mt-6 px-5 py-14 sm:px-10 md:py-20">
        <p class="text-sm font-semibold uppercase tracking-wide text-[#0F766E]">En trois étapes</p>
        <h2 class="font-display mt-2 text-4xl text-[#115E59] sm:text-5xl">Comment ça marche</h2>

        <ol class="mt-10 grid gap-5 md:grid-cols-3">
            <li class="relative rounded-2xl border border-[#CCFBF1] bg-white p-6 transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-within:border-teal-200 focus-within:shadow-md motion-reduce:transform-none motion-reduce:transition-none">
                <span class="absolute -top-5 left-[43px] h-5 w-0.5 bg-[#99F6E4] md:hidden" aria-hidden="true"></span>
                <span class="absolute -right-5 top-[42px] hidden items-center md:flex" aria-hidden="true">
                    <span class="h-px w-4 bg-[#99F6E4]"></span>
                    <svg class="h-4 w-4 -ml-0.5 text-[#99F6E4]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </span>
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#0F766E] text-base font-semibold text-white">1</span>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#CCFBF1] text-[#115E59]" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            <path d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Créez votre compte</h3>
                <p class="mt-2 text-slate-600">Inscrivez-vous en tant que patient. La secrétaire du service valide votre compte.</p>
            </li>
            <li class="relative rounded-2xl border border-[#CCFBF1] bg-white p-6 transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-within:border-teal-200 focus-within:shadow-md motion-reduce:transform-none motion-reduce:transition-none">
                <span class="absolute -top-5 left-[43px] h-5 w-0.5 bg-[#99F6E4] md:hidden" aria-hidden="true"></span>
                <span class="absolute -right-5 top-[42px] hidden items-center md:flex" aria-hidden="true">
                    <span class="h-px w-4 bg-[#99F6E4]"></span>
                    <svg class="h-4 w-4 -ml-0.5 text-[#99F6E4]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </span>
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#0F766E] text-base font-semibold text-white">2</span>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#CCFBF1] text-[#115E59]" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Prenez rendez-vous</h3>
                <p class="mt-2 text-slate-600">Choisissez un service, un médecin et un créneau libre. La secrétaire confirme votre demande.</p>
            </li>
            <li class="relative rounded-2xl border border-[#CCFBF1] bg-white p-6 transition duration-150 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md focus-within:border-teal-200 focus-within:shadow-md motion-reduce:transform-none motion-reduce:transition-none">
                <span class="absolute -top-5 left-[43px] h-5 w-0.5 bg-[#99F6E4] md:hidden" aria-hidden="true"></span>
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#0F766E] text-base font-semibold text-white">3</span>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#CCFBF1] text-[#115E59]" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Suivez votre file</h3>
                <p class="mt-2 text-slate-600">Le jour du rendez-vous, consultez votre position dans la file d'attente de votre service.</p>
            </li>
        </ol>
    </section>

    {{-- ===== AVANTAGES ===== --}}
    <section class="bg-[#F0FDFA]">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-5 py-14 sm:px-10 md:py-20 lg:grid-cols-2">
            <div>
                <h2 class="font-display text-4xl text-[#115E59] sm:text-5xl">Pensé pour les patients</h2>
                <ul class="mt-8 space-y-5">
                    <li class="flex gap-4">
                        <span class="mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                        </span>
                        <p class="text-slate-700"><span class="font-semibold text-slate-900">Rendez-vous en ligne.</span> Demandez un créneau sans vous déplacer.</p>
                    </li>
                    <li class="flex gap-4">
                        <span class="mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                        </span>
                        <p class="text-slate-700"><span class="font-semibold text-slate-900">Modifier ou annuler.</span> Gérez vos demandes depuis « Mes rendez-vous ».</p>
                    </li>
                    <li class="flex gap-4">
                        <span class="mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                        </span>
                        <p class="text-slate-700"><span class="font-semibold text-slate-900">Suivi de la file.</span> Voyez où vous en êtes, sans rester debout à attendre.</p>
                    </li>
                    <li class="flex gap-4">
                        <span class="mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                        </span>
                        <p class="text-slate-700"><span class="font-semibold text-slate-900">Notifications par e-mail.</span> Soyez prévenu à chaque étape : compte validé, rendez-vous confirmé, modifié ou annulé.</p>
                    </li>
                </ul>
            </div>

            <div class="overflow-hidden rounded-3xl bg-[#CCFBF1]">
                @if (file_exists(public_path('images/salle-attente.webp')))
                    <img src="{{ asset('images/salle-attente.webp') }}"
                         alt="Salle d'attente d'un établissement de santé"
                         class="aspect-[4/3] w-full object-cover"
                         width="1024" height="683" loading="lazy">
                @else
                    <div class="aspect-[4/3] w-full"></div>
                @endif
            </div>
        </div>
    </section>

    {{-- ===== APPEL À L'ACTION ===== --}}
    <section class="mx-auto max-w-6xl px-5 py-14 sm:px-10 md:py-20">
        <div class="rounded-2xl bg-gradient-to-br from-[#0F766E] to-[#0B3B38] px-5 py-8 text-white shadow-sm sm:px-8 sm:py-10">
            <h2 class="font-display text-3xl sm:text-4xl">Prêt à prendre rendez-vous ?</h2>
            <p class="mt-2 text-sm text-teal-50 sm:text-base">Créez votre compte patient, ou retrouvez votre espace.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('register.patient') }}" class="inline-flex min-h-[48px] items-center justify-center rounded-full bg-white px-7 text-base font-semibold text-[#115E59] transition-colors hover:bg-[#F0FDFA] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Créer mon compte patient</a>
                <a href="{{ route('login') }}" class="inline-flex min-h-[48px] items-center justify-center rounded-full border-2 border-white px-7 text-base font-semibold text-white transition-colors hover:bg-white/10 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Se connecter</a>
            </div>
        </div>
    </section>

    {{-- ===== PIED DE PAGE ===== --}}
    <footer class="border-t border-[#CCFBF1] bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-10">
            <p class="flex items-baseline gap-2">
                <span class="font-display text-2xl text-[#115E59]">Sunu Santé</span>
                <span class="text-sm text-slate-600">· Jërëjëf</span>
            </p>
            <p class="text-sm text-slate-600">© {{ date('Y') }} Sunu Santé. Tous droits réservés.</p>
        </div>
    </footer>
</x-app-layout>
