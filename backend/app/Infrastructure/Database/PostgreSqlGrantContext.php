<?php

namespace App\Infrastructure\Database;

/**
 * Contexto validable para operaciones GRANT/REVOKE.
 * Separado de la ejecución SQL para poder probar reglas sin PostgreSQL.
 */
final class PostgreSqlGrantContext
{
    public function __construct(
        public readonly string $environment,
        public readonly string $defaultConnection,
        public readonly ?string $pgsqlDriver,
        public readonly ?string $pgsqlOwnerDriver,
        public readonly string $configuredAppRole,
        public readonly string $expectedDatabase,
        public readonly string $expectedAppUser,
        public readonly string $expectedOwnerUser,
        public readonly string $expectedSchema,
        public readonly PostgreSqlConnectionSnapshot $appConnection,
        public readonly PostgreSqlConnectionSnapshot $ownerConnection,
        public readonly PostgreSqlRoleSnapshot $role,
    ) {
    }
}
