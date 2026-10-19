<?php

namespace App\Livewire\Patient;

use App\Models\Disponibilite;
use App\Models\Service;
use App\Models\User;
use App\Services\CreneauService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Livewire\Component;

class PrendreRendezVous extends Component
{
    public ?int $serviceId = null;
    public ?int $medecinId = null;
    public ?string $date = null;
    public ?string $heureDebut = null;
    public ?string $motif = null;

    public ?string $messageSucces = null;
    public ?string $messageErreur = null;
    public bool $rdvCree = false;

    public function mount(): void
    {
        $this->date = Carbon::now()->addDay()->toDateString();
    }

    public function updatedServiceId(): void
    {
        $this->medecinId = null;
        $this->date = Carbon::now()->addDay()->toDateString();
        $this->heureDebut = null;
        $this->resetValidation();
    }

    public function updatedMedecinId(): void
    {
        $this->date = Carbon::now()->addDay()->toDateString();
        $this->heureDebut = null;
        $this->resetValidation();
    }

    public function updatedDate(): void
    {
        $this->heureDebut = null;
        $this->resetValidation();
    }

    public function setHeureDebut(string $heure): void
    {
        $this->heureDebut = $heure;
    }

    public function reserver(ReservationService $reservationService): void
    {
        $this->validate([
            'serviceId' => 'required|integer|exists:services,id',
            'medecinId' => 'required|integer|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'heureDebut' => 'required|date_format:H:i',
            'motif' => 'nullable|string|max:500',
        ]);

        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $rdv = $reservationService->reserver(
                auth()->user(),
                $this->medecinId,
                $this->date,
                $this->heureDebut,
                $this->motif
            );

            $this->messageSucces = 'Rendez-vous demandé avec succès. Il sera validé par une secrétaire.';
            $this->rdvCree = true;
            $this->heureDebut = null;
            $this->resetValidation();
        } catch (\Exception $e) {
            $this->messageErreur = $e->getMessage();
        }
    }

    public function render(CreneauService $creneaux)
    {
        $services = Service::query()->where('actif', true)->orderBy('nom')->get();

        $medecins = $this->serviceId
            ? User::role('Médecin')->where('service_id', $this->serviceId)->orderBy('name')->get()
            : collect();

        $creneauxDisponibles = collect();
        if ($this->medecinId && $this->date) {
            $jour = Carbon::parse($this->date)->startOfDay();
            $creneauxDisponibles = collect($creneaux->creneauxLibresDuJour($this->medecinId, $jour));
        }

        return view('livewire.patient.prendre-rendez-vous', [
            'services' => $services,
            'medecins' => $medecins,
            'creneauxDisponibles' => $creneauxDisponibles,
        ])->layout('layouts.patient');
    }
}