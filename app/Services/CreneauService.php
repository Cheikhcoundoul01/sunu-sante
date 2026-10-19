<?php

namespace App\Services;

use App\Models\Disponibilite;
use App\Models\Parametre;
use App\Models\RendezVous;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Calcul des créneaux libres d'un médecin à partir de ses disponibilités,
 * en excluant les rendez-vous actifs (Demandé ou Validé) et en respectant
 * l'horizon de réservation défini par l'administrateur (étape 6).
 */
class CreneauService
{
    /**
     * Durée d'un créneau, en minutes.
     */
    public const DUREE_CRENEAU_MINUTES = 30;

    /**
     * Dernière date réservable (aujourd'hui + horizon), à minuit.
     */
    public function dateLimiteReservation(?CarbonInterface $reference = null): Carbon
    {
        $ref = $reference ? Carbon::parse($reference) : Carbon::now();

        return $ref->copy()->startOfDay()->addDays(Parametre::horizonReservationJours());
    }

    /**
     * Indique si une date (jour) est réservable au regard de l'horizon.
     */
    public function estDansHorizon(CarbonInterface $jour, ?CarbonInterface $reference = null): bool
    {
        $ref = $reference ? Carbon::parse($reference) : Carbon::now();
        $debut = $ref->copy()->startOfDay();

        return $jour->copy()->startOfDay()->betweenIncluded($debut, $this->dateLimiteReservation($ref));
    }

    /**
     * Liste des créneaux libres d'une disponibilité.
     *
     * @return array<int, array{debut: string, fin: string}>
     */
    public function creneauxLibres(Disponibilite $dispo, ?CarbonInterface $reference = null): array
    {
        if (! $dispo->active) {
            return [];
        }

        $ref = $reference ? Carbon::parse($reference) : Carbon::now();
        $debutJour = $ref->copy()->startOfDay();
        $limite = $this->dateLimiteReservation($ref);
        $jour = $dispo->jour->copy()->startOfDay();

        // Hors horizon ou dans le passé : aucun créneau.
        if ($jour->lt($debutJour) || $jour->gt($limite)) {
            return [];
        }

        $debutMinutes = $dispo->heureDebutMinutes();
        $finMinutes = $dispo->heureFinMinutes();

        if ($debutMinutes === null || $finMinutes === null || $finMinutes <= $debutMinutes) {
            return [];
        }

        $intervallesOccupes = $this->intervallesOccupes($dispo->medecin_id, $dispo->jour);

        $creneaux = [];
        $minutesAujourdhui = $ref->hour * 60 + $ref->minute;

        for ($start = $debutMinutes; $start + self::DUREE_CRENEAU_MINUTES <= $finMinutes; $start += self::DUREE_CRENEAU_MINUTES) {
            $end = $start + self::DUREE_CRENEAU_MINUTES;

            // Créneau déjà passé (aujourd'hui).
            if ($jour->equalTo($debutJour) && $end <= $minutesAujourdhui) {
                continue;
            }

            if ($this->chevauche($start, $end, $intervallesOccupes)) {
                continue;
            }

            $creneaux[] = [
                'debut' => $this->minutesVersHeure($start),
                'fin' => $this->minutesVersHeure($end),
            ];
        }

        return $creneaux;
    }

    /**
     * Créneaux libres par disponibilité pour un médecin et un jour donné.
     *
     * @return array<int, array{disponibilite_id: int, creneaux: array<int, array{debut: string, fin: string}>}>
     */
    public function creneauxLibresDuJour(int $medecinId, CarbonInterface $jour, ?CarbonInterface $reference = null): array
    {
        $disponibilites = Disponibilite::query()
            ->where('medecin_id', $medecinId)
            ->whereDate('jour', $jour->toDateString())
            ->orderBy('heure_debut')
            ->get();

        $resultat = [];

        foreach ($disponibilites as $dispo) {
            $creneaux = $this->creneauxLibres($dispo, $reference);

            if ($creneaux !== []) {
                $resultat[] = [
                    'disponibilite_id' => $dispo->id,
                    'creneaux' => $creneaux,
                ];
            }
        }

        return $resultat;
    }

    /**
     * Intervalles occupés par les rendez-vous actifs d'un médecin sur une date.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    private function intervallesOccupes(int $medecinId, CarbonInterface $jour): array
    {
        $rendezVous = RendezVous::query()
            ->where('medecin_id', $medecinId)
            ->whereDate('date', $jour->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->get();

        return $rendezVous
            ->map(function (RendezVous $rdv) {
                $debut = self::heureEnMinutes($rdv->heure_debut);
                $duree = $rdv->duree_prevue ?: self::DUREE_CRENEAU_MINUTES;

                return [$debut, $debut + $duree];
            })
            ->all();
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervalles
     */
    private function chevauche(int $debut, int $fin, array $intervalles): bool
    {
        foreach ($intervalles as [$occupeDebut, $occupeFin]) {
            if ($debut < $occupeFin && $occupeDebut < $fin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convertit une heure (HH:MM) en minutes depuis minuit.
     */
    public static function heureEnMinutes(string $heure): int
    {
        $parties = explode(':', $heure);

        return ((int) $parties[0]) * 60 + (int) ($parties[1] ?? 0);
    }

    private function minutesVersHeure(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
