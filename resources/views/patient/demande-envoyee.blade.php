@extends('layouts.guest')

@section('content')
    <style>
        .font-display { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; }
        @keyframes sunu-entree {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .sunu-entree { animation: sunu-entree 0.45s ease-out both; }
        @media (prefers-reduced-motion: reduce) {
            .sunu-entree { animation: none; }
        }
    </style>

    <div class="sunu-entree">
        <header class="mb-8 flex items-center justify-between gap-4">
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

        <x-card class="p-6 text-center sm:p-8">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
            </span>

            <h1 class="font-display mt-5 text-4xl leading-none text-[#115E59]">Demande envoyée</h1>

            <p class="mt-4 text-base leading-relaxed text-slate-600">
                Votre demande d'inscription a bien été reçue. Elle est en attente de validation par une secrétaire.
                Vous pourrez vous connecter dès la validation de votre compte.
            </p>

            <ol class="mt-8 space-y-4 text-left" aria-label="Étapes de votre inscription">
                <li class="flex gap-3">
                    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#0F766E] text-white" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Demande envoyée</p>
                        <p class="text-sm text-[#0F766E]">Terminée</p>
                    </div>
                </li>

                <li class="flex gap-3" aria-current="step">
                    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 border-[#0F766E] bg-white" aria-hidden="true">
                        <span class="h-2 w-2 rounded-full bg-[#0F766E]"></span>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Validation par la secrétaire</p>
                        <p class="text-sm text-[#0F766E]">En cours</p>
                    </div>
                </li>

                <li class="flex gap-3">
                    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 border-slate-300 bg-white text-xs font-semibold text-slate-500" aria-hidden="true">3</span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Email de confirmation puis connexion</p>
                        <p class="text-sm text-slate-500">À venir</p>
                    </div>
                </li>
            </ol>

            <a href="{{ route('login') }}"
               class="mt-8 inline-flex h-14 w-full items-center justify-center rounded-full bg-[#0F766E] text-base font-semibold text-white transition-colors hover:bg-[#115E59] motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                Retour à la connexion
            </a>

            <p class="mt-4 text-center text-base text-slate-700">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-semibold text-[#0F766E] underline underline-offset-4 hover:text-[#115E59] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                    Retour à l'accueil
                </a>
            </p>
        </x-card>
    </div>
@endsection
