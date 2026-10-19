<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-teal-800">Statistiques du service</h1>
            <p class="mt-1 text-sm text-slate-600">Service : {{ $stats['service']->nom }} | {{ $stats['date'] }}</p>
        </div>
        <div>
            <label for="date" class="text-sm font-medium text-slate-700">Date :</label>
            <input type="date" id="date" wire:model="date" class="ml-2 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
    </div>

    {{-- Rendez-vous --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Total RDV</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-800">{{ $stats['rdv']['total'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Validés</h3>
            <p class="mt-1 text-2xl font-semibold text-green-700">{{ $stats['rdv']['valides'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Demandés</h3>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $stats['rdv']['demandes'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Annulés</h3>
            <p class="mt-1 text-2xl font-semibold text-red-700">{{ $stats['rdv']['annules'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Consultations</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-700">{{ $stats['rdv']['consultations'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Interventions</h3>
            <p class="mt-1 text-2xl font-semibold text-purple-700">{{ $stats['rdv']['interventions'] }}</p>
        </x-card>
    </div>

    {{-- File d'attente --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Total file</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-800">{{ $stats['file']['total'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">En attente</h3>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $stats['file']['en_attente'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">En cours</h3>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ $stats['file']['en_cours'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Terminés</h3>
            <p class="mt-1 text-2xl font-semibold text-green-700">{{ $stats['file']['termine'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Absents</h3>
            <p class="mt-1 text-2xl font-semibold text-red-700">{{ $stats['file']['absent'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Prioritaires</h3>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $stats['file']['prioritaires'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Temps attente moy.</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-700">{{ $stats['file']['temps_attente_moyen_min'] }} min</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Taux absentéisme</h3>
            <p class="mt-1 text-2xl font-semibold text-red-700">{{ $stats['file']['taux_absenteisme_pct'] }}%</p>
        </x-card>
    </div>

    {{-- Personnel --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Médecins</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-700">{{ $stats['personnel']['medecins'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Secrétaires</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-700">{{ $stats['personnel']['secretaires'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Agents accueil</h3>
            <p class="mt-1 text-2xl font-semibold text-teal-700">{{ $stats['personnel']['agents_accueil'] }}</p>
        </x-card>
    </div>
</div>