<?php

namespace App\Livewire\Secretaire;

use App\Models\Disponibilite;
use App\Models\Parametre;
use App\Models\User;
use App\Services\CreneauService;
use Carbon\Carbon;
use Livewire\Component;

class Disponibilites extends Component
{
    public ?int $medecinId = null;

    public ?string $jour = null;

    public ?string $heure_debut = null;

    public ?string $heure_fin = null;

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

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

    public function ajouter(CreneauService $creneaux): void
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

        $disponibilite->update(['active' => ! $disponibilite->active]);

        $this->messageSucces = $disponibilite->active
            ? 'Disponibilité réactivée.'
            : 'Disponibilité désactivée.';
    }

    public function render(CreneauService $creneaux)
    {
        $services = $this->servicesAutorises();

        $medecins = empty($services)
            ? collect()
            : User::role('Médecin')->whereIn('service_id', $services)->orderBy('name')->get();

        $disponibilites = empty($services)
            ? collect()
            : Disponibilite::query()
                ->whereIn('service_id', $services)
                ->when($this->medecinId, fn ($query) => $query->where('medecin_id', $this->medecinId))
                ->orderBy('jour')
                ->orderBy('heure_debut')
                ->get();

        $creneauxParDisponibilite = $disponibilites->mapWithKeys(
            fn (Disponibilite $dispo) => [$dispo->id => $creneaux->creneauxLibres($dispo)]
        );

        return view('livewire.secretaire.disponibilites', [
            'medecins' => $medecins,
            'disponibilites' => $disponibilites,
            'creneauxParDisponibilite' => $creneauxParDisponibilite,
            'horizonJours' => Parametre::horizonReservationJours(),
        ])->layout('layouts.app');
    }
}
