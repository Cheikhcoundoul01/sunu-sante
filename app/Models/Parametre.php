<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametre extends Model
{
    public const CLE_HORIZON_RESERVATION = 'horizon_reservation_jours';

    public const HORIZON_RESERVATION_DEFAUT = 30;

    protected $fillable = [
        'cle',
        'valeur',
    ];

    /**
     * Récupère la valeur d'un paramètre, ou $defaut si absent.
     */
    public static function valeur(string $cle, ?string $defaut = null): ?string
    {
        return static::query()->where('cle', $cle)->value('valeur') ?? $defaut;
    }

    /**
     * Horizon de réservation en jours, défini par l'administrateur.
     */
    public static function horizonReservationJours(): int
    {
        $valeur = static::valeur(
            self::CLE_HORIZON_RESERVATION,
            (string) self::HORIZON_RESERVATION_DEFAUT
        );

        if (! is_numeric($valeur)) {
            return self::HORIZON_RESERVATION_DEFAUT;
        }

        $jours = (int) $valeur;

        return $jours > 0 ? $jours : self::HORIZON_RESERVATION_DEFAUT;
    }
}
