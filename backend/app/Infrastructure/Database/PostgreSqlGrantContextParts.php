<?php

namespace App\Infrastructure\Database;

/**
 * Partes crudas obtenidas de PostgreSQL antes de aplicar reglas de entorno.
 */
final class PostgreSqlGrantContextParts
{
    public function __construct(
        public readonly PostgreSqlConnectionSnapshot $appConnection,
        public readonly PostgreSqlConnectionSnapshot $ownerConnection,
        public readonly PostgreSqlRoleSnapshot $role,
    ) {
    }
}
