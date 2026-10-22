<?php

namespace App\Services;

use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Service de calcul des statistiques.
 * Major : statistiques de son service uniquement.
 * Administrateur : statistiques globales.
 */
class StatisticsService
{
    /**
     * Périodes acceptées par le sélecteur de période.
     *
     * @var array<int, string>
     */
    public const PERIODES = ['jour', 'semaine', 'mois'];

    /**
     * Statistiques pour un service donné (Major).
     */
    public function getServiceStatistics(int $serviceId, ?CarbonInterface $date = null, string $periode = 'jour'): array
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();
        $periode = $this->normaliserPeriode($periode);
        [$debut, $fin] = $this->bornesPeriode($date, $periode);
        $service = Service::findOrFail($serviceId);

        // RDV de la période pour ce service
        $rdvsJour = RendezVous::query()
            ->where('service_id', $serviceId)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->get();

        $rdvsValides = $rdvsJour->where('statut', 'Validé');
        $rdvsDemandes = $rdvsJour->where('statut', 'Demandé');
        $rdvsAnnules = $rdvsJour->where('statut', 'Annulé');
        $rdvsInterventions = $rdvsValides->whereNotNull('salle_id');
        $rdvsConsultations = $rdvsValides->whereNull('salle_id');

        // File d'attente de la période
        $file = FileEntree::query()
            ->where('service_id', $serviceId)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->get();

        $enAttente = $file->where('statut', 'En attente')->count();
        $enCours = $file->where('statut', 'En cours')->count();
        $termine = $file->where('statut', 'Terminé')->count();
        $absent = $file->where('statut', 'Absent')->count();
        $prioritaires = $file->where('priorite', true)->count();

        // Temps d'attente moyen (estimation basée sur position * 30min)
        $tempsAttenteMoyen = $file->where('statut', 'En attente')
            ->avg(function ($e) {
                return $e->position * 30;
            });

        // Taux d'absentéisme
        $totalFile = $file->count();
        $tauxAbsenteisme = $totalFile > 0 ? round(($absent / $totalFile) * 100, 1) : 0;

        // Personnel du service
        $medecins = User::role('Médecin')->where('service_id', $serviceId)->count();
        $secretaires = User::role('Secrétaire de service')->where('service_id', $serviceId)->count();
        $agents = User::role("Agent d'accueil")->where('service_id', $serviceId)->count();

        return [
            'service' => $service,
            'date' => $date->toDateString(),
            'periode' => [
                'type' => $periode,
                'debut' => $debut->toDateString(),
                'fin' => $fin->toDateString(),
            ],
            'rdv' => [
                'total' => $rdvsJour->count(),
                'valides' => $rdvsValides->count(),
                'demandes' => $rdvsDemandes->count(),
                'annules' => $rdvsAnnules->count(),
                'interventions' => $rdvsInterventions->count(),
                'consultations' => $rdvsConsultations->count(),
            ],
            'file' => [
                'total' => $totalFile,
                'en_attente' => $enAttente,
                'en_cours' => $enCours,
                'termine' => $termine,
                'absent' => $absent,
                'prioritaires' => $prioritaires,
                'temps_attente_moyen_min' => $tempsAttenteMoyen ? round($tempsAttenteMoyen) : 0,
                'taux_absenteisme_pct' => $tauxAbsenteisme,
            ],
            'personnel' => [
                'medecins' => $medecins,
                'secretaires' => $secretaires,
                'agents_accueil' => $agents,
            ],
        ];
    }

    /**
     * Statistiques globales (Administrateur).
     */
    public function getGlobalStatistics(?CarbonInterface $date = null, string $periode = 'jour'): array
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();
        $periode = $this->normaliserPeriode($periode);
        [$debut, $fin] = $this->bornesPeriode($date, $periode);
        $bornes = [$debut->toDateString(), $fin->toDateString()];

        $services = Service::where('actif', true)->get();
        $statsParService = [];

        foreach ($services as $service) {
            $statsParService[$service->nom] = $this->getServiceStatistics($service->id, $date, $periode);
        }

        // Totaux globaux
        $totalRdv = RendezVous::whereBetween('date', $bornes)->count();
        $totalValides = RendezVous::whereBetween('date', $bornes)->where('statut', 'Validé')->count();
        $totalDemandes = RendezVous::whereBetween('date', $bornes)->where('statut', 'Demandé')->count();
        $totalAnnules = RendezVous::whereBetween('date', $bornes)->where('statut', 'Annulé')->count();
        $totalInterventions = RendezVous::whereBetween('date', $bornes)
            ->where('statut', 'Validé')
            ->whereNotNull('salle_id')
            ->count();

        $totalFile = FileEntree::whereBetween('date', $bornes)->count();
        $totalEnAttente = FileEntree::whereBetween('date', $bornes)->where('statut', 'En attente')->count();
        $totalEnCours = FileEntree::whereBetween('date', $bornes)->where('statut', 'En cours')->count();
        $totalTermine = FileEntree::whereBetween('date', $bornes)->where('statut', 'Terminé')->count();
        $totalAbsent = FileEntree::whereBetween('date', $bornes)->where('statut', 'Absent')->count();

        $totalMedecins = User::role('Médecin')->count();
        $totalSecretaires = User::role('Secrétaire de service')->count();
        $totalAgents = User::role("Agent d'accueil")->count();
        $totalPatients = User::role('Patient')->count();

        // Taux de validation des RDV
        $totalRdvTraites = $totalValides + $totalAnnules;
        $tauxValidation = $totalRdvTraites > 0 ? round(($totalValides / $totalRdvTraites) * 100, 1) : 0;

        // Top 3 services par volume
        $topServices = collect($statsParService)
            ->sortByDesc(fn ($s) => $s['rdv']['total'])
            ->take(3)
            ->map(fn ($s, $nom) => ['nom' => $nom, 'rdv' => $s['rdv']['total']])
            ->values();

        return [
            'date' => $date->toDateString(),
            'periode' => [
                'type' => $periode,
                'debut' => $debut->toDateString(),
                'fin' => $fin->toDateString(),
            ],
            'global' => [
                'rdv' => [
                    'total' => $totalRdv,
                    'valides' => $totalValides,
                    'demandes' => $totalDemandes,
                    'annules' => $totalAnnules,
                    'interventions' => $totalInterventions,
                    'taux_validation_pct' => $tauxValidation,
                ],
                'file' => [
                    'total' => $totalFile,
                    'en_attente' => $totalEnAttente,
                    'en_cours' => $totalEnCours,
                    'termine' => $totalTermine,
                    'absent' => $totalAbsent,
                ],
                'personnel' => [
                    'medecins' => $totalMedecins,
                    'secretaires' => $totalSecretaires,
                    'agents_accueil' => $totalAgents,
                ],
                'patients' => [
                    'total_inscrits' => $totalPatients,
                ],
            ],
            'par_service' => $statsParService,
            'top_services' => $topServices,
        ];
    }

    /**
     * Ramène une période non reconnue sur la période 'jour'.
     */
    public function normaliserPeriode(?string $periode): string
    {
        return in_array($periode, self::PERIODES, true) ? $periode : 'jour';
    }

    /**
     * Bornes réelles de la période, la date choisie servant de référence.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function bornesPeriode(CarbonInterface $date, ?string $periode = 'jour'): array
    {
        $date = Carbon::parse($date)->startOfDay();
        $periode = $this->normaliserPeriode($periode);

        return match ($periode) {
            'semaine' => [
                $date->copy()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            'mois' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
            default => [$date->copy(), $date->copy()],
        };
    }
}