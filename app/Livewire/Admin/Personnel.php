<?php

namespace App\Livewire\Admin;

use App\Models\Service;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Personnel extends Component
{
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

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('users.gerer'), 403);

        // Garantit un formulaire vierge au chargement : aucune erreur résiduelle.
        $this->resetValidation();
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
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
    }

    public function render()
    {
        $personnel = User::query()
            ->role(self::ROLES)
            ->with(['service', 'roles'])
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        return view('livewire.admin.personnel', [
            'personnel' => $personnel,
            'services' => $services,
            'roles' => self::ROLES,
        ])->layout('layouts.admin');
    }
}
