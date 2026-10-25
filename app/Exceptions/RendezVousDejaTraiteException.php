<?php

namespace App\Exceptions;

use RuntimeException;

class RendezVousDejaTraiteException extends RuntimeException
{
    protected $message = 'Ce rendez-vous a déjà été traité.';
}
