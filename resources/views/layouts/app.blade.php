<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Sunu Santé') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        @if (config('demo.enabled'))
            <div class="bg-amber-100 border-b border-amber-300 px-4 py-2 text-center text-sm font-medium text-amber-800" role="status">
                Mode démonstration : données fictives
            </div>
        @endif
        @auth
            @unless (auth()->user()->hasRole('Administrateur'))
                <header class="border-b border-slate-200 bg-white">
                    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                        <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0F766E]">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#0F766E] text-white" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                            <span class="text-lg font-semibold text-[#115E59]">Sunu Santé</span>
                        </a>
                        <div class="flex items-center gap-3">
                            <span class="hidden max-w-[12rem] truncate text-sm font-medium text-slate-700 sm:inline">{{ auth()->user()->nomAffiche }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex h-10 items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors hover:border-red-300 hover:bg-red-50 hover:text-red-700 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                                    Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </header>
            @endunless
        @endauth
        <div class="min-h-screen flex flex-col">
            <main class="flex-1">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
        @livewireScripts
    </body>
</html>
