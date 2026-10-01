<?php

namespace App\Infrastructure\Pii;

use RuntimeException;

final class TelefonoInsuficienteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El teléfono no tiene suficientes dígitos.');
    }
}
