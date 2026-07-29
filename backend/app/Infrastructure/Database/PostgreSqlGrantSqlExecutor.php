<?php

namespace App\Infrastructure\Database;

interface PostgreSqlGrantSqlExecutor
{
    /**
     * Ejecuta una sentencia GRANT/REVOKE ya validada sobre pgsql_owner.
     */
    public function execute(string $sql): void;
}
