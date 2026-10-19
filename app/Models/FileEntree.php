<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileEntree extends Model
{
    use HasFactory;

    public const STATUT_EN_ATTENTE = 'En attente';
    public const STATUT_EN_COURS = 'En cours';
    public const STATUT_TERMINE = 'Terminé';
    public const STATUT_ABSENT = 'Absent';

    public static function statuts(): array
    {
        return [
            self::STATUT_EN_ATTENTE,
            self::STATUT_EN_COURS,
            self::STATUT_TERMINE,
            self::STATUT_ABSENT,
        ];
    }

    /**
     * Transitions autorisées pour l'agent d'accueil.
     *
     * @return array<string, array<int, string>>
     */
    public static function transitionsAutorisees(): array
    {
        return [
            self::STATUT_EN_ATTENTE => [self::STATUT_EN_COURS, self::STATUT_ABSENT],
            self::STATUT_EN_COURS => [self::STATUT_TERMINE],
            self::STATUT_ABSENT => [self::STATUT_EN_ATTENTE],
            self::STATUT_TERMINE => [],
        ];
    }

    protected $fillable = [
        'service_id',
        'rendez_vous_id',
        'patient_id',
        'date',
        'statut',
        'position',
        'priorite',
        'motif_priorite',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'priorite' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function rendezVous(): BelongsTo
    {
        return $this->belongsTo(RendezVous::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estEnCours(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    public function estTermine(): bool
    {
        return $this->statut === self::STATUT_TERMINE;
    }

    public function estAbsent(): bool
    {
        return $this->statut === self::STATUT_ABSENT;
    }

    /**
     * Vérifie si une transition de statut est autorisée.
     */
    public function peutTransitionnerVers(string $nouveauStatut): bool
    {
        return in_array($nouveauStatut, self::transitionsAutorisees()[$this->statut] ?? [], true);
    }
}