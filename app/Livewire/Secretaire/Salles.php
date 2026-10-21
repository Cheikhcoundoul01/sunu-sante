<?php

namespace App\Livewire\Secretaire;

use App\Models\DisponibiliteSalle;
use App\Models\Salle;
use App\Services\SalleService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Salles extends Component
{
    use WithPagination;

    /**
     * Salle en cours de modification (null : le formulaire crée une salle).
     */
    public ?int $salleId = null;

    public string $nom = '';

    public string $description = '';

    public bool $active = true;

    /**
     * Disponibilité en cours de modification (null : le formulaire en crée une).
     */
    public ?int $dispoId = null;

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

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la salle est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'dispoJourSemaine.required' => 'Le jour de la semaine est obligatoire.',
            'dispoJourSemaine.between' => 'Le jour doit être compris entre le lundi et le dimanche.',
            'dispoHeureDebut.required' => "L'heure de début est obligatoire.",
            'dispoHeureDebut.date_format' => "L'heure de début est invalide.",
            'dispoHeureFin.required' => "L'heure de fin est obligatoire.",
            'dispoHeureFin.date_format' => "L'heure de fin est invalide.",
            'dispoHeureFin.after' => "L'heure de fin doit être après l'heure de début.",
        ];
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);
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
     * Point d'entrée du formulaire salle : la création et la modification
     * partagent le même formulaire.
     */
    public function enregistrerSalle(): void
    {
        if ($this->salleId !== null) {
            $this->enregistrerModificationSalle();

            return;
        }

        $this->ajouterSalle();
    }

    public function ajouterSalle(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $this->validate();

        $serviceId = auth()->user()->service_id;

        if ($serviceId === null) {
            $this->messageErreur = "Aucun service n'est rattaché à votre compte.";

            return;
        }

        // Le nom est unique à l'intérieur d'un service : le même nom reste
        // possible dans un autre service.
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

    /**
     * Préremplit le formulaire avec les informations de la salle.
     */
    public function modifierSalle(int $id): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $salle = Salle::whereKey($id)->whereIn('service_id', $this->servicesAutorises())->first();

        if (! $salle) {
            $this->messageErreur = 'Salle introuvable dans votre service.';

            return;
        }

        $this->salleId = $salle->id;
        $this->nom = $salle->nom;
        $this->description = $salle->description ?? '';

        $this->resetValidation();
    }

    /**
     * Enregistre les modifications de la salle : le nom et la description
     * uniquement. Le service et l'activation ne changent pas.
     */
    public function enregistrerModificationSalle(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $this->validate();

        $salle = Salle::whereKey($this->salleId)->whereIn('service_id', $this->servicesAutorises())->first();

        if (! $salle) {
            $this->messageErreur = 'Salle introuvable dans votre service.';

            return;
        }

        // Le nom reste unique à l'intérieur du service de la salle.
        $doublon = Salle::where('service_id', $salle->service_id)
            ->where('nom', $this->nom)
            ->where('id', '!=', $salle->id)
            ->exists();

        if ($doublon) {
            $this->messageErreur = 'Une salle avec ce nom existe déjà dans votre service.';

            return;
        }

        try {
            $salle->update([
                'nom' => $this->nom,
                'description' => $this->description,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->messageErreur = 'Une salle avec ce nom existe déjà dans ce service.';

                return;
            }

            report($e);

            $this->messageErreur = "Une erreur inattendue est survenue lors de la modification de la salle.";

            return;
        }

        $this->messageSucces = 'Salle mise à jour.';

        $this->annulerModificationSalle();
    }

    /**
     * Revient au formulaire de création.
     */
    public function annulerModificationSalle(): void
    {
        $this->salleId = null;
        $this->nom = '';
        $this->description = '';
        $this->active = true;
        $this->resetValidation();
    }

    public function basculerSalle(int $id): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $salle = Salle::whereKey($id)->whereIn('service_id', $this->servicesAutorises())->first();

        if (! $salle) {
            $this->messageErreur = 'Salle introuvable dans votre service.';

            return;
        }

        $salle->update(['active' => ! $salle->active]);

        $this->messageSucces = $salle->active ? 'Salle réactivée.' : 'Salle désactivée.';
    }

    /**
     * Point d'entrée du formulaire disponibilité : la création et la
     * modification partagent le même formulaire.
     */
    public function enregistrerDisponibilite(): void
    {
        if ($this->dispoId !== null) {
            $this->enregistrerModificationDisponibilite();

            return;
        }

        $this->ajouterDisponibilite();
    }

    public function ajouterDisponibilite(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $this->validate([
                'dispoSalleId' => 'required|integer|exists:salles,id',
                'dispoJourSemaine' => 'required|integer|between:1,7',
                'dispoHeureDebut' => 'required|date_format:H:i',
                'dispoHeureFin' => 'required|date_format:H:i|after:dispoHeureDebut',
            ]);
        } catch (ValidationException $e) {
            $this->messageErreur = $e->validator->errors()->first();

            return;
        }

        $salle = Salle::whereKey($this->dispoSalleId)->whereIn('service_id', $this->servicesAutorises())->first();

        if (! $salle) {
            $this->messageErreur = 'Salle introuvable dans votre service.';

            return;
        }

        // Vérifier chevauchement avec disponibilités existantes
        $chevauchement = $this->chevauchementDisponibilite(
            $salle,
            (int) $this->dispoJourSemaine,
            $this->dispoHeureDebut,
            $this->dispoHeureFin
        );

        if ($chevauchement) {
            $this->messageErreur = 'Une disponibilité existe déjà sur cette plage horaire pour ce jour.';

            return;
        }

        DisponibiliteSalle::create([
            'salle_id' => $salle->id,
            'service_id' => $salle->service_id,
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

    /**
     * Préremplit le formulaire avec les informations de la disponibilité.
     */
    public function modifierDisponibilite(int $id): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $dispo = DisponibiliteSalle::whereKey($id)
            ->whereHas('salle', fn ($q) => $q->whereIn('service_id', $this->servicesAutorises()))
            ->first();

        if (! $dispo) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';

            return;
        }

        $this->dispoId = $dispo->id;
        $this->dispoSalleId = $dispo->salle_id;
        $this->dispoJourSemaine = $dispo->jour_semaine;
        $this->dispoHeureDebut = Str::substr((string) $dispo->heure_debut, 0, 5);
        $this->dispoHeureFin = Str::substr((string) $dispo->heure_fin, 0, 5);
        $this->dispoActive = $dispo->active;

        $this->resetValidation();
    }

    /**
     * Enregistre les modifications d'une disponibilité : jour, heure de début
     * et heure de fin. La salle et l'activation ne changent pas.
     */
    public function enregistrerModificationDisponibilite(): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        try {
            $this->validate([
                'dispoJourSemaine' => 'required|integer|between:1,7',
                'dispoHeureDebut' => 'required|date_format:H:i',
                'dispoHeureFin' => 'required|date_format:H:i|after:dispoHeureDebut',
            ]);
        } catch (ValidationException $e) {
            $this->messageErreur = $e->validator->errors()->first();

            return;
        }

        $dispo = DisponibiliteSalle::whereKey($this->dispoId)
            ->whereHas('salle', fn ($q) => $q->whereIn('service_id', $this->servicesAutorises()))
            ->first();

        if (! $dispo) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';

            return;
        }

        $salle = $dispo->salle;

        // Pas de chevauchement avec les autres disponibilités de la même salle
        // et du même jour (la disponibilité modifiée est exclue).
        $chevauchement = $this->chevauchementDisponibilite(
            $salle,
            (int) $this->dispoJourSemaine,
            $this->dispoHeureDebut,
            $this->dispoHeureFin,
            $dispo->id
        );

        if ($chevauchement) {
            $this->messageErreur = 'Une disponibilité existe déjà sur cette plage horaire pour ce jour.';

            return;
        }

        // La modification ne doit pas rendre incompatible une intervention
        // déjà programmée dans la salle.
        $conflits = $this->interventionsAffectees($salle, $dispo, [
            'jour_semaine' => (int) $this->dispoJourSemaine,
            'heure_debut' => $this->dispoHeureDebut,
            'heure_fin' => $this->dispoHeureFin,
        ]);

        if ($conflits->isNotEmpty()) {
            $this->messageErreur = $this->messageConflitInterventions($conflits);

            return;
        }

        $dispo->update([
            'jour_semaine' => (int) $this->dispoJourSemaine,
            'heure_debut' => $this->dispoHeureDebut,
            'heure_fin' => $this->dispoHeureFin,
        ]);

        $this->messageSucces = 'Disponibilité modifiée.';

        $this->annulerModificationDisponibilite();
    }

    /**
     * Revient au formulaire de création d'une disponibilité.
     */
    public function annulerModificationDisponibilite(): void
    {
        $this->dispoId = null;
        $this->dispoSalleId = null;
        $this->dispoJourSemaine = null;
        $this->dispoHeureDebut = null;
        $this->dispoHeureFin = null;
        $this->dispoActive = true;
        $this->resetValidation();
    }

    public function basculerDisponibilite(int $id): void
    {
        abort_unless(auth()->user()->can('salles.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $dispo = DisponibiliteSalle::whereKey($id)
            ->whereHas('salle', fn ($q) => $q->whereIn('service_id', $this->servicesAutorises()))
            ->first();

        if (! $dispo) {
            $this->messageErreur = 'Disponibilité introuvable dans votre service.';

            return;
        }

        $dispo->update(['active' => ! $dispo->active]);

        $this->messageSucces = $dispo->active ? 'Disponibilité réactivée.' : 'Disponibilité désactivée.';
    }

    /**
     * Une disponibilité chevauche-t-elle une autre plage active de la même
     * salle et du même jour ?
     */
    private function chevauchementDisponibilite(
        Salle $salle,
        int $jourSemaine,
        string $heureDebut,
        string $heureFin,
        ?int $dispoExclu = null
    ): bool {
        return DisponibiliteSalle::query()
            ->where('salle_id', $salle->id)
            ->when($dispoExclu !== null, fn ($q) => $q->where('id', '!=', $dispoExclu))
            ->where('jour_semaine', $jourSemaine)
            ->where('active', true)
            ->where('heure_debut', '<', $heureFin)
            ->where('heure_fin', '>', $heureDebut)
            ->exists();
    }

    /**
     * Interventions actives de la salle qui seraient rendues incompatibles
     * par la nouvelle plage : elles étaient couvertes par les horaires
     * actuels et ne le seraient plus après la modification.
     *
     * @param  array{jour_semaine: int, heure_debut: string, heure_fin: string}  $nouvellePlage
     * @return Collection<int, \App\Models\RendezVous>
     */
    private function interventionsAffectees(Salle $salle, DisponibiliteSalle $dispo, array $nouvellePlage): Collection
    {
        $service = app(SalleService::class);

        $plagesActuelles = $this->plagesActives($salle);
        $plagesApres = $this->plagesActives($salle, $dispo->id);

        if ($dispo->active) {
            $plagesApres[] = $nouvellePlage;
        }

        $nonCouvertesAvant = $service->interventionsNonCouvertes($salle, $plagesActuelles);
        $nonCouvertesApres = $service->interventionsNonCouvertes($salle, $plagesApres);

        return $nonCouvertesApres->diff($nonCouvertesAvant);
    }

    /**
     * Plages horaires actives d'une salle, éventuellement en excluant une
     * disponibilité (cas de la modification).
     *
     * @return array<int, array{jour_semaine: int, heure_debut: string, heure_fin: string}>
     */
    private function plagesActives(Salle $salle, ?int $dispoExclu = null): array
    {
        return $salle->disponibilites()
            ->where('active', true)
            ->when($dispoExclu !== null, fn ($q) => $q->where('id', '!=', $dispoExclu))
            ->get()
            ->map(fn (DisponibiliteSalle $dispo) => [
                'jour_semaine' => (int) $dispo->jour_semaine,
                'heure_debut' => $dispo->heure_debut,
                'heure_fin' => $dispo->heure_fin,
            ])
            ->all();
    }

    /**
     * Message d'erreur expliquant qu'une modification met en cause des
     * interventions déjà programmées.
     *
     * @param  Collection<int, \App\Models\RendezVous>  $conflits
     */
    private function messageConflitInterventions(Collection $conflits): string
    {
        $nombre = $conflits->count();
        $premier = $conflits->first();

        return sprintf(
            'Cette modification rendrait incompatible %d intervention%s déjà programmée%s dans cette salle (%s à %s). '
                .'Déplacez ou annulez %s avant de modifier les horaires.',
            $nombre,
            $nombre > 1 ? 's' : '',
            $nombre > 1 ? 's' : '',
            $premier->date->format('d/m/Y'),
            Str::substr((string) $premier->heure_debut, 0, 5),
            $nombre > 1 ? 'ces rendez-vous' : 'ce rendez-vous'
        );
    }

    public function render()
    {
        $services = $this->servicesAutorises();

        $salles = empty($services)
            ? collect()
            : Salle::query()
                ->whereIn('service_id', $services)
                ->with(['disponibilites'])
                ->orderBy('nom')
                ->get();

        $disponibilites = empty($services)
            ? collect()
            : DisponibiliteSalle::query()
                ->whereHas('salle', fn ($q) => $q->whereIn('service_id', $services))
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

        // Salle de la disponibilité en cours de modification.
        $salleEnEdition = $this->dispoId !== null
            ? $disponibilites->firstWhere('id', $this->dispoId)?->salle
            : null;

        return view('livewire.secretaire.salles', [
            'salles' => $salles,
            'disponibilites' => $disponibilites,
            'jours' => $jours,
            'salleEnEdition' => $salleEnEdition,
        ])->layout('layouts.app');
    }
}
