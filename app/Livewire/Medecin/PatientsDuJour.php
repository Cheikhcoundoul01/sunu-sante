<?php

namespace App\Livewire\Medecin;

use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Services\FileAttenteService;
use Carbon\Carbon;
use Livewire\Component;

class PatientsDuJour extends Component
{
    public ?string $date = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('Médecin'), 403);
        $this->date = Carbon::now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->resetValidation();
    }

    public function render(FileAttenteService $service)
    {
        $medecin = auth()->user();
        $date = $this->date ? Carbon::parse($this->date) : Carbon::now();

        $rdvs = RendezVous::query()
            ->where('medecin_id', $medecin->id)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->with(['patient', 'service'])
            ->orderBy('heure_debut')
            ->get();

        // Pour chaque RDV, récupérer l'entrée de file si elle existe
        $rdvsAvecFile = $rdvs->map(function ($rdv) use ($service) {
            $entree = null;
            if (! $rdv->estIntervention()) {
                $entree = FileEntree::query()
                    ->where('rendez_vous_id', $rdv->id)
                    ->first();
            }
            return [
                'rdv' => $rdv,
                'entree' => $entree,
            ];
        });

        return view('livewire.medecin.patients-du-jour', [
            'date' => $date,
            'rdvsAvecFile' => $rdvsAvecFile,
        ])->layout('layouts.app');
    }
}