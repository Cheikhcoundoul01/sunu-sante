<?php

namespace App\Livewire\Secretaire;

use App\Exceptions\DossierDejaTraiteException;
use App\Models\User;
use App\Services\PatientAccountService;
use Livewire\Component;

class Patients extends Component
{
    public string $filtre = 'En attente';

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('patients.view'), 403);
    }

    public function valider(int $id): void
    {
        abort_unless(auth()->user()->can('patients.valider'), 403);

        $this->traiter($id, 'valider');
    }

    public function rejeter(int $id): void
    {
        abort_unless(auth()->user()->can('patients.rejeter'), 403);

        $this->traiter($id, 'rejeter');
    }

    protected function traiter(int $id, string $action): void
    {
        $this->messageSucces = null;
        $this->messageErreur = null;

        $patient = User::whereKey($id)->firstOrFail();
        $secretaire = auth()->user();

        try {
            $service = app(PatientAccountService::class);

            if ($action === 'valider') {
                $service->valider($patient, $secretaire);
                $this->messageSucces = 'Compte patient validé. Un email de confirmation a été envoyé.';
            } else {
                $service->rejeter($patient, $secretaire);
                $this->messageSucces = 'Compte patient rejeté.';
            }
        } catch (DossierDejaTraiteException $e) {
            $this->messageErreur = 'Dossier déjà traité';
        }
    }

    public function render()
    {
        $patients = User::role('Patient')
            ->when($this->filtre !== 'Tous', function ($query) {
                $query->where('statut_compte', $this->filtre);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.secretaire.patients', [
            'patients' => $patients,
            'statuts' => ['En attente', 'Actif', 'Rejeté', 'Tous'],
        ])->layout('layouts.app');
    }
}
