<?php

namespace App\Infrastructure\Pii;

use RuntimeException;

final class DatoPiiException extends RuntimeException
{
    public static function failClosed(): self
    {
        return new self('No fue posible proteger el dato.');
    }
}
