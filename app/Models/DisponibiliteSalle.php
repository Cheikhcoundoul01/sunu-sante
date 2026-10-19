<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibiliteSalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'salle_id',
        'service_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'jour_semaine' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Salle concernée par cette disponibilité.
     */
    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    /**
     * Service de rattachement.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Durée de la plage en minutes.
     */
    public function dureeMinutes(): int
    {
        return $this->heureDebutMinutes() !== null && $this->heureFinMinutes() !== null
            ? $this->heureFinMinutes() - $this->heureDebutMinutes()
            : 0;
    }

    public function heureDebutMinutes(): ?int
    {
        return $this->heureEnMinutes($this->heure_debut);
    }

    public function heureFinMinutes(): ?int
    {
        return $this->heureEnMinutes($this->heure_fin);
    }

    private function heureEnMinutes(?string $heure): ?int
    {
        if ($heure === null || $heure === '') {
            return null;
        }

        $parties = explode(':', $heure);

        return ((int) $parties[0]) * 60 + (int) ($parties[1] ?? 0);
    }
}