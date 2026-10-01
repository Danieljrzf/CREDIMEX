<?php

namespace App\Clientes;

use RuntimeException;

final class ClienteOperacionRechazada extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $cuerpo
     */
    public function __construct(
        private readonly array $cuerpo,
        private readonly int $estadoHttp,
    ) {
        parent::__construct('Operación de cliente rechazada.');
    }

    /**
     * @return array<string, mixed>
     */
    public function cuerpo(): array
    {
        return $this->cuerpo;
    }

    public function estadoHttp(): int
    {
        return $this->estadoHttp;
    }
}
