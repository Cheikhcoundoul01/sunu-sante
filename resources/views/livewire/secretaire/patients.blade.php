<div class="max-w-5xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-teal-800">Patients</h1>
    <p class="mt-1 text-sm text-slate-600">Valider ou rejeter les comptes patients en attente.</p>

    @if ($messageSucces)
        <div class="mt-4 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ $messageSucces }}
        </div>
    @endif

    @if ($messageErreur)
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $messageErreur }}
        </div>
    @endif

    <div class="mt-6">
        <label for="filtre" class="block text-sm font-medium text-slate-700">Filtrer par statut</label>
        <select id="filtre" wire:model.live="filtre"
            class="mt-1 block w-64 rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
            @foreach ($statuts as $statut)
                <option value="{{ $statut }}">{{ $statut }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Demandé le</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $patient)
                    <tr wire:key="patient-{{ $patient->id }}">
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $patient->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $patient->email }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($patient->statut_compte === 'Actif')
                                <span class="inline-flex rounded-full bg-teal-50 px-2 py-1 text-xs font-medium text-teal-800">Actif</span>
                            @elseif ($patient->statut_compte === 'Rejeté')
                                <span class="inline-flex rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700">Rejeté</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800">En attente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $patient->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($patient->statut_compte === 'En attente')
                                <button type="button" wire:click="valider({{ $patient->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-700 disabled:opacity-50">
                                    Valider
                                </button>
                                <button type="button" wire:click="rejeter({{ $patient->id }})"
                                    wire:loading.attr="disabled"
                                    class="ml-2 inline-flex items-center justify-center rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:opacity-50">
                                    Rejeter
                                </button>
                            @else
                                <span class="text-xs text-slate-400">
                                    Traité par {{ $patient->traitePar?->name ?? '—' }}
                                    @if ($patient->traite_at)
                                        le {{ $patient->traite_at->format('d/m/Y H:i') }}
                                    @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Aucun compte patient à afficher.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
