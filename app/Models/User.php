<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'telephone',
        'password',
        'statut_compte',
        'service_id',
        'traite_par_id',
        'traite_at',
        'is_demo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telephone',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'traite_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * La personne (secrétaire) ayant traité le dossier de ce compte.
     */
    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par_id');
    }

    /**
     * Service auquel appartient ce membre du personnel (personnel de service).
     *
     * Pour un secrétaire multi-service, il s'agit du service principal.
     * Les autres rôles n'utilisent que cette relation.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Services supplémentaires explicitement affectés à ce secrétaire.
     * Ne concerne que le rôle « Secrétaire de service » ; vide pour les autres rôles.
     */
    public function servicesSupplementaires(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_user')
            ->withTimestamps();
    }

    /**
     * Identifiants des services autorisés pour ce secrétaire : service principal
     * + services supplémentaires affectés. Renvoie un tableau vide pour un
     * utilisateur sans service principal ni affectation (ex. patient).
     *
     * @return array<int, int>
     */
    public function servicesAutorisesIds(): array
    {
        $ids = [];

        if ($this->service_id !== null) {
            $ids[] = (int) $this->service_id;
        }

        foreach ($this->servicesSupplementaires()->pluck('services.id') as $id) {
            $ids[] = (int) $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Entrée dans la file d'attente (patient).
     */
    public function fileEntree(): BelongsTo
    {
        return $this->belongsTo(FileEntree::class, 'id', 'patient_id');
    }

    /**
     * Rôles du personnel (tous les rôles, sauf Patient).
     * Réutilise les noms de rôles existants (RolePermissionSeeder).
     *
     * @var list<string>
     */
    public const ROLES_PERSONNEL = [
        'Secrétaire de service',
        "Agent d'accueil",
        'Médecin',
        'Major de service',
        'Administrateur',
    ];

    /**
     * Nom de route de l'espace correspondant au rôle, ou null si inconnu.
     */
    public function routeSelonRole(): ?string
    {
        return match (true) {
            $this->hasRole('Patient') => 'patient.dashboard',
            $this->hasRole('Secrétaire de service') => 'secretaire.dashboard',
            $this->hasRole("Agent d'accueil") => 'accueil.dashboard',
            $this->hasRole('Médecin') => 'medecin.dashboard',
            $this->hasRole('Major de service') => 'major.dashboard',
            $this->hasRole('Administrateur') => 'admin.dashboard',
            default => null,
        };
    }

    /**
     * Nom à afficher à l'écran : ajoute le titre « Dr. » aux médecins
     * uniquement à l'affichage (jamais en base), sans dupliquer
     * un préfixe déjà présent dans le nom enregistré.
     */
    public function getNomAfficheAttribute(): string
    {
        if (! $this->hasRole('Médecin')) {
            return $this->name;
        }

        $sansTitre = trim((string) preg_replace('/^dr(?:\.|\s)+\s*/iu', '', $this->name));

        return $sansTitre !== '' ? 'Dr. '.$sansTitre : $this->name;
    }

    public function estActif(): bool
    {
        return $this->statut_compte === 'Actif';
    }

    public function estEnAttente(): bool
    {
        return $this->statut_compte === 'En attente';
    }

    public function estRejete(): bool
    {
        return $this->statut_compte === 'Rejeté';
    }

    /**
     * Indique si ce compte appartient au personnel (et non à un patient).
     */
    public function estPersonnel(): bool
    {
        return $this->hasAnyRole(self::ROLES_PERSONNEL);
    }

    /**
     * Message à afficher lorsqu'un compte au statut inactif tente d'accéder à la plateforme.
     */
    public function messageCompteInactif(): string
    {
        if ($this->estRejete()) {
            return $this->estPersonnel()
                ? "Votre compte a été désactivé. Veuillez contacter l'administrateur."
                : "Votre compte n'a pas été retenu.";
        }

        return 'Votre compte est en attente de validation.';
    }
}
