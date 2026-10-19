<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-teal-800">Mon planning</h1>
            <p class="mt-1 text-sm text-slate-600">Visualisez vos créneaux libres et rendez-vous du jour.</p>
        </div>
        <div class="flex items-center gap-2">
            <label for="date" class="text-sm font-medium text-slate-700">Date :</label>
            <input type="date" id="date" wire:model="date" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
    </div>

    {{-- Créneaux libres --}}
    <x-card class="mb-6 p-4">
        <h2 class="text-lg font-medium text-teal-800">Créneaux libres</h2>

        @if (empty($creneauxDisponibles))
            <p class="mt-3 text-sm text-slate-500">Aucun créneau libre pour cette date.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($creneauxDisponibles as $dispo)
                    <div class="p-3 rounded-lg border border-slate-200 bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-900">{{ $dispo['disponibilite_id'] ? 'Disponibilité #' . $dispo['disponibilite_id'] : 'Disponibilité' }}</p>
                                <p class="text-sm text-slate-600">
                                    @foreach ($dispo['creneaux'] as $creneau)
                                        <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-800 mr-1 mb-1">
                                            {{ $creneau['debut'] }}–{{ $creneau['fin'] }}
                                        </span>
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    {{-- Rendez-vous du jour --}}
    <x-card class="p-4">
        <h2 class="text-lg font-medium text-teal-800">Rendez-vous du {{ $jour->format('d/m/Y') }}</h2>

        @if ($rdvs->isEmpty())
            <p class="mt-3 text-sm text-slate-500">Aucun rendez-vous pour cette date.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($rdvs as $rdv)
                    <div class="p-4 rounded-lg border border-slate-200 bg-white">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-slate-900 truncate">{{ $rdv->patient->name }}</p>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $rdv->statut === 'Validé' ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-800' }}">
                                        {{ $rdv->statut }}
                                    </span>
                                    @if ($rdv->estIntervention())
                                        <span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-800">Intervention</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ $rdv->service->nom }}</p>
                                <p class="text-sm text-slate-600">{{ \Carbon\Carbon::parse($rdv->heure_debut)->format('H:i') }} ({{ $rdv->duree_prevue }} min)</p>
                                @if ($rdv->motif)
                                    <p class="mt-1 text-sm text-slate-500">{{ $rdv->motif }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium text-teal-700 bg-teal-50">
                                    {{ \Carbon\Carbon::parse($rdv->heure_debut)->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</div>