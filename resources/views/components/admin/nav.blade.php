@php
    // Les noms viennent de ton route:list. Pour ajouter une page : copie une ligne.
    $liens = [
        ['label' => 'Tableau de bord',       'route' => 'admin.dashboard'],
        ['label' => 'Journal d\'activité',   'route' => 'admin.journal'],
        ['label' => 'Statistiques',          'route' => 'admin.statistiques'],
        ['label' => 'Gestion des services',  'route' => 'admin.services'],
        ['label' => 'Gestion du personnel',  'route' => 'admin.personnel'],
    ];
    $user = auth()->user();
@endphp

<nav x-data="{ ouvert: false }" aria-label="Navigation administrateur"
     class="mb-8 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-6">
    <div class="flex items-center justify-between gap-4">

        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0F766E]">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#0F766E] text-white" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block text-lg font-semibold text-[#115E59]">Sunu Santé</span>
                <span class="block text-xs font-medium uppercase tracking-wide text-slate-500">Administration</span>
            </span>
        </a>

        <div class="hidden items-center gap-1 md:flex">
            @foreach ($liens as $lien)
                @php $actif = request()->routeIs($lien['route']); @endphp
                <a href="{{ route($lien['route']) }}" @if($actif) aria-current="page" @endif
                   class="inline-flex h-11 items-center rounded-full px-4 text-sm font-medium transition-colors motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0F766E]
                          {{ $actif ? 'bg-[#F0FDFA] text-[#0F766E]' : 'text-slate-700 hover:bg-slate-100 hover:text-[#0F766E]' }}">
                    {{ $lien['label'] }}
                </a>
            @endforeach
        </div>

        <div class="hidden items-center gap-3 md:flex">
            @if ($user)
                <span class="grid h-10 w-10 place-items-center rounded-full bg-[#CCFBF1] text-sm font-bold text-[#115E59]" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <span class="max-w-[10rem] truncate text-sm font-medium text-slate-800">{{ $user->name }}</span>
            @endif
            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex h-11 items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors hover:border-red-300 hover:bg-red-50 hover:text-red-700 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                        Déconnexion
                    </button>
                </form>
            @endif
        </div>

        <button type="button" @click="ouvert = !ouvert" :aria-expanded="ouvert" aria-label="Ouvrir le menu"
                class="grid h-11 w-11 place-items-center rounded-full border border-slate-300 text-slate-700 md:hidden focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0F766E]">
            <svg x-show="!ouvert" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-show="ouvert" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <div x-show="ouvert" x-cloak x-transition.opacity class="mt-3 space-y-1 border-t border-slate-100 pt-3 md:hidden">
        @foreach ($liens as $lien)
            @php $actif = request()->routeIs($lien['route']); @endphp
            <a href="{{ route($lien['route']) }}"
               class="flex min-h-[44px] items-center rounded-xl px-4 text-base font-medium {{ $actif ? 'bg-[#F0FDFA] text-[#0F766E]' : 'text-slate-700 hover:bg-slate-100' }}">
                {{ $lien['label'] }}
            </a>
        @endforeach
        @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex min-h-[44px] w-full items-center rounded-xl px-4 text-base font-medium text-red-700 hover:bg-red-50">
                    Déconnexion
                </button>
            </form>
        @endif
    </div>
</nav>