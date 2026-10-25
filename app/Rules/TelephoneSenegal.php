<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valide et normalise un numéro de téléphone sénégalais.
 *
 * Formats acceptés : 771234567, 77 123 45 67, +221771234567, +221 77 123 45 67.
 * Le numéro est stocké sous forme locale à 9 chiffres (ex. 771234567).
 */
class TelephoneSenegal implements ValidationRule
{
    private const PREFIXES_AUTORISES = ['70', '75', '76', '77', '78'];

    /**
     * Normalise une valeur vers les 9 chiffres locaux, ou null si invalide/vide.
     */
    public static function normaliser(?string $valeur): ?string
    {
        if ($valeur === null) {
            return null;
        }

        // Suppression des espaces ASCII uniquement : évite les acceptations trop permissives.
        $compact = str_replace(' ', '', trim($valeur));

        if ($compact === '') {
            return null;
        }

        if (str_starts_with($compact, '+221')) {
            $compact = substr($compact, 4);
        }

        if (! preg_match('/^[0-9]{9}$/', $compact)) {
            return null;
        }

        if (! in_array(substr($compact, 0, 2), self::PREFIXES_AUTORISES, true)) {
            return null;
        }

        return $compact;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Un champ vide est géré par la règle "required" (inscription) ou "nullable" (profil).
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || self::normaliser($value) === null) {
            $fail('Le numéro de téléphone doit contenir 9 chiffres et commencer par 70, 75, 76, 77 ou 78.');
        }
    }
}
