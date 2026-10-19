<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Sunu Santé') }} · Mon espace</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900 pb-[calc(64px+env(safe-area-inset-bottom))] lg:pb-0">
        @if (config('demo.enabled'))
            <div class="bg-amber-100 border-b border-amber-300 px-4 py-2 text-center text-sm font-medium text-amber-800" role="status">
                Mode démonstration : données fictives
            </div>
        @endif
        <div class="min-h-screen flex flex-col">
            {{-- Navigation ordinateur (≥ lg) : pilules en haut --}}
            <nav class="hidden lg:flex lg:items-center lg:justify-center lg:gap-2 lg:px-4 lg:py-3 lg:bg-white lg:border-b lg:border-slate-200 lg:sticky lg:top-0 lg:z-40 lg:bg-white/95 lg:backdrop-blur-sm" role="navigation" aria-label="Navigation principale">
                {{-- Accueil --}}
                <a href="{{ route('patient.dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                           {{ request()->routeIs('patient.dashboard')
                               ? 'bg-teal-600 text-white shadow-sm'
                               : 'text-slate-700 hover:bg-teal-50 hover:text-teal-700' }}"
                   aria-current="{{ request()->routeIs('patient.dashboard') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="font-medium">Accueil</span>
                </a>

                {{-- Prendre rendez-vous --}}
                <a href="{{ route('patient.rdv.create') }}"
                   class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                           {{ request()->routeIs('patient.rdv.create')
                               ? 'bg-teal-600 text-white shadow-sm'
                               : 'text-slate-700 hover:bg-teal-50 hover:text-teal-700' }}"
                   aria-current="{{ request()->routeIs('patient.rdv.create') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="font-medium">Prendre RDV</span>
                </a>

                {{-- Mes rendez-vous --}}
                <a href="{{ route('patient.rdv.index') }}"
                   class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                           {{ request()->routeIs('patient.rdv.index')
                               ? 'bg-teal-600 text-white shadow-sm'
                               : 'text-slate-700 hover:bg-teal-50 hover:text-teal-700' }}"
                   aria-current="{{ request()->routeIs('patient.rdv.index') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">Mes RDV</span>
                </a>

                {{-- Ma file d'attente --}}
                <a href="{{ route('patient.file') }}"
                   class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                           {{ request()->routeIs('patient.file')
                               ? 'bg-teal-600 text-white shadow-sm'
                               : 'text-slate-700 hover:bg-teal-50 hover:text-teal-700' }}"
                   aria-current="{{ request()->routeIs('patient.file') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857" />
                    </svg>
                    <span class="font-medium">Ma file</span>
                </a>

                {{-- Mon profil --}}
                <a href="{{ route('patient.profil') }}"
                   class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]
                           {{ request()->routeIs('patient.profil')
                               ? 'bg-teal-600 text-white shadow-sm'
                               : 'text-slate-700 hover:bg-teal-50 hover:text-teal-700' }}"
                   aria-current="{{ request()->routeIs('patient.profil') ? 'page' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="font-medium">Mon profil</span>
                </a>

                {{-- Déconnexion --}}
                <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-red-50 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="font-medium">Déconnexion</span>
                    </button>
                </form>
        </nav>

        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    {{-- Barre d'onglets fixe en bas (mobile-first, < lg) --}}
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 h-16 bg-white border-t border-slate-200 pb-safe" style="padding-bottom: env(safe-area-inset-bottom);" role="navigation" aria-label="Navigation principale">
        <div class="flex h-full">
            {{-- Accueil --}}
            <a href="{{ route('patient.dashboard') }}"
               class="flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 transition-colors
                      {{ request()->routeIs('patient.dashboard')
                          ? 'text-teal-700 bg-teal-50'
                          : 'text-slate-500 hover:text-teal-700' }}"
               aria-current="{{ request()->routeIs('patient.dashboard') ? 'page' : 'false' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 011-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs font-medium">Accueil</span>
            </a>

            {{-- Prendre rendez-vous --}}
            <a href="{{ route('patient.rdv.create') }}"
               class="flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 transition-colors
                      {{ request()->routeIs('patient.rdv.create')
                          ? 'text-teal-700 bg-teal-50'
                          : 'text-slate-500 hover:text-teal-700' }}"
               aria-current="{{ request()->routeIs('patient.rdv.create') ? 'page' : 'false' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="text-xs font-medium">Prendre RDV</span>
            </a>

            {{-- Mes rendez-vous --}}
            <a href="{{ route('patient.rdv.index') }}"
               class="flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 transition-colors
                      {{ request()->routeIs('patient.rdv.index')
                          ? 'text-teal-700 bg-teal-50'
                          : 'text-slate-500 hover:text-teal-700' }}"
               aria-current="{{ request()->routeIs('patient.rdv.index') ? 'page' : 'false' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="text-xs font-medium">Mes RDV</span>
            </a>

            {{-- Ma file d'attente --}}
            <a href="{{ route('patient.file') }}"
               class="flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 transition-colors
                      {{ request()->routeIs('patient.file')
                          ? 'text-teal-700 bg-teal-50'
                          : 'text-slate-500 hover:text-teal-700' }}"
               aria-current="{{ request()->routeIs('patient.file') ? 'page' : 'false' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-xs font-medium">Ma file</span>
            </a>

            {{-- Mon profil --}}
            <a href="{{ route('patient.profil') }}"
               class="flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 transition-colors
                      {{ request()->routeIs('patient.profil')
                          ? 'text-teal-700 bg-teal-50'
                          : 'text-slate-500 hover:text-teal-700' }}"
               aria-current="{{ request()->routeIs('patient.profil') ? 'page' : 'false' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-xs font-medium">Mon profil</span>
            </a>

            {{-- Déconnexion --}}
            <form method="POST" action="{{ route('logout') }}" class="flex-1 flex">
                @csrf
                <button type="submit"
                        class="flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 text-slate-500 transition-colors hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E] motion-reduce:transition-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="text-xs font-medium">Déconnexion</span>
                </button>
            </form>
        </div>
    </nav>

    @livewireScripts
</body>
</html>