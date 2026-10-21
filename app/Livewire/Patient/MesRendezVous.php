<?php

namespace App\Livewire\Patient;

use App\Models\RendezVous;
use App\Services\ReservationService;
use Carbon\Carbon;
use Livewire\Component;

class MesRendezVous extends Component
{
    public ?int $editingId = null;
    public ?int $editMedecinId = null;
    public ?string $editDate = null;
    public ?string $editHeureDebut = null;
    public ?string $editMotif = null;

    public ?string $messageSucces = null;
    public ?string $messageErreur = null;

    /** Onglet actif de la page « Mes rendez-vous » (présentation). */
    public string $onglet = 'a-venir';

    public function setOnglet(string $onglet): void
    {
        if (in_array($onglet, ['a-venir', 'passes', 'annules'], true)) {
            $this->onglet = $onglet;
        }
    }

    public function render()
    {
        $rdvs = RendezVous::query()
            ->where('patient_id', auth()->id())
            ->with(['medecin', 'service'])
            ->orderByDesc('date')
            ->orderByDesc('heure_debut')
            ->get();

        return view('livewire.patient.mes-rendez-vous', [
            'rdvs' => $rdvs,
        ])->layout('layouts.patient');
    }

    public function ouvrirModification(RendezVous $rdv): void
    {
        if ($rdv->patient_id !== auth()->id()) {
            $this->messageErreur = 'Vous n\'êtes pas autorisé à modifier ce rendez-vous.';
            return;
        }

        if (! $rdv->estActif()) {
            $this->messageErreur = 'Seuls les rendez-vous actifs peuvent être modifiés.';
            return;
        }

        $this->editingId = $rdv->id;
        $this->editMedecinId = $rdv->medecin_id;
        $this->editDate = $rdv->date->toDateString();
        $this->editHeureDebut = substr($rdv->heure_debut, 0, 5);
        $this->editMotif = $rdv->motif;
        $this->messageErreur = null;
        $this->messageSucces = null;
    }

    public function annulerModification(): void
    {
        $this->editingId = null;
        $this->editMedecinId = null;
        $this->editDate = null;
        $this->editHeureDebut = null;
        $this->editMotif = null;
        $this->resetValidation();
    }

    public function setEditHeureDebut(string $heure): void
    {
        $this->editHeureDebut = $heure;
    }

    public function sauvegarderModification(ReservationService $reservationService): void
    {
        $this->validate([
            'editMedecinId' => 'required|integer|exists:users,id',
            'editDate' => 'required|date|after_or_equal:today',
            'editHeureDebut' => 'required|date_format:H:i',
            'editMotif' => 'nullable|string|max:500',
        ]);

        $this->messageErreur = null;
        $this->messageSucces = null;

        try {
            $rdv = RendezVous::findOrFail($this->editingId);

            $reservationService->modifier(
                $rdv,
                auth()->user(),
                $this->editMedecinId,
                $this->editDate,
                $this->editHeureDebut,
                $this->editMotif
            );

            $this->messageSucces = 'Rendez-vous modifié. Il repasse en attente de validation.';
            $this->annulerModification();
        } catch (\Exception $e) {
            report($e);
            $this->messageErreur = 'Impossible de modifier ce rendez-vous.';
        }
    }

    public function annuler(RendezVous $rdv, ReservationService $reservationService): void
    {
        if (! $rdv->estActif()) {
            $this->messageErreur = 'Ce rendez-vous ne peut pas être annulé.';
            return;
        }

        $this->messageErreur = null;
        $this->messageSucces = null;

        try {
            $reservationService->annuler($rdv, auth()->user());
            $this->messageSucces = 'Rendez-vous annulé. Le créneau est libéré.';
        } catch (\Exception $e) {
            report($e);
            $this->messageErreur = 'Impossible d\'annuler ce rendez-vous.';
        }
    }

    public function getStatutBadgeClass(RendezVous $rdv): string
    {
        return match ($rdv->statut) {
            RendezVous::STATUT_DEMANDE => 'bg-amber-50 text-amber-800',
            RendezVous::STATUT_VALIDE => 'bg-green-50 text-green-800',
            RendezVous::STATUT_ANNULE => 'bg-red-50 text-red-800',
            default => 'bg-slate-50 text-slate-800',
        };
    }
}