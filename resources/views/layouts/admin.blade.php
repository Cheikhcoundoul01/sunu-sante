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
        <div class="min-h-screen flex flex-col">
            <main class="flex-1">
                <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
                    <x-admin.nav />
                </div>
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
        @livewireScripts
    </body>
</html>
