<?php

namespace App\Livewire\Secretaire;

use App\Models\DisponibiliteSalle;
use App\Models\Salle;
use App\Models\Service;
use App\Services\SalleService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Salles extends Component
{
    use WithPagination;

    public ?int $salleId = null;
    public string $nom = '';
    public string $description = '';
    public bool $active = true;

    public ?int $dispoSalleId = null;
    public ?int $dispoJourSemaine = null;
    public ?string $dispoHeureDebut = null;
    public ?string $dispoHeureFin = null;
    public bool $dispoActive = true;

    public ?string $messageSucces = null;
    public ?string $messageErreur = null;

    protected function rules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'active' => 'boolean',
        ];
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);
    }

    public function ajouterSalle(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->validate();

        $serviceId = auth()->user()->service_id;

        if ($serviceId === null) {
            $this->messageErreur = "Aucun service n'est rattaché à votre compte.";
            return;
        }

        $existe = Salle::where('service_id', $serviceId)
            ->where('nom', $this->nom)
            ->exists();

        if ($existe) {
            $this->messageErreur = 'Une salle avec ce nom existe déjà dans votre service.';
            return;
        }

        Salle::create([
            'service_id' => $serviceId,
            'nom' => $this->nom,
            'description' => $this->description,
            'active' => $this->active,
        ]);

        $this->messageSucces = 'Salle ajoutée avec succès.';
        $this->reset(['nom', 'description', 'active']);
        $this->active = true;
        $this->resetValidation();
    }

    public function basculerSalle(int $id): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $serviceId = auth()->user()->service_id;
        $salle = Salle::whereKey($id)->where('service_id', $serviceId)->first();

        if (! $salle) {
            $this->messageErreur = 'Salle introuvable dans votre service.';
            return;
        }

        $salle->update(['active' => ! $salle->active]);

        $this->messageSucces = $salle->active ? 'Salle réactivée.' : 'Salle désactivée.';
    }

    public function ajouterDisponibilite(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        try {
            $this->validate([
                'dispoSalleId' => 'required|integer|exists:salles,id',
                'dispoJourSemaine' => 'required|integer|between:1,7',
                'dispoHeureDebut' => 'required|date_format:H:i',
                'dispoHeureFin' => 'required|date_format:H:i|after:dispoHeureDebut',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->messageErreur = $e->validator->errors()->first();
            return;
        }

        $serviceId = auth()->user()->service_id;
        $salle = Salle::whereKey($this->dispoSalleId)->where('service_id', $serviceId)->first();

        if (! $salle) {
            $this->messageErreur = 'Salle introuvable dans votre service.';
            return;
        }

        // Vérifier chevauchement avec disponibilités existantes
        $chevauchement = DisponibiliteSalle::query()
            ->where('salle_id', $salle->id)
            ->where('jour_semaine', $this->dispoJourSemaine)
            ->where('active', true)
            ->where('heure_debut', '<', $this->dispoHeureFin)
            ->where('heure_fin', '>', $this->dispoHeureDebut)
            ->exists();

        if ($chevauchement) {
            $this->messageErreur = 'Une disponibilité existe déjà sur cette plage horaire pour ce jour.';
            return;
        }

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $serviceId,
            'jour_semaine' => $this->dispoJourSemaine,
            'heure_debut' => $this->dispoHeureDebut,
            'heure_fin' => $this->dispoHeureFin,
            'active' => $this->dispoActive,
        ]);

        $this->messageSucces = 'Disponibilité ajoutée.';
        $this->reset(['dispoSalleId', 'dispoJourSemaine', 'dispoHeureDebut', 'dispoHeureFin', 'dispoActive']);
        $this->dispoActive = true;
        $this->resetValidation();
    }

    public function basculerDisponibilite(int $id): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $serviceId = auth()->user()->service_id;
        $dispo = DisponibiliteSalle::whereKey($id)
            ->whereHas('salle', fn ($q) => $q->where('service_id', $serviceId))
            ->first();

        if (! $dispo) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';
            return;
        }

        $dispo->update(['active' => ! $dispo->active]);

        $this->messageSucces = $dispo->active ? 'Disponibilité réactivée.' : 'Disponibilité désactivée.';
    }

    public function render(SalleService $salleService)
    {
        $serviceId = auth()->user()->service_id;

        $salles = $serviceId === null
            ? collect()
            : Salle::query()
                ->where('service_id', $serviceId)
                ->with(['disponibilites'])
                ->orderBy('nom')
                ->get();

        $disponibilites = $serviceId === null
            ? collect()
            : DisponibiliteSalle::query()
                ->whereHas('salle', fn ($q) => $q->where('service_id', $serviceId))
                ->with('salle')
                ->orderBy('jour_semaine')
                ->orderBy('heure_debut')
                ->get();

        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return view('livewire.secretaire.salles', [
            'salles' => $salles,
            'disponibilites' => $disponibilites,
            'jours' => $jours,
        ])->layout('layouts.app');
    }
}