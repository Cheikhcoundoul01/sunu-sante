<?php

namespace App\Livewire\Secretaire;

use App\Models\Disponibilite;
use App\Models\Parametre;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\CreneauService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Disponibilites extends Component
{
    use WithPagination;

    public ?int $medecinId = null;

    public ?string $jour = null;

    public ?string $heure_debut = null;

    public ?string $heure_fin = null;

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

    /**
     * Disponibilité en cours de modification.
     */
    public ?int $dispoId = null;

    /**
     * Onglet actif : principal ou historique.
     */
    public string $onglet = 'principal';

    /**
     * @var array<string, string>
     */
    protected $rules = [
        'medecinId' => 'required|integer',
        'jour' => 'required|date',
        'heure_debut' => 'required|date_format:H:i',
        'heure_fin' => 'required|date_format:H:i|after:heure_debut',
    ];

    /**
     * @var array<string, string>
     */
    protected $messages = [
        'medecinId.required' => 'Veuillez sélectionner un médecin.',
        'jour.required' => 'Veuillez choisir une date.',
        'jour.date' => 'La date est invalide.',
        'heure_debut.required' => "Veuillez indiquer l'heure de début.",
        'heure_debut.date_format' => "L'heure de début est invalide.",
        'heure_fin.required' => "Veuillez indiquer l'heure de fin.",
        'heure_fin.date_format' => "L'heure de fin est invalide.",
        'heure_fin.after' => "L'heure de fin doit être postérieure à l'heure de début.",
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('disponibilites.gerer'), 403);

        $this->jour = Carbon::now()->toDateString();
    }

    public function updatingOnglet(): void
    {
        $this->resetPage();
    }

    /**
     * Services autorisés pour la secrétaire (service principal + services
     * supplémentaires).
     *
     * @return array<int, int>
     */
    protected function servicesAutorises(): array
    {
        return auth()->user()->servicesAutorisesIds();
    }

    /**
     * Crée un ou plusieurs créneaux de 30 minutes pour couvrir la plage.
     * Retourne true si au moins un RDV actif chevauche la plage.
     *
     * @param  array{debut: string, fin: string}  $plage
     */
    private function plagePriseParRdvActif(User $medecin, Carbon $date, array $plage): bool
    {
        $debutPlage = CreneauService::heureEnMinutes($plage['debut']);
        $finPlage = CreneauService::heureEnMinutes($plage['fin']);

        if ($debutPlage === null || $finPlage === null || $finPlage <= $debutPlage) {
            return false;
        }

        $rdvs = RendezVous::query()
            ->where('medecin_id', $medecin->id)
            ->whereDate('date', $date->toDateString())
            ->whereIn('statut', RendezVous::statutsActifs())
            ->get();

        foreach ($rdvs as $rdv) {
            $rdvDebut = CreneauService::heureEnMinutes($rdv->heure_debut);
            if ($rdvDebut === null) {
                continue;
            }

            $duree = $rdv->duree_prevue ?: CreneauService::DUREE_CRENEAU_MINUTES;
            $rdvFin = $rdvDebut + $duree;

            if ($rdvDebut < $finPlage && $debutPlage < $rdvFin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie s'il existe des RDV actifs chevauchant la plage.
     */
    private function rdvActifChevauche(User $medecin, Carbon $date, string $heureDebut, string $heureFin): bool
    {
        return $this->plagePriseParRdvActif($medecin, $date, [
            'debut' => $heureDebut,
            'fin' => $heureFin,
        ]);
    }

    private function heureEnMinutes(?string $heure): ?int
    {
        if ($heure === null || $heure === '') {
            return null;
        }

        $parties = explode(':', $heure);

        if (count($parties) < 2) {
            return null;
        }

        return ((int) $parties[0]) * 60 + (int) ($parties[1] ?? 0);
    }

    public function modifier(int $id): void
    {
        abort_unless(auth()->user()->can('disponibilites.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $disponibilite = Disponibilite::whereKey($id)
            ->whereIn('service_id', $this->servicesAutorises())
            ->first();

        if ($disponibilite === null) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';

            return;
        }

        $jour = $disponibilite->jour instanceof Carbon
            ? $disponibilite->jour->copy()->startOfDay()
            : Carbon::parse($disponibilite->jour)->startOfDay();

        if ($this->estPassee($jour, $disponibilite->heure_fin)) {
            $this->messageErreur = 'Impossible de modifier une disponibilité passée.';

            return;
        }

        $medecin = User::whereKey($disponibilite->medecin_id)->first();

        if ($medecin && $this->rdvActifChevauche($medecin, $jour, (string) $disponibilite->heure_debut, (string) $disponibilite->heure_fin)) {
            $this->messageErreur = 'Ce créneau est déjà pris par un patient, il ne peut pas être modifié.';

            return;
        }

        $this->dispoId = $disponibilite->id;
        $this->medecinId = $disponibilite->medecin_id;
        $this->jour = $jour->toDateString();
        $this->heure_debut = is_string($disponibilite->heure_debut) ? substr($disponibilite->heure_debut, 0, 5) : null;
        $this->heure_fin = is_string($disponibilite->heure_fin) ? substr($disponibilite->heure_fin, 0, 5) : null;

        $this->resetValidation();
    }

    public function annulerModification(): void
    {
        $this->dispoId = null;
        $this->heure_debut = null;
        $this->heure_fin = null;
        $this->resetValidation();
    }

    private function estPassee(Carbon $jour, ?string $heureFin): bool
    {
        $maintenant = Carbon::now(config('app.timezone'));
        $fin = $this->heureEnMinutes($heureFin);

        if ($fin === null) {
            return $jour->copy()->endOfDay()->lt($maintenant);
        }

        $finDuJour = $jour->copy()->setTime(intdiv($fin, 60), $fin % 60, 0);

        return $finDuJour->lt($maintenant);
    }

    public function ajouter(CreneauService $creneaux): void
    {
        if ($this->dispoId !== null) {
            $this->enregistrerModification($creneaux);

            return;
        }

        abort_unless(auth()->user()->can('disponibilites.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $this->validate();

        $services = $this->servicesAutorises();

        if (empty($services)) {
            $this->messageErreur = "Aucun service n'est rattaché à votre compte.";

            return;
        }

        // Périmètre : le médecin doit appartenir à un service autorisé.
        $medecin = User::role('Médecin')
            ->whereKey($this->medecinId)
            ->whereIn('service_id', $services)
            ->first();

        if ($medecin === null) {
            $this->messageErreur = 'Médecin introuvable dans votre service.';

            return;
        }

        $jour = Carbon::parse($this->jour)->startOfDay();

        // Horizon de réservation défini par l'administrateur.
        if ($jour->lt(Carbon::now()->startOfDay())) {
            $this->messageErreur = 'La date choisie est déjà passée.';

            return;
        }

        if (! $creneaux->estDansHorizon($jour)) {
            $this->messageErreur = "Cette date dépasse l'horizon de réservation ("
                .Parametre::horizonReservationJours().' jours).';

            return;
        }

        $chevauchement = Disponibilite::query()
            ->where('medecin_id', $medecin->id)
            ->whereDate('jour', $jour->toDateString())
            ->where('active', true)
            ->where('heure_debut', '<', $this->heure_fin)
            ->where('heure_fin', '>', $this->heure_debut)
            ->exists();

        if ($chevauchement) {
            $this->messageErreur = 'Une disponibilité existe déjà sur cette plage horaire.';

            return;
        }

        Disponibilite::create([
            'medecin_id' => $medecin->id,
            'service_id' => $medecin->service_id,
            'jour' => $jour->toDateString(),
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
            'active' => true,
        ]);

        $this->medecinId = $medecin->id;
        $this->messageSucces = 'Disponibilité ajoutée.';
        $this->reset(['heure_debut', 'heure_fin']);
        $this->resetValidation();
    }

    public function enregistrerModification(CreneauService $creneaux): void
    {
        abort_unless(auth()->user()->can('disponibilites.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $this->validate();

        $services = $this->servicesAutorises();

        if (empty($services)) {
            $this->messageErreur = "Aucun service n'est rattaché à votre compte.";

            return;
        }

        $disponibilite = Disponibilite::whereKey($this->dispoId)
            ->whereIn('service_id', $services)
            ->first();

        if ($disponibilite === null) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';

            return;
        }

        $jour = Carbon::parse($this->jour)->startOfDay();

        if ($jour->lt(Carbon::now()->startOfDay())) {
            $this->messageErreur = 'La date choisie est déjà passée.';

            return;
        }

        if (! $creneaux->estDansHorizon($jour)) {
            $this->messageErreur = "Cette date dépasse l'horizon de réservation ("
                .Parametre::horizonReservationJours().' jours).';

            return;
        }

        try {
            DB::transaction(function () use ($disponibilite, $jour) {
                $medecin = User::role('Médecin')
                    ->whereKey($disponibilite->medecin_id)
                    ->lockForUpdate()
                    ->first();

                if ($medecin === null) {
                    throw new \Exception('Médecin introuvable.');
                }

                $dispoVerrou = Disponibilite::whereKey($disponibilite->id)
                    ->lockForUpdate()
                    ->first();

                if ($dispoVerrou === null) {
                    throw new \Exception('Disponibilité introuvable dans votre service.');
                }

                if ($this->estPassee($jour, $this->heure_fin)) {
                    throw new \Exception('Impossible de modifier une disponibilité passée.');
                }

                if ($this->rdvActifChevauche($medecin, $jour, (string) $this->heure_debut, (string) $this->heure_fin)) {
                    throw new \Exception('Ce créneau est déjà pris par un patient, il ne peut pas être modifié.');
                }

                $chevauchement = Disponibilite::query()
                    ->where('medecin_id', $medecin->id)
                    ->whereDate('jour', $jour->toDateString())
                    ->where('active', true)
                    ->where('id', '!=', $dispoVerrou->id)
                    ->where('heure_debut', '<', $this->heure_fin)
                    ->where('heure_fin', '>', $this->heure_debut)
                    ->exists();

                if ($chevauchement) {
                    throw new \Exception('Une disponibilité existe déjà sur cette plage horaire.');
                }

                $dispoVerrou->update([
                    'jour' => $jour->toDateString(),
                    'heure_debut' => $this->heure_debut,
                    'heure_fin' => $this->heure_fin,
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($dispoVerrou)
                    ->event('modification_disponibilite')
                    ->log('Disponibilité modifiée par la secrétaire');
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->messageErreur = $this->messageErreurAffiche(
                $e,
                'Une erreur est survenue lors de la modification de la disponibilité. Veuillez réessayer.'
            );

            return;
        }

        $this->messageSucces = 'Disponibilité modifiée.';
        $this->dispoId = null;
        $this->heure_debut = null;
        $this->heure_fin = null;
        $this->resetValidation();
    }

    public function basculer(int $id): void
    {
        abort_unless(auth()->user()->can('disponibilites.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $disponibilite = Disponibilite::whereKey($id)
            ->whereIn('service_id', $this->servicesAutorises())
            ->first();

        if ($disponibilite === null) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';

            return;
        }

        try {
            DB::transaction(function () use ($disponibilite, &$dispoVerrouResult) {
                $medecin = User::role('Médecin')
                    ->whereKey($disponibilite->medecin_id)
                    ->lockForUpdate()
                    ->first();

                if ($medecin === null) {
                    throw new \Exception('Médecin introuvable.');
                }

                $dispoVerrou = Disponibilite::whereKey($disponibilite->id)
                    ->lockForUpdate()
                    ->first();

                if ($dispoVerrou === null) {
                    throw new \Exception('Disponibilité introuvable dans votre service.');
                }

                $jour = $dispoVerrou->jour instanceof Carbon
                    ? $dispoVerrou->jour->copy()->startOfDay()
                    : Carbon::parse($dispoVerrou->jour)->startOfDay();

                if ($dispoVerrou->active) {
                    if ($this->rdvActifChevauche($medecin, $jour, (string) $dispoVerrou->heure_debut, (string) $dispoVerrou->heure_fin)) {
                        throw new \Exception("Des rendez-vous sont liés à ce créneau. Annulez-les d'abord.");
                    }

                    $dispoVerrou->update(['active' => false]);

                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($dispoVerrou)
                        ->event('desactivation_disponibilite')
                        ->log('Disponibilité désactivée par la secrétaire');

                    $dispoVerrouResult = $dispoVerrou->fresh();

                    return;
                }

                $chevauchement = Disponibilite::query()
                    ->where('medecin_id', $medecin->id)
                    ->whereDate('jour', $jour->toDateString())
                    ->where('active', true)
                    ->where('id', '!=', $dispoVerrou->id)
                    ->where('heure_debut', '<', $dispoVerrou->heure_fin)
                    ->where('heure_fin', '>', $dispoVerrou->heure_debut)
                    ->exists();

                if ($chevauchement) {
                    throw new \Exception('Une disponibilité existe déjà sur cette plage horaire.');
                }

                $dispoVerrou->update(['active' => true]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($dispoVerrou)
                    ->event('reactivation_disponibilite')
                    ->log('Disponibilité réactivée par la secrétaire');

                $dispoVerrouResult = $dispoVerrou->fresh();
            }, 3);
        } catch (QueryException $e) {
            report($e);
            $this->messageErreur = 'Une erreur inattendue est survenue.';

            return;
        } catch (\Throwable $e) {
            $this->messageErreur = $this->messageErreurAffiche(
                $e,
                'Une erreur est survenue lors du changement de statut de la disponibilité. Veuillez réessayer.'
            );

            return;
        }

        $dispoActualisee = Disponibilite::find($id);
        $this->messageSucces = $dispoActualisee && $dispoActualisee->active
            ? 'Disponibilité réactivée.'
            : 'Disponibilité désactivée.';
    }

    /**
     * Messages métier connus, seuls autorisés à l'affichage.
     *
     * @return array<int, string>
     */
    private function messagesMetier(): array
    {
        return [
            'Médecin introuvable.',
            'Disponibilité introuvable dans votre service.',
            'Impossible de modifier une disponibilité passée.',
            'Ce créneau est déjà pris par un patient, il ne peut pas être modifié.',
            'Une disponibilité existe déjà sur cette plage horaire.',
            "Des rendez-vous sont liés à ce créneau. Annulez-les d'abord.",
        ];
    }

    private function messageErreurAffiche(\Throwable $e, string $messageParDefaut): string
    {
        $message = $e->getMessage();

        if (in_array($message, $this->messagesMetier(), true)) {
            return $message;
        }

        report($e);

        return $messageParDefaut;
    }

    public function render(CreneauService $creneaux)
    {
        $services = $this->servicesAutorises();
        $maintenant = Carbon::now(config('app.timezone'));
        $aujourdhui = $maintenant->copy()->startOfDay();

        $medecins = empty($services)
            ? collect()
            : User::role('Médecin')->whereIn('service_id', $services)->orderBy('name')->get();

        $disponibilitesQuery = empty($services)
            ? Disponibilite::query()->whereRaw('1 = 0')
            : Disponibilite::query()
                ->whereIn('service_id', $services)
                ->when($this->medecinId, fn ($query) => $query->where('medecin_id', $this->medecinId));

        if ($this->onglet === 'historique') {
            $disponibilites = $disponibilitesQuery
                ->where(function ($query) use ($aujourdhui, $maintenant) {
                    $query->whereDate('jour', '<', $aujourdhui->toDateString())
                        ->orWhere(function ($q) use ($aujourdhui, $maintenant) {
                            $q->whereDate('jour', '=', $aujourdhui->toDateString())
                                ->whereTime('heure_fin', '<=', $maintenant->format('H:i:s'));
                        });
                })
                ->orderBy('jour', 'desc')
                ->orderBy('heure_debut', 'desc')
                ->paginate(15);
        } else {
            $disponibilites = $disponibilitesQuery
                ->where(function ($query) use ($aujourdhui, $maintenant) {
                    $query->whereDate('jour', '>', $aujourdhui->toDateString())
                        ->orWhere(function ($q) use ($aujourdhui, $maintenant) {
                            $q->whereDate('jour', '=', $aujourdhui->toDateString())
                                ->whereTime('heure_fin', '>', $maintenant->format('H:i:s'));
                        });
                })
                ->orderBy('jour')
                ->orderBy('heure_debut')
                ->get();
        }

        $disponibilitesPourCreneaux = $this->onglet === 'historique'
            ? $disponibilites->getCollection()
            : $disponibilites;

        $creneauxParDisponibilite = $disponibilitesPourCreneaux->mapWithKeys(
            fn (Disponibilite $dispo) => [$dispo->id => $creneaux->creneauxLibres($dispo)]
        );

        return view('livewire.secretaire.disponibilites', [
            'medecins' => $medecins,
            'disponibilites' => $disponibilites,
            'creneauxParDisponibilite' => $creneauxParDisponibilite,
            'horizonJours' => Parametre::horizonReservationJours(),
            'onglet' => $this->onglet,
            'estHistorique' => $this->onglet === 'historique',
        ])->layout('layouts.app');
    }
}
