<?php

namespace App\Livewire\Accueil;

use App\Models\FileEntree;
use App\Services\FileAttenteService;
use Carbon\Carbon;
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
        } catch (\Throwable $e) {
            $this->messageErreur = $this->messageErreurAffiche(
                $e,
                'Une erreur est survenue lors de la génération de la file. Veuillez réessayer.'
            );
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
        abort_unless(auth()->user()->can('file.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $entree = $this->entreeAutorisee($id);

        try {
            $service = app(FileAttenteService::class);
            $service->remettreEnAttente($entree->id, auth()->user()->service_id, Carbon::now());
            $this->messageSucces = 'Patient remis en attente (fin de file).';
        } catch (\Throwable $e) {
            $this->messageErreur = $this->messageErreurAffiche(
                $e,
                'Une erreur est survenue lors de la remise en attente. Veuillez réessayer.'
            );
        }
    }

    public function ouvrirPriorite(int $id): void
    {
        $this->entreeAutorisee($id);

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

        abort_unless($this->prioritaireId !== null, 403);

        $entree = $this->entreeAutorisee($this->prioritaireId);

        try {
            $service = app(FileAttenteService::class);
            $service->passerPrioritaire($entree->id, $this->motifPriorite, auth()->user()->service_id, Carbon::now());
            $this->messageSucces = 'Patient passé en priorité.';
            $this->prioritaireId = null;
            $this->motifPriorite = null;
        } catch (\Throwable $e) {
            $this->messageErreur = $this->messageErreurAffiche(
                $e,
                'Une erreur est survenue lors du passage prioritaire. Veuillez réessayer.'
            );
        }
    }

    protected function changerStatut(int $id, string $statut): void
    {
        abort_unless(auth()->user()->can('file.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $entree = $this->entreeAutorisee($id);

        try {
            $service = app(FileAttenteService::class);
            $service->changerStatut($entree->id, $statut, null, auth()->user()->service_id, Carbon::now());
            $this->messageSucces = "Statut mis à jour : $statut";
        } catch (\Throwable $e) {
            $this->messageErreur = $this->messageErreurAffiche(
                $e,
                'Une erreur est survenue lors du changement de statut. Veuillez réessayer.'
            );
        }
    }

    /**
     * Messages métier connus de FileAttenteService, seuls autorisés à l'affichage.
     *
     * @return array<int, string>
     */
    private function messagesMetier(): array
    {
        return [
            'Le motif est obligatoire pour un passage prioritaire',
            'Seuls les patients en attente peuvent passer en priorité',
            'Seuls les patients absents peuvent être remis en attente',
        ];
    }

    private function messageErreurAffiche(\Throwable $e, string $messageParDefaut): string
    {
        $message = $e->getMessage();

        if (in_array($message, $this->messagesMetier(), true)
            || str_starts_with($message, 'Transition non autorisée de ')) {
            return $message;
        }

        report($e);

        return $messageParDefaut;
    }

    protected function entreeAutorisee(int $id): FileEntree
    {
        $entree = FileEntree::query()
            ->whereKey($id)
            ->where('service_id', auth()->user()->service_id)
            ->whereDate('date', Carbon::now()->toDateString())
            ->first();

        abort_unless($entree !== null, 403);

        return $entree;
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
