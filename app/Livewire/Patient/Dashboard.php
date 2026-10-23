<?php

namespace App\Livewire\Patient;

use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Services\FileAttenteService;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(FileAttenteService $fileService)
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $prenom = explode(' ', trim($user->name))[0];

        $maintenant = now('Africa/Dakar');

        $rdvsDuPatient = RendezVous::query()
            ->where('patient_id', $user->id)
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();

        $momentRdv = fn (RendezVous $rdv) => Carbon::parse(
            $rdv->date->format('Y-m-d').' '.$rdv->heure_debut,
            'Africa/Dakar'
        );

        $estAVenir = fn (RendezVous $rdv) => $rdv->estActif() && $momentRdv($rdv)->greaterThanOrEqualTo($maintenant);

        $prochainRdv = $rdvsDuPatient->filter($estAVenir)->first();

        $rdvActifsCount = $rdvsDuPatient->filter($estAVenir)->count();

        $rdvPassesCount = $rdvsDuPatient
            ->filter(fn (RendezVous $rdv) => $rdv->statut === RendezVous::STATUT_VALIDE && $momentRdv($rdv)->lessThan($maintenant))
            ->count();

        $entree = $fileService->getEntreePatient($user->id);

        $positionFile = $entree && in_array($entree->statut, [
            FileEntree::STATUT_EN_ATTENTE,
            FileEntree::STATUT_EN_COURS,
        ], true)
            ? $entree->position
            : null;

        return view('livewire.patient.dashboard', [
            'prenom' => $prenom,
            'prochainRdv' => $prochainRdv,
            'rdvActifsCount' => $rdvActifsCount,
            'rdvPassesCount' => $rdvPassesCount,
            'positionFile' => $positionFile,
        ])->layout('layouts.patient');
    }
}