<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVous extends Model
{
    use HasFactory;

    public const STATUT_DEMANDE = 'Demandé';

    public const STATUT_VALIDE = 'Validé';

    public const STATUT_ANNULE = 'Annulé';

    /**
     * Statuts actifs : occupent le créneau (AGENTS.md §6).
     *
     * @return array<int, string>
     */
    public static function statutsActifs(): array
    {
        return [self::STATUT_DEMANDE, self::STATUT_VALIDE];
    }

    protected $fillable = [
        'patient_id',
        'medecin_id',
        'service_id',
        'salle_id',
        'date',
        'heure_debut',
        'duree_prevue',
        'statut',
        'motif',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function medecin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medecin_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function estIntervention(): bool
    {
        return $this->salle_id !== null;
    }

    public function estActif(): bool
    {
        return in_array($this->statut, self::statutsActifs(), true);
    }
}
