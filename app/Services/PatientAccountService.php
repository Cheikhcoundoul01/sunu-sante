<?php

namespace App\Services;

use App\Exceptions\DossierDejaTraiteException;
use App\Mail\ComptePatientValide;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PatientAccountService
{
    /**
     * Valider un compte patient (En attente -> Actif).
     * La première décision est définitive.
     *
     * @throws DossierDejaTraiteException
     */
    public function valider(User $patient, User $secretaire): User
    {
        return $this->traiter($patient, $secretaire, 'Actif');
    }

    /**
     * Rejeter un compte patient (En attente -> Rejeté).
     * La première décision est définitive.
     *
     * @throws DossierDejaTraiteException
     */
    public function rejeter(User $patient, User $secretaire): User
    {
        return $this->traiter($patient, $secretaire, 'Rejeté');
    }

    /**
     * Traiter un dossier patient dans une transaction verrouillée.
     * Le verrou (SELECT ... FOR UPDATE) et le contrôle du statut empêchent
     * tout double traitement concurrent : seule la première décision s'applique.
     *
     * @throws DossierDejaTraiteException
     */
    protected function traiter(User $patient, User $secretaire, string $nouveauStatut): User
    {
        return DB::transaction(function () use ($patient, $secretaire, $nouveauStatut) {
            $dossier = User::whereKey($patient->getKey())->lockForUpdate()->firstOrFail();

            if ($dossier->statut_compte !== 'En attente') {
                throw new DossierDejaTraiteException();
            }

            $dossier->statut_compte = $nouveauStatut;
            $dossier->traite_par_id = $secretaire->getKey();
            $dossier->traite_at = now();
            $dossier->save();

            activity()
                ->causedBy($secretaire)
                ->performedOn($dossier)
                ->event($nouveauStatut === 'Actif' ? 'validation_compte_patient' : 'rejet_compte_patient')
                ->withProperties([
                    'statut' => $nouveauStatut,
                    'patient_email' => $dossier->email,
                    'traite_par' => $secretaire->email,
                ])
                ->log($nouveauStatut === 'Actif'
                    ? 'Validation du compte patient'
                    : 'Rejet du compte patient');

            // V1 : email envoyé uniquement à la validation du compte patient.
            if ($nouveauStatut === 'Actif') {
                Mail::to($dossier->email)->send(new ComptePatientValide($dossier));
            }

            return $dossier;
        });
    }
}
