<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-teal-800">Statistiques globales</h1>
            <p class="mt-1 text-sm text-slate-600">Vue d'ensemble de l'ensemble des services | {{ $stats['date'] }}</p>
        </div>
        <div>
            <label for="date" class="text-sm font-medium text-slate-700">Date :</label>
            <input type="date" id="date" wire:model="date" class="ml-2 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
    </div>

    {{-- Rendez-vous globaux --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-card class="p-4 border-l-4 border-teal-600">
            <h3 class="text-sm font-medium text-slate-600">Total RDV</h3>
            <p class="mt-1 text-3xl font-bold text-teal-800">{{ $stats['global']['rdv']['total'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-green-600">
            <h3 class="text-sm font-medium text-slate-600">Validés</h3>
            <p class="mt-1 text-3xl font-bold text-green-700">{{ $stats['global']['rdv']['valides'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-amber-600">
            <h3 class="text-sm font-medium text-slate-600">Demandés</h3>
            <p class="mt-1 text-3xl font-bold text-amber-700">{{ $stats['global']['rdv']['demandes'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-red-600">
            <h3 class="text-sm font-medium text-slate-600">Annulés</h3>
            <p class="mt-1 text-3xl font-bold text-red-700">{{ $stats['global']['rdv']['annules'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-purple-600">
            <h3 class="text-sm font-medium text-slate-600">Interventions</h3>
            <p class="mt-1 text-3xl font-bold text-purple-700">{{ $stats['global']['rdv']['interventions'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-teal-600">
            <h3 class="text-sm font-medium text-slate-600">Taux validation</h3>
            <p class="mt-1 text-3xl font-bold text-teal-700">{{ $stats['global']['rdv']['taux_validation_pct'] }}%</p>
        </x-card>
    </div>

    {{-- File d'attente globale --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
        <x-card class="p-4 border-l-4 border-teal-600">
            <h3 class="text-sm font-medium text-slate-600">Total file</h3>
            <p class="mt-1 text-2xl font-bold text-teal-800">{{ $stats['global']['file']['total'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-amber-600">
            <h3 class="text-sm font-medium text-slate-600">En attente</h3>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['global']['file']['en_attente'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-blue-600">
            <h3 class="text-sm font-medium text-slate-600">En cours</h3>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $stats['global']['file']['en_cours'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-green-600">
            <h3 class="text-sm font-medium text-slate-600">Terminés</h3>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ $stats['global']['file']['termine'] }}</p>
        </x-card>
        <x-card class="p-4 border-l-4 border-red-600">
            <h3 class="text-sm font-medium text-slate-600">Absents</h3>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ $stats['global']['file']['absent'] }}</p>
        </x-card>
    </div>

    {{-- Personnel global --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4 mb-6">
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Médecins</h3>
            <p class="mt-1 text-2xl font-bold text-teal-700">{{ $stats['global']['personnel']['medecins'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Secrétaires</h3>
            <p class="mt-1 text-2xl font-bold text-teal-700">{{ $stats['global']['personnel']['secretaires'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Agents accueil</h3>
            <p class="mt-1 text-2xl font-bold text-teal-700">{{ $stats['global']['personnel']['agents_accueil'] }}</p>
        </x-card>
        <x-card class="p-4">
            <h3 class="text-sm font-medium text-slate-600">Patients inscrits</h3>
            <p class="mt-1 text-2xl font-bold text-teal-700">{{ $stats['global']['patients']['total_inscrits'] }}</p>
        </x-card>
    </div>

    {{-- Top services --}}
    <x-card class="mb-6 p-4">
        <h2 class="text-lg font-semibold text-teal-800 mb-4">Top 3 services par volume de RDV</h2>
        @if ($stats['top_services']->isEmpty())
            <p class="text-sm text-slate-500">Aucune donnée disponible.</p>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Rang</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Service</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">RDV</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stats['top_services'] as $index => $service)
                            <tr class="{{ $loop->first ? 'bg-teal-50' : '' }}">
                                <td class="px-4 py-3 text-sm font-medium text-teal-800">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $service['nom'] }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-slate-700">{{ $service['rdv'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{-- Détail par service --}}
    <x-card class="p-4">
        <h2 class="text-lg font-semibold text-teal-800 mb-4">Détail par service</h2>
        @if (empty($stats['par_service']))
            <p class="text-sm text-slate-500">Aucun service actif.</p>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Service</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Total RDV</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Validés</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Demandés</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Annulés</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Interventions</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">File</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Médecins</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stats['par_service'] as $nom => $data)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $nom }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700">{{ $data['rdv']['total'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-green-700">{{ $data['rdv']['valides'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-amber-700">{{ $data['rdv']['demandes'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-700">{{ $data['rdv']['annules'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-purple-700">{{ $data['rdv']['interventions'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-teal-700">{{ $data['file']['total'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-teal-700">{{ $data['personnel']['medecins'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>