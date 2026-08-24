<?php

namespace Tests\Support\Migrations;

use RuntimeException;

final class OwnerAwareMigrationException extends RuntimeException
{
    private function __construct(
        public readonly string $stage,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forStage(string $stage, string $detail): self
    {
        return new self(
            $stage,
            "Falló la etapa [{$stage}] del escenario owner-aware: {$detail}",
        );
    }

    public static function forCleanup(?self $originalFailure): self
    {
        $detail = $originalFailure === null
            ? 'No fue posible revertir y verificar completamente la transacción exterior.'
            : "El cleanup falló después de la etapa [{$originalFailure->stage}], que ya había fallado.";

        return self::forStage('cleanup', $detail);
    }
}
