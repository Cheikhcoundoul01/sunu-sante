<?php

namespace App\Services;

use App\Models\RendezVous;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use App\Mail\RendezVousAnnule;
use App\Mail\RendezVousModifie;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Service de réservation de rendez-vous avec gestion de la concurrence.
 * Utilise des transactions avec verrouillage FOR UPDATE et reprise sur deadlock.
 */
class ReservationService
{
    public const MAX_DEADLOCK_RETRIES = 3;

    /**
     * Crée un nouveau rendez-vous pour un patient.
     *
     * @throws \Exception Si le créneau n'est pas disponible ou en cas d'erreur de concurrence persistante
     */
    public function reserver(
        User $patient,
        int $medecinId,
        string $date,
        string $heureDebut,
        ?string $motif = null
    ): RendezVous {
        return $this->executeWithDeadlockRetry(function () use ($patient, $medecinId, $date, $heureDebut, $motif) {
            return DB::transaction(function () use ($patient, $medecinId, $date, $heureDebut, $motif) {
                $medecin = User::role('Médecin')
                    ->whereKey($medecinId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jour = Carbon::parse($date)->startOfDay();

                $this->verifierCreneauDisponible($medecinId, $jour, $heureDebut);

                $rdv = RendezVous::create([
                    'patient_id' => $patient->id,
                    'medecin_id' => $medecin->id,
                    'service_id' => $medecin->service_id,
                    'date' => $jour->toDateString(),
                    'heure_debut' => $heureDebut,
                    'duree_prevue' => CreneauService::DUREE_CRENEAU_MINUTES,
                    'statut' => RendezVous::STATUT_DEMANDE,
                    'motif' => $motif,
                ]);

                activity()
                    ->causedBy($patient)
                    ->performedOn($rdv)
                    ->event('creation_rendez_vous')
                    ->log('Rendez-vous demandé par le patient');

                return $rdv;
            });
        });
    }

    /**
     * Modifie un rendez-vous existant (patient propriétaire uniquement).
     * Le rendez-vous repasse en statut Demandé.
     *
     * @throws \Exception Si non autorisé, créneau indisponible ou erreur de concurrence
     */
    public function modifier(
        RendezVous $rdv,
        User $patient,
        int $nouveauMedecinId,
        string $nouvelleDate,
        string $nouvelleHeureDebut,
        ?string $motif = null
    ): RendezVous {
        return $this->executeWithDeadlockRetry(function () use ($rdv, $patient, $nouveauMedecinId, $nouvelleDate, $nouvelleHeureDebut, $motif) {
            return DB::transaction(function () use ($rdv, $patient, $nouveauMedecinId, $nouvelleDate, $nouvelleHeureDebut, $motif) {
                $rdv = RendezVous::whereKey($rdv->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->verifierPropriete($rdv, $patient);

                if ($rdv->statut === RendezVous::STATUT_ANNULE) {
                    throw new \Exception('Ce rendez-vous est annulé et ne peut plus être modifié.');
                }

                $ancienMedecinId = $rdv->medecin_id;
                $ancienneDate = $rdv->date;
                $ancienneHeureDebut = $rdv->heure_debut;

                $nouveauMedecin = User::role('Médecin')
                    ->whereKey($nouveauMedecinId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nouveauJour = Carbon::parse($nouvelleDate)->startOfDay();

                $creneauChange = $ancienMedecinId !== $nouveauMedecinId
                    || ! $ancienneDate->isSameDay($nouveauJour)
                    || $ancienneHeureDebut !== $nouvelleHeureDebut;

                if ($creneauChange) {
                    $this->verifierCreneauDisponible($nouveauMedecinId, $nouveauJour, $nouvelleHeureDebut, $rdv->id);
                }

                $rdv->update([
                    'medecin_id' => $nouveauMedecin->id,
                    'service_id' => $nouveauMedecin->service_id,
                    'date' => $nouveauJour->toDateString(),
                    'heure_debut' => $nouvelleHeureDebut,
                    'duree_prevue' => CreneauService::DUREE_CRENEAU_MINUTES,
                    'statut' => RendezVous::STATUT_DEMANDE,
                    'motif' => $motif,
                ]);

                activity()
                    ->causedBy($patient)
                    ->performedOn($rdv)
                    ->event('modification_rendez_vous')
                    ->log('Rendez-vous modifié par le patient');

                DB::afterCommit(function () use ($patient, $rdv) {
                    $this->envoyerEmail(function () use ($patient, $rdv) {
                        Mail::to($patient->email)->send(new RendezVousModifie($rdv->fresh()));
                    });
                });

                return $rdv;
            });
        });
    }

    /**
     * Annule un rendez-vous (patient propriétaire uniquement).
     * Le créneau est libéré.
     *
     * @throws \Exception Si non autorisé ou erreur de concurrence
     */
    public function annuler(RendezVous $rdv, User $patient): RendezVous
    {
        return $this->executeWithDeadlockRetry(function () use ($rdv, $patient) {
            return DB::transaction(function () use ($rdv, $patient) {
                $rdv = RendezVous::whereKey($rdv->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->verifierPropriete($rdv, $patient);

                if ($rdv->statut === RendezVous::STATUT_ANNULE) {
                    throw new \Exception('Ce rendez-vous est déjà annulé.');
                }

                $rdv->update([
                    'statut' => RendezVous::STATUT_ANNULE,
                ]);

                activity()
                    ->causedBy($patient)
                    ->performedOn($rdv)
                    ->event('annulation_rendez_vous')
                    ->log('Rendez-vous annulé par le patient');

                DB::afterCommit(function () use ($patient, $rdv) {
                    $this->envoyerEmail(function () use ($patient, $rdv) {
                        Mail::to($patient->email)->send(new RendezVousAnnule($rdv->fresh()));
                    });
                });

                return $rdv;
            });
        });
    }

    /**
     * Envoie un email métier après le commit, en journalisant un éventuel échec.
     * Le rendez-vous est déjà enregistré : un échec d'envoi ne doit jamais le
     * remettre en cause ni faire croire que l'opération métier a échoué.
     */
    private function envoyerEmail(callable $envoi): void
    {
        try {
            $envoi();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Vérifie qu'un créneau est disponible pour un médecin à une date/heure donnée.
     * Exclut optionnellement un RDV existant (cas modification).
     */
    private function verifierCreneauDisponible(
        int $medecinId,
        CarbonInterface $jour,
        string $heureDebut,
        ?int $excludeRdvId = null
    ): void {
        if (! app(CreneauService::class)->estDansHorizon($jour)) {
            throw new \Exception("Cette date dépasse l'horizon de réservation.");
        }

        $creneauDebut = CreneauService::heureEnMinutes($heureDebut);
        $creneauFin = $creneauDebut + CreneauService::DUREE_CRENEAU_MINUTES;

        $rendezVous = RendezVous::query()
            ->where('medecin_id', $medecinId)
            ->whereDate('date', $jour->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->get();

        foreach ($rendezVous as $rdv) {
            if ($excludeRdvId !== null && $rdv->id === $excludeRdvId) {
                continue;
            }
            $rdvDebut = CreneauService::heureEnMinutes($rdv->heure_debut);
            $rdvFin = $rdvDebut + ($rdv->duree_prevue ?: CreneauService::DUREE_CRENEAU_MINUTES);

            if ($creneauDebut < $rdvFin && $rdvDebut < $creneauFin) {
                throw new \Exception('Ce créneau n\'est plus disponible.');
            }
        }
    }

    /**
     * Vérifie que le patient est bien le propriétaire du rendez-vous.
     */
    private function verifierPropriete(RendezVous $rdv, User $patient): void
    {
        if ($rdv->patient_id !== $patient->id) {
            throw new \Exception('Vous n\'êtes pas autorisé à modifier ce rendez-vous.');
        }
    }

    /**
     * Exécute un callback avec reprise sur deadlock (max 3 tentatives).
     */
    private function executeWithDeadlockRetry(callable $callback)
    {
        $attempts = 0;

        while (true) {
            try {
                return $callback();
            } catch (QueryException $e) {
                if ($this->isDeadlock($e) && $attempts < self::MAX_DEADLOCK_RETRIES) {
                    $attempts++;
                    Log::warning("Deadlock détecté lors de la réservation, tentative $attempts/" . self::MAX_DEADLOCK_RETRIES);
                    usleep(100000 * $attempts);
                    continue;
                }
                throw $e;
            } catch (Throwable $e) {
                throw $e;
            }
        }
    }

    /**
     * Détecte si l'erreur est un deadlock MySQL/MariaDB.
     */
    private function isDeadlock(QueryException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'Deadlock found')
            || str_contains($message, 'deadlock detected')
            || str_contains($message, 'Lock wait timeout exceeded');
    }
}
