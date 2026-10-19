<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * Membres du personnel rattachés à ce service.
     */
    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Disponibilités rattachées à ce service.
     */
    public function disponibilites(): HasMany
    {
        return $this->hasMany(Disponibilite::class);
    }

    /**
     * Rendez-vous rattachés à ce service.
     */
    public function rendezVous(): HasMany
    {
        return $this->hasMany(RendezVous::class);
    }
}
