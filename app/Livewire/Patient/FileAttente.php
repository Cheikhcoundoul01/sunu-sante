<?php

namespace App\Livewire\Patient;

use App\Models\RendezVous;
use App\Services\FileAttenteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class FileAttente extends Component
{
    public function render(FileAttenteService $service)
    {
        $patient = auth()->user();

        $entree = null;
        $rdvDuJour = null;
        $prochainRdv = null;
        $erreurTechnique = false;

        try {
            $entree = $service->getEntreePatient($patient->id);

            if (! $entree) {
                $rdvDuJour = RendezVous::query()
                    ->where('patient_id', $patient->id)
                    ->whereDate('date', Carbon::now()->toDateString())
                    ->orderBy('heure_debut')
                    ->with('medecin')
                    ->first();
            }

            // Prochain rendez-vous à venir (un autre jour), en lecture seule.
            $prochainRdv = RendezVous::query()
                ->where('patient_id', $patient->id)
                ->whereIn('statut', RendezVous::statutsActifs())
                ->whereDate('date', '>', Carbon::now()->toDateString())
                ->orderBy('date')
                ->orderBy('heure_debut')
                ->with(['medecin', 'service'])
                ->first();
        } catch (Throwable $e) {
            $erreurTechnique = true;

            Log::error('Ma file : échec de récupération des données.', [
                'patient_id' => $patient->id,
                'exception' => $e,
            ]);
        }

        $situation = 'erreur';

        if (! $erreurTechnique) {
            if ($entree) {
                $situation = 'entree';
            } elseif ($rdvDuJour === null) {
                $situation = 'aucun';
            } elseif ($rdvDuJour->statut === RendezVous::STATUT_VALIDE && $rdvDuJour->salle_id === null) {
                $situation = 'rdv_sans_entree';
            } else {
                $situation = 'indetermine';
            }
        }

        return view('livewire.patient.file-attente', [
            'entree' => $entree,
            'rdvDuJour' => $rdvDuJour,
            'prochainRdv' => $prochainRdv,
            'situation' => $situation,
        ])->layout('layouts.patient');
    }
}
