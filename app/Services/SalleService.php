<?php

namespace App\Services;

use App\Models\DisponibiliteSalle;
use App\Models\RendezVous;
use App\Models\Salle;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Service de gestion des disponibilités et conflits pour les salles d'intervention.
 */
class SalleService
{
    /**
     * Vérifie si une salle est disponible pour un créneau donné.
     * Exclut optionnellement un RDV existant (cas modification).
     */
    public function estDisponible(
        int $salleId,
        CarbonInterface $date,
        string $heureDebut,
        int $dureeMinutes,
        ?int $excludeRdvId = null
    ): bool {
        $salle = Salle::find($salleId);
        if (! $salle || ! $salle->active) {
            return false;
        }

        $jourSemaine = (int) $date->format('N'); // 1=Lundi ... 7=Dimanche
        $heureDebutMin = $this->heureEnMinutes($heureDebut);
        $heureFinMin = $heureDebutMin + $dureeMinutes;

        if ($heureDebutMin === null || $heureFinMin === null || $heureFinMin <= $heureDebutMin) {
            return false;
        }

        // Vérifier que le créneau est dans une plage de disponibilité de la salle
        $disponibilites = DisponibiliteSalle::query()
            ->where('salle_id', $salleId)
            ->where('jour_semaine', $jourSemaine)
            ->where('active', true)
            ->get();

        if ($disponibilites->isEmpty()) {
            return false;
        }

        $dansPlage = false;
        foreach ($disponibilites as $dispo) {
            $dispoDebut = $this->heureEnMinutes($dispo->heure_debut);
            $dispoFin = $this->heureEnMinutes($dispo->heure_fin);
            if ($dispoDebut !== null && $dispoFin !== null && $heureDebutMin >= $dispoDebut && $heureFinMin <= $dispoFin) {
                $dansPlage = true;
                break;
            }
        }

        if (! $dansPlage) {
            return false;
        }

        // Vérifier les conflits avec les RDV existants (actifs) sur cette salle
        $rdvActifs = RendezVous::query()
            ->where('salle_id', $salleId)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->when($excludeRdvId !== null, fn ($q) => $q->where('id', '!=', $excludeRdvId))
            ->get();

        foreach ($rdvActifs as $rdv) {
            $rdvDebut = $this->heureEnMinutes($rdv->heure_debut);
            $rdvFin = $rdvDebut + ($rdv->duree_prevue ?: 30);

            if ($heureDebutMin < $rdvFin && $rdvDebut < $heureFinMin) {
                return false;
            }
        }

        return true;
    }

    /**
     * Interventions actives de la salle qui ne sont couvertes par aucune des
     * plages horaires proposées.
     *
     * Une intervention est couverte lorsqu'une plage active correspond à son
     * jour de la semaine et contient toute sa durée.
     *
     * @param  array<int, array{jour_semaine: int, heure_debut: string, heure_fin: string}>  $plages
     * @return Collection<int, RendezVous>
     */
    public function interventionsNonCouvertes(Salle $salle, array $plages): Collection
    {
        return RendezVous::query()
            ->where('salle_id', $salle->id)
            ->whereIn('statut', RendezVous::statutsActifs())
            ->get()
            ->filter(function (RendezVous $rdv) use ($plages) {
                $debut = $this->heureEnMinutes($rdv->heure_debut);

                if ($debut === null) {
                    return true;
                }

                $fin = $debut + ($rdv->duree_prevue ?: 30);
                $jour = (int) Carbon::parse($rdv->date)->format('N');

                foreach ($plages as $plage) {
                    if ((int) $plage['jour_semaine'] !== $jour) {
                        continue;
                    }

                    $plageDebut = $this->heureEnMinutes($plage['heure_debut']);
                    $plageFin = $this->heureEnMinutes($plage['heure_fin']);

                    if ($plageDebut !== null && $plageFin !== null && $debut >= $plageDebut && $fin <= $plageFin) {
                        return false;
                    }
                }

                return true;
            });
    }

    /**
     * Liste des créneaux libres pour une salle à une date donnée.
     *
     * @return array<int, array{debut: string, fin: string}>
     */
    public function creneauxLibres(Salle $salle, CarbonInterface $date, int $dureeMinutes = 30): array
    {
        if (! $salle->active) {
            return [];
        }

        $jourSemaine = (int) $date->format('N');
        $disponibilites = DisponibiliteSalle::query()
            ->where('salle_id', $salle->id)
            ->where('jour_semaine', $jourSemaine)
            ->where('active', true)
            ->orderBy('heure_debut')
            ->get();

        if ($disponibilites->isEmpty()) {
            return [];
        }

        // Récupérer les RDV actifs sur cette salle à cette date
        $rdvActifs = RendezVous::query()
            ->where('salle_id', $salle->id)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->get()
            ->map(function ($rdv) {
                $debut = $this->heureEnMinutes($rdv->heure_debut);
                $duree = $rdv->duree_prevue ?: 30;
                return [$debut, $debut + $duree];
            })
            ->all();

        $creneaux = [];

        foreach ($disponibilites as $dispo) {
            $dispoDebut = $this->heureEnMinutes($dispo->heure_debut);
            $dispoFin = $this->heureEnMinutes($dispo->heure_fin);

            if ($dispoDebut === null || $dispoFin === null || $dispoFin <= $dispoDebut) {
                continue;
            }

            for ($start = $dispoDebut; $start + $dureeMinutes <= $dispoFin; $start += $dureeMinutes) {
                $end = $start + $dureeMinutes;

                $conflit = false;
                foreach ($rdvActifs as [$rdvDebut, $rdvFin]) {
                    if ($start < $rdvFin && $rdvDebut < $end) {
                        $conflit = true;
                        break;
                    }
                }

                if (! $conflit) {
                    $creneaux[] = [
                        'debut' => $this->minutesVersHeure($start),
                        'fin' => $this->minutesVersHeure($end),
                    ];
                }
            }
        }

        return $creneaux;
    }

    /**
     * Vérifie les conflits de réservation pour une salle (utilisé côté serveur).
     */
    public function verifierConflit(
        int $salleId,
        CarbonInterface $date,
        string $heureDebut,
        int $dureeMinutes,
        ?int $excludeRdvId = null
    ): void {
        if (! $this->estDisponible($salleId, $date, $heureDebut, $dureeMinutes, $excludeRdvId)) {
            throw new \Exception('Ce créneau n\'est plus disponible pour cette salle.');
        }
    }

    private function heureEnMinutes(string $heure): ?int
    {
        $parties = explode(':', $heure);
        if (count($parties) < 2) {
            return null;
        }
        return ((int) $parties[0]) * 60 + (int) ($parties[1] ?? 0);
    }

    private function minutesVersHeure(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}