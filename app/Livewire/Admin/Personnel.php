<?php

namespace App\Livewire\Admin;

use App\Models\Service;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Personnel extends Component
{
    use WithPagination;

    /**
     * Rôles du personnel gérés par l'administrateur (noms Spatie exacts).
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'Médecin',
        'Secrétaire de service',
        "Agent d'accueil",
        'Major de service',
    ];

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public ?int $service_id = null;

    /**
     * Services supplémentaires affectés au personnel (secrétaires uniquement).
     *
     * @var array<int, int|string>
     */
    public array $servicesSupplementaires = [];

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

    public string $search = '';

    public string $filtreRole = 'Tous';

    public string $filtreService = 'Tous';

    public string $filtreEtat = 'Tous';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('users.gerer'), 403);

        // Garantit un formulaire vierge au chargement : aucune erreur résiduelle.
        $this->resetValidation();
    }

    /**
     * Réinitialise la pagination dès qu'un critère de recherche ou de filtre change.
     */
    public function updating(string $propriete): void
    {
        if (in_array($propriete, ['search', 'filtreRole', 'filtreService', 'filtreEtat'], true)) {
            $this->resetPage();
        }
    }

    public function reinitialiserFiltres(): void
    {
        $this->search = '';
        $this->filtreRole = 'Tous';
        $this->filtreService = 'Tous';
        $this->filtreEtat = 'Tous';
        $this->resetPage();
    }

    /**
     * Les services supplémentaires ne concernent que la secrétaire : on les
     * vide dès que le rôle sélectionné change pour un autre rôle.
     */
    public function updatedRole(string $valeur): void
    {
        if ($valeur !== 'Secrétaire de service') {
            $this->servicesSupplementaires = [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $reglesMotDePasse = $this->editingId !== null
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('actif', true),
            ],
            'servicesSupplementaires' => [function (string $attribute, mixed $value, \Closure $fail) {
                // Les services supplémentaires ne concernent que la secrétaire.
                if ($this->role !== 'Secrétaire de service') {
                    if (! empty($value)) {
                        $fail('Les services supplémentaires ne concernent que le rôle Secrétaire de service.');
                    }

                    return;
                }

                if (! is_array($value)) {
                    $fail('Les services supplémentaires sont invalides.');

                    return;
                }

                $principale = $this->service_id;

                foreach ($value as $idService) {
                    if ($idService === '' || $idService === null) {
                        continue;
                    }

                    $idService = (int) $idService;

                    $existe = Service::query()
                        ->whereKey($idService)
                        ->where('actif', true)
                        ->exists();

                    if (! $existe) {
                        $fail('Un des services supplémentaires est introuvable ou inactif.');

                        return;
                    }

                    if ($principale !== null && $idService === (int) $principale) {
                        $fail('Le service principal ne doit pas être répété dans les services supplémentaires.');

                        return;
                    }
                }
            }],
            'password' => $reglesMotDePasse,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => "L'adresse e-mail est obligatoire.",
            'email.email' => "L'adresse e-mail est invalide.",
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'role.required' => 'Veuillez sélectionner un rôle.',
            'role.in' => "Ce rôle n'est pas autorisé.",
            'service_id.required' => 'Veuillez sélectionner un service.',
            'service_id.exists' => 'Le service sélectionné est introuvable ou inactif.',
            'password.required' => 'Le mot de passe initial est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->can('users.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $donnees = $this->validate();

        if ($this->editingId !== null) {
            $utilisateur = User::whereKey($this->editingId)->firstOrFail();
            abort_unless($utilisateur->hasAnyRole(self::ROLES), 403);

            $miseAJour = [
                'name' => $donnees['name'],
                'email' => $donnees['email'],
                'service_id' => $donnees['service_id'],
            ];

            // Mot de passe laissé vide : on ne le modifie pas.
            if (! empty($donnees['password'])) {
                $miseAJour['password'] = $donnees['password'];
            }

            $utilisateur->update($miseAJour);
            $utilisateur->syncRoles([$donnees['role']]);
            $this->synchroniserServicesSupplementaires($utilisateur, $donnees['role']);

            $this->messageSucces = 'Compte du personnel mis à jour.';
        } else {
            $utilisateur = User::create([
                'name' => $donnees['name'],
                'email' => $donnees['email'],
                'password' => $donnees['password'],
                'statut_compte' => 'Actif',
                'service_id' => $donnees['service_id'],
            ]);

            $utilisateur->syncRoles([$donnees['role']]);
            $this->synchroniserServicesSupplementaires($utilisateur, $donnees['role']);

            $this->messageSucces = 'Compte du personnel créé.';
        }

        $this->reinitialiserFormulaire();
    }

    public function editer(int $id): void
    {
        abort_unless(auth()->user()->can('users.gerer'), 403);

        $utilisateur = User::whereKey($id)->firstOrFail();
        abort_unless($utilisateur->hasAnyRole(self::ROLES), 403);

        $this->editingId = $utilisateur->id;
        $this->name = $utilisateur->name;
        $this->email = $utilisateur->email;
        $this->role = $utilisateur->getRoleNames()->first() ?? '';
        $this->service_id = $utilisateur->service_id;
        $this->servicesSupplementaires = $utilisateur->servicesSupplementaires()
            ->pluck('services.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->password = '';
        $this->password_confirmation = '';

        $this->messageSucces = null;
        $this->messageErreur = null;
        $this->resetValidation();
    }

    public function annulerEdition(): void
    {
        $this->reinitialiserFormulaire();
    }

    public function basculer(int $id): void
    {
        abort_unless(auth()->user()->can('users.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $utilisateur = User::whereKey($id)->firstOrFail();
        abort_unless($utilisateur->hasAnyRole(self::ROLES), 403);

        $desactive = $utilisateur->statut_compte === 'Actif';

        $utilisateur->update([
            'statut_compte' => $desactive ? 'Rejeté' : 'Actif',
        ]);

        $this->messageSucces = $desactive
            ? 'Compte du personnel désactivé.'
            : 'Compte du personnel réactivé.';
    }

    protected function reinitialiserFormulaire(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->role = '';
        $this->service_id = null;
        $this->servicesSupplementaires = [];
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
    }

    /**
     * Synchronise la table pivot `service_user`. Les services supplémentaires
     * ne sont conservés que pour le rôle « Secrétaire de service » : pour tout
     * autre rôle, les affectations supplémentaires sont retirées.
     */
    protected function synchroniserServicesSupplementaires(User $utilisateur, string $role): void
    {
        if ($role !== 'Secrétaire de service') {
            $utilisateur->servicesSupplementaires()->sync([]);

            return;
        }

        $ids = collect($this->servicesSupplementaires)
            ->filter(fn ($id) => $id !== '' && $id !== null)
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => (int) $id === (int) $this->service_id)
            ->unique()
            ->values()
            ->all();

        $utilisateur->servicesSupplementaires()->sync($ids);
    }

    public function render()
    {
        $base = User::query()->role(self::ROLES);

        $totalComptes = (clone $base)->count();
        $totalActifs = (clone $base)->where('statut_compte', 'Actif')->count();

        $personnel = $base
            ->with(['service', 'servicesSupplementaires', 'roles'])
            ->when($this->search !== '', function ($q) {
                $terme = '%'.$this->search.'%';
                $q->where(function ($sq) use ($terme) {
                    $sq->where('name', 'like', $terme)
                        ->orWhere('email', 'like', $terme);
                });
            })
            ->when($this->filtreRole !== 'Tous', function ($q) {
                $q->role($this->filtreRole);
            })
            ->when($this->filtreService !== 'Tous', function ($q) {
                $q->where('service_id', (int) $this->filtreService);
            })
            ->when($this->filtreEtat !== 'Tous', function ($q) {
                $q->where('statut_compte', $this->filtreEtat);
            })
            // Affichage : les comptes actifs avant les comptes désactivés.
            ->orderByRaw("CASE WHEN statut_compte = 'Actif' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(10);

        $services = Service::query()
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        return view('livewire.admin.personnel', [
            'personnel' => $personnel,
            'services' => $services,
            'roles' => self::ROLES,
            'etats' => ['Tous', 'Actif', 'Rejeté'],
            'totalComptes' => $totalComptes,
            'totalActifs' => $totalActifs,
        ])->layout('layouts.admin');
    }
}
