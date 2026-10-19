<?php

namespace App\Exceptions;

use RuntimeException;

class DossierDejaTraiteException extends RuntimeException
{
    protected $message = 'Dossier déjà traité';
}
