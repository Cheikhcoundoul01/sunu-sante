<?php

namespace App\Livewire\Secretaire;

use App\Mail\RendezVousAnnule;
use App\Mail\RendezVousValide;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class RendezVousSecretaire extends Component
{
    use WithPagination;

    public string $filtreStatut = 'Demandé';
    public ?string $search = null;
    public ?string $dateDebut = null;
    public ?string $dateFin = null;

    public ?string $messageSucces = null;
    public ?string $messageErreur = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('rdv.voir'), 403);
    }

    public function valider(int $id): void
    {
        abort_unless(auth()->user()->can('rdv.valider'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $rdv = RendezVous::whereKey($id)
            ->where('service_id', auth()->user()->service_id)
            ->first();

        if (! $rdv) {
            $this->messageErreur = 'Rendez-vous introuvable dans votre service.';
            return;
        }

        if ($rdv->statut !== RendezVous::STATUT_DEMANDE) {
            $this->messageErreur = 'Seuls les rendez-vous au statut « Demandé » peuvent être validés.';
            return;
        }

        try {
            $rdv->update(['statut' => RendezVous::STATUT_VALIDE]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($rdv)
                ->event('validation_rendez_vous')
                ->log('Rendez-vous validé par la secrétaire');

            Mail::to($rdv->patient->email)->send(new RendezVousValide($rdv->fresh()));

            $this->messageSucces = 'Rendez-vous validé. Email de confirmation envoyé au patient.';
        } catch (\Exception $e) {
            $this->messageErreur = 'Erreur lors de la validation.';
        }
    }

    public function rejeter(int $id): void
    {
        abort_unless(auth()->user()->can('rdv.rejeter'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $rdv = RendezVous::whereKey($id)
            ->where('service_id', auth()->user()->service_id)
            ->first();

        if (! $rdv) {
            $this->messageErreur = 'Rendez-vous introuvable dans votre service.';
            return;
        }

        if ($rdv->statut !== RendezVous::STATUT_DEMANDE) {
            $this->messageErreur = 'Seuls les rendez-vous au statut « Demandé » peuvent être rejetés.';
            return;
        }

        try {
            $rdv->update(['statut' => RendezVous::STATUT_ANNULE]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($rdv)
                ->event('rejet_rendez_vous')
                ->log('Rendez-vous rejeté par la secrétaire');

            Mail::to($rdv->patient->email)->send(new RendezVousAnnule($rdv->fresh()));

            $this->messageSucces = 'Demande de rendez-vous rejetée. Le créneau est libéré. Email envoyé au patient.';
        } catch (\Exception $e) {
            $this->messageErreur = 'Erreur lors du rejet.';
        }
    }

    public function render()
    {
        $query = RendezVous::query()
            ->where('service_id', auth()->user()->service_id)
            ->with(['patient', 'medecin', 'service'])
            ->when($this->filtreStatut !== 'Tous', function ($q) {
                $q->where('statut', $this->filtreStatut);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('patient', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%')
                       ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->dateDebut, function ($q) {
                $q->whereDate('date', '>=', $this->dateDebut);
            })
            ->when($this->dateFin, function ($q) {
                $q->whereDate('date', '<=', $this->dateFin);
            })
            ->orderBy('date')
            ->orderBy('heure_debut');

        $rdvs = $query->paginate(15);

        return view('livewire.secretaire.rendez-vous', [
            'rdvs' => $rdvs,
            'statuts' => ['Demandé', 'Validé', 'Annulé', 'Tous'],
        ])->layout('layouts.app');
    }
}