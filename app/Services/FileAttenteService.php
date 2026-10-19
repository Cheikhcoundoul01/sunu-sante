<?php

namespace App\Services;

use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service de gestion de la file d'attente.
 * Génère la file du jour, gère les transitions de statut et les passages prioritaires.
 */
class FileAttenteService
{
    /**
     * Génère la file d'attente pour un service à une date donnée.
     * Idempotent : relancer plusieurs fois ne crée pas de doublons.
     * Exclut les RDV annulés et les interventions (avec salle).
     * Protégé contre les accès concurrents au niveau base (verrou sur service).
     *
     * @return int Nombre de nouvelles entrées créées
     */
    public function genererFileDuJour(int $serviceId, ?CarbonInterface $date = null): int
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();

        return $this->executeWithServiceLock($serviceId, function () use ($serviceId, $date) {
            // Vérifier si la file a déjà été générée pour ce service/date
            $dejaGenere = FileEntree::where('service_id', $serviceId)
                ->whereDate('date', $date->toDateString())
                ->exists();

            if ($dejaGenere) {
                return 0;
            }

            // Récupérer les RDV de consultation Validés sans salle pour ce service/date
            $rdvs = RendezVous::query()
                ->where('service_id', $serviceId)
                ->whereDate('date', $date->toDateString())
                ->where('statut', RendezVous::STATUT_VALIDE)
                ->whereNull('salle_id') // Exclure les interventions
                ->orderBy('heure_debut')
                ->get();

            if ($rdvs->isEmpty()) {
                return 0;
            }

            $nouvellesEntrees = 0;

            foreach ($rdvs as $index => $rdv) {
                // Double vérification : pas de file_entree existante pour ce RDV
                $existe = FileEntree::where('rendez_vous_id', $rdv->id)->exists();
                if ($existe) {
                    continue;
                }

                FileEntree::create([
                    'service_id' => $serviceId,
                    'rendez_vous_id' => $rdv->id,
                    'patient_id' => $rdv->patient_id,
                    'date' => $date->toDateString(),
                    'statut' => FileEntree::STATUT_EN_ATTENTE,
                    'position' => $index + 1,
                    'priorite' => false,
                ]);

                $nouvellesEntrees++;
            }

            return $nouvellesEntrees;
        });
    }

    /**
     * Génère les files pour tous les services actifs.
     */
    public function genererToutesFiles(?CarbonInterface $date = null): array
    {
        $services = Service::where('actif', true)->get();
        $resultats = [];

        foreach ($services as $service) {
            $resultats[$service->nom] = $this->genererFileDuJour($service->id, $date);
        }

        return $resultats;
    }

    /**
     * Change le statut d'une entrée de file (agent d'accueil uniquement).
     * Vérifie les transitions autorisées.
     */
    public function changerStatut(int $fileEntreeId, string $nouveauStatut, ?string $motifPriorite = null): FileEntree
    {
        return DB::transaction(function () use ($fileEntreeId, $nouveauStatut, $motifPriorite) {
            $entree = FileEntree::whereKey($fileEntreeId)->lockForUpdate()->firstOrFail();

            if (! $entree->peutTransitionnerVers($nouveauStatut)) {
                throw new \Exception("Transition non autorisée de {$entree->statut} vers $nouveauStatut");
            }

            $ancienStatut = $entree->statut;

            // Gestion du passage prioritaire
            $priorite = $entree->priorite;
            $motif = $entree->motif_priorite;

            if ($nouveauStatut === FileEntree::STATUT_EN_COURS && $entree->priorite) {
                // Le patient prioritaire passe en cours, on remet priorite à false
                $priorite = false;
                $motif = null;
            }

            $entree->update([
                'statut' => $nouveauStatut,
                'priorite' => $priorite,
                'motif_priorite' => $motif,
            ]);

            // Journaliser le changement de statut
            activity()
                ->causedBy(auth()->user())
                ->performedOn($entree)
                ->event('changement_statut_file')
                ->log("Statut file changé : $ancienStatut → $nouveauStatut");

            // Si passage prioritaire, journaliser aussi
            if ($motifPriorite && $nouveauStatut === FileEntree::STATUT_EN_COURS && $entree->priorite) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($entree)
                    ->event('passage_prioritaire')
                    ->log("Passage prioritaire : $motifPriorite");
            }

            // Recalculer les positions après changement
            $this->recalculerPositions($entree->service_id, $entree->date);

            return $entree->fresh();
        });
    }

    /**
     * Fait passer un patient en priorité (agent d'accueil uniquement).
     * Motif obligatoire.
     */
    public function passerPrioritaire(int $fileEntreeId, string $motif): FileEntree
    {
        if (empty(trim($motif))) {
            throw new \Exception('Le motif est obligatoire pour un passage prioritaire');
        }

        return DB::transaction(function () use ($fileEntreeId, $motif) {
            $entree = FileEntree::whereKey($fileEntreeId)->lockForUpdate()->firstOrFail();

            if (! $entree->estEnAttente()) {
                throw new \Exception('Seuls les patients en attente peuvent passer en priorité');
            }

            $entree->update([
                'priorite' => true,
                'motif_priorite' => $motif,
            ]);

            // Journaliser le passage prioritaire
            activity()
                ->causedBy(auth()->user())
                ->performedOn($entree)
                ->event('passage_prioritaire')
                ->log("Passage prioritaire programmé : $motif");

            // Recalculer les positions : les prioritaires passent devant
            $this->recalculerPositions($entree->service_id, $entree->date);

            return $entree->fresh();
        });
    }

    /**
     * Remet un patient absent en attente (fin de file).
     */
    public function remettreEnAttente(int $fileEntreeId): FileEntree
    {
        return DB::transaction(function () use ($fileEntreeId) {
            $entree = FileEntree::whereKey($fileEntreeId)->lockForUpdate()->firstOrFail();

            if (! $entree->estAbsent()) {
                throw new \Exception('Seuls les patients absents peuvent être remis en attente');
            }

            $entree->update([
                'statut' => FileEntree::STATUT_EN_ATTENTE,
                'priorite' => false,
                'motif_priorite' => null,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($entree)
                ->event('changement_statut_file')
                ->log('Patient absent remis en attente (fin de file)');

            $this->recalculerPositions($entree->service_id, $entree->date);

            return $entree->fresh();
        });
    }

    /**
     * Récupère la file du jour pour un service, ordonnée par priorité puis position.
     */
    public function getFileDuJour(int $serviceId, ?CarbonInterface $date = null)
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();

        return FileEntree::query()
            ->where('service_id', $serviceId)
            ->whereDate('date', $date->toDateString())
            ->with(['patient', 'rendezVous.medecin'])
            ->orderBy('priorite', 'desc')
            ->orderBy('position')
            ->get();
    }

    /**
     * Récupère la position d'un patient dans la file du jour.
     */
    public function getPositionPatient(int $patientId, int $serviceId, ?CarbonInterface $date = null): ?int
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();

        $entree = FileEntree::query()
            ->where('patient_id', $patientId)
            ->where('service_id', $serviceId)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', [FileEntree::STATUT_EN_ATTENTE, FileEntree::STATUT_EN_COURS])
            ->first();

        return $entree?->position;
    }

    /**
     * Récupère l'entrée de file d'un patient pour une date donnée, quel que soit le statut.
     * Permet d'afficher au patient sa propre entrée sans dépendre de son service
     * (un compte patient n'est pas rattaché à un service).
     */
    public function getEntreePatient(int $patientId, ?CarbonInterface $date = null): ?FileEntree
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();

        return FileEntree::query()
            ->where('patient_id', $patientId)
            ->whereDate('date', $date->toDateString())
            ->with(['service', 'rendezVous.medecin'])
            ->first();
    }

    /**
     * Recalcule les positions dans la file pour un service/date.
     * Prioritaires d'abord (ordre d'arrivée), puis non-prioritaires (ordre d'arrivée).
     */
    private function recalculerPositions(int $serviceId, CarbonInterface $date): void
    {
        $date = Carbon::parse($date)->startOfDay();

        $entrees = FileEntree::query()
            ->where('service_id', $serviceId)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', [FileEntree::STATUT_EN_ATTENTE, FileEntree::STATUT_EN_COURS])
            ->orderBy('priorite', 'desc')
            ->orderBy('created_at')
            ->get();

        foreach ($entrees as $index => $entree) {
            if ($entree->position !== $index + 1) {
                $entree->update(['position' => $index + 1]);
            }
        }
    }

    /**
     * Exécute un callback avec verrou sur le service pour éviter les doublons en concurrence.
     */
    private function executeWithServiceLock(int $serviceId, callable $callback): int
    {
        $attempts = 0;
        $maxAttempts = 3;

        while (true) {
            try {
                return DB::transaction(function () use ($serviceId, $callback) {
                    // Verrouiller la ligne service pour sérialiser les générations
                    Service::whereKey($serviceId)->lockForUpdate()->firstOrFail();
                    return $callback();
                });
            } catch (QueryException $e) {
                if ($this->isDeadlock($e) && $attempts < $maxAttempts) {
                    $attempts++;
                    Log::warning("Deadlock file d'attente service $serviceId, tentative $attempts/$maxAttempts");
                    usleep(100000 * $attempts);
                    continue;
                }
                throw $e;
            }
        }
    }

    private function isDeadlock(QueryException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'Deadlock found')
            || str_contains($message, 'deadlock detected')
            || str_contains($message, 'Lock wait timeout exceeded');
    }
}