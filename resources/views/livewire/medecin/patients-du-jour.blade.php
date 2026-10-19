<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-teal-800">Patients du jour</h1>
            <p class="mt-1 text-sm text-slate-600">Consultez vos patients et leur position dans la file d'attente.</p>
        </div>
        <div class="flex items-center gap-2">
            <label for="date" class="text-sm font-medium text-slate-700">Date :</label>
            <input type="date" id="date" wire:model="date" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
    </div>

    @if ($rdvsAvecFile->isEmpty())
        <x-card class="p-8 text-center">
            <svg class="mx-auto w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h2 class="mt-3 text-lg font-medium text-slate-900">Aucun patient pour cette date</h2>
            <p class="mt-1 text-sm text-slate-600">Vous n'avez pas de rendez-vous actif pour ce jour.</p>
        </x-card>
    @else
        <div class="space-y-3">
            @foreach ($rdvsAvecFile as $item)
                @php
                    $rdv = $item['rdv'];
                    $entree = $item['entree'];
                @endphp
                <x-card class="p-4">
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

                        <div class="flex items-center gap-3">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium text-teal-700 bg-teal-50">
                                {{ \Carbon\Carbon::parse($rdv->heure_debut)->format('H:i') }}
                            </span>

                            @if ($entree)
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $entree->statut === 'En cours' ? 'bg-blue-50 text-blue-800'
                                        : ($entree->statut === 'Terminé' ? 'bg-green-50 text-green-800'
                                        : ($entree->statut === 'Absent' ? 'bg-purple-50 text-purple-800'
                                        : 'bg-amber-50 text-amber-800')) }}">
                                        {{ $entree->statut }}
                                    </span>
                                    <span class="text-xs text-slate-500">Pos: {{ $entree->position }}</span>
                                    @if ($entree->priorite)
                                        <span class="inline-flex rounded-full bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-800">★</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-500">Hors file</span>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>