<?php

namespace App\Livewire\Medecin;

use App\Models\RendezVous;
use App\Services\CreneauService;
use Carbon\Carbon;
use Livewire\Component;

class Planning extends Component
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

    public function render(CreneauService $creneaux)
    {
        $medecin = auth()->user();
        $date = $this->date ? Carbon::parse($this->date) : Carbon::now();

        $creneauxDisponibles = $creneaux->creneauxLibresDuJour($medecin->id, $date);

        $rdvs = RendezVous::query()
            ->where('medecin_id', $medecin->id)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->with(['patient', 'service'])
            ->orderBy('heure_debut')
            ->get();

        return view('livewire.medecin.planning', [
            'jour' => $date,
            'creneauxDisponibles' => $creneauxDisponibles,
            'rdvs' => $rdvs,
        ])->layout('layouts.app');
    }
}
