<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salle extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'nom',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Service auquel appartient cette salle.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Disponibilités hebdomadaires de cette salle.
     */
    public function disponibilites(): HasMany
    {
        return $this->hasMany(DisponibiliteSalle::class);
    }

    /**
     * Rendez-vous d'intervention programmés dans cette salle.
     */
    public function rendezVous(): HasMany
    {
        return $this->hasMany(RendezVous::class);
    }

    /**
     * Vérifie si la salle est disponible pour un créneau donné.
     */
    public function estDisponiblePour(int $jourSemaine, string $heureDebut, string $heureFin, ?int $excludeRdvId = null): bool
    {
        if (! $this->active) {
            return false;
        }

        $disponibilites = $this->disponibilites()
            ->where('jour_semaine', $jourSemaine)
            ->where('active', true)
            ->get();

        if ($disponibilites->isEmpty()) {
            return false;
        }

        $creneauDebut = $this->heureEnMinutes($heureDebut);
        $creneauFin = $this->heureEnMinutes($heureFin);

        if ($creneauDebut === null || $creneauFin === null || $creneauFin <= $creneauDebut) {
            return false;
        }

        // Vérifier que le créneau est dans une plage de disponibilité
        $dansPlage = false;
        foreach ($disponibilites as $dispo) {
            $dispoDebut = $this->heureEnMinutes($dispo->heure_debut);
            $dispoFin = $this->heureEnMinutes($dispo->heure_fin);
            if ($dispoDebut !== null && $dispoFin !== null && $creneauDebut >= $dispoDebut && $creneauFin <= $dispoFin) {
                $dansPlage = true;
                break;
            }
        }

        if (! $dansPlage) {
            return false;
        }

        return true;
    }

    private function heureEnMinutes(string $heure): ?int
    {
        $parties = explode(':', $heure);
        if (count($parties) < 2) {
            return null;
        }
        return ((int) $parties[0]) * 60 + (int) ($parties[1] ?? 0);
    }
}