<?php

namespace App\Livewire\Accueil;

use App\Models\FileEntree;
use App\Services\FileAttenteService;
use Livewire\Component;
use Livewire\WithPagination;

class FileDuJour extends Component
{
    use WithPagination;

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

    public ?int $prioritaireId = null;

    public ?string $motifPriorite = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('file.voir'), 403);
    }

    public function generer(FileAttenteService $service): void
    {
        abort_unless(auth()->user()->can('file.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $creees = $service->genererFileDuJour(auth()->user()->service_id);
            $this->messageSucces = $creees > 0
                ? "File générée : $creees patient(s) ajouté(s)."
                : 'File déjà générée pour aujourd\'hui ou aucun RDV éligible.';
        } catch (\Exception $e) {
            $this->messageErreur = $e->getMessage();
        }
    }

    public function demarrer(int $id): void
    {
        $this->changerStatut($id, FileEntree::STATUT_EN_COURS);
    }

    public function terminer(int $id): void
    {
        $this->changerStatut($id, FileEntree::STATUT_TERMINE);
    }

    public function marquerAbsent(int $id): void
    {
        $this->changerStatut($id, FileEntree::STATUT_ABSENT);
    }

    public function remettreEnAttente(int $id): void
    {
        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $service = app(FileAttenteService::class);
            $service->remettreEnAttente($id);
            $this->messageSucces = 'Patient remis en attente (fin de file).';
        } catch (\Exception $e) {
            $this->messageErreur = $e->getMessage();
        }
    }

    public function ouvrirPriorite(int $id): void
    {
        $this->prioritaireId = $id;
        $this->motifPriorite = null;
    }

    public function confirmerPriorite(): void
    {
        abort_unless(auth()->user()->can('file.priorite'), 403);

        $this->validate([
            'motifPriorite' => 'required|string|max:500',
        ]);

        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $service = app(FileAttenteService::class);
            $service->passerPrioritaire($this->prioritaireId, $this->motifPriorite);
            $this->messageSucces = 'Patient passé en priorité.';
            $this->prioritaireId = null;
            $this->motifPriorite = null;
        } catch (\Exception $e) {
            $this->messageErreur = $e->getMessage();
        }
    }

    protected function changerStatut(int $id, string $statut): void
    {
        abort_unless(auth()->user()->can('file.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $service = app(FileAttenteService::class);
            $service->changerStatut($id, $statut);
            $this->messageSucces = "Statut mis à jour : $statut";
        } catch (\Exception $e) {
            $this->messageErreur = $e->getMessage();
        }
    }

    public function render(FileAttenteService $service)
    {
        $serviceId = auth()->user()->service_id;
        $file = $service->getFileDuJour($serviceId);

        return view('livewire.accueil.file-du-jour', [
            'file' => $file,
            'service' => auth()->user()->service?->nom,
        ])->layout('layouts.app');
    }
}
