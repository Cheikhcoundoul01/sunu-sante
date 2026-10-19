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
     * Statistiques pour un service donné (Major).
     */
    public function getServiceStatistics(int $serviceId, ?CarbonInterface $date = null): array
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();
        $service = Service::findOrFail($serviceId);

        // RDV du jour pour ce service
        $rdvsJour = RendezVous::query()
            ->where('service_id', $serviceId)
            ->whereDate('date', $date->toDateString())
            ->get();

        $rdvsValides = $rdvsJour->where('statut', 'Validé');
        $rdvsDemandes = $rdvsJour->where('statut', 'Demandé');
        $rdvsAnnules = $rdvsJour->where('statut', 'Annulé');
        $rdvsInterventions = $rdvsValides->whereNotNull('salle_id');
        $rdvsConsultations = $rdvsValides->whereNull('salle_id');

        // File d'attente du jour
        $file = FileEntree::query()
            ->where('service_id', $serviceId)
            ->whereDate('date', $date->toDateString())
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
    public function getGlobalStatistics(?CarbonInterface $date = null): array
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();

        $services = Service::where('actif', true)->get();
        $statsParService = [];

        foreach ($services as $service) {
            $statsParService[$service->nom] = $this->getServiceStatistics($service->id, $date);
        }

        // Totaux globaux
        $totalRdv = RendezVous::whereDate('date', $date->toDateString())->count();
        $totalValides = RendezVous::whereDate('date', $date->toDateString())->where('statut', 'Validé')->count();
        $totalDemandes = RendezVous::whereDate('date', $date->toDateString())->where('statut', 'Demandé')->count();
        $totalAnnules = RendezVous::whereDate('date', $date->toDateString())->where('statut', 'Annulé')->count();
        $totalInterventions = RendezVous::whereDate('date', $date->toDateString())
            ->where('statut', 'Validé')
            ->whereNotNull('salle_id')
            ->count();

        $totalFile = FileEntree::whereDate('date', $date->toDateString())->count();
        $totalEnAttente = FileEntree::whereDate('date', $date->toDateString())->where('statut', 'En attente')->count();
        $totalEnCours = FileEntree::whereDate('date', $date->toDateString())->where('statut', 'En cours')->count();
        $totalTermine = FileEntree::whereDate('date', $date->toDateString())->where('statut', 'Terminé')->count();
        $totalAbsent = FileEntree::whereDate('date', $date->toDateString())->where('statut', 'Absent')->count();

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
}