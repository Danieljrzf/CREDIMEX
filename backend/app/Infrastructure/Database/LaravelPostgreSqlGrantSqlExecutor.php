<?php

namespace App\Infrastructure\Database;

use Illuminate\Database\DatabaseManager;
use Throwable;

final class LaravelPostgreSqlGrantSqlExecutor implements PostgreSqlGrantSqlExecutor
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    public function execute(string $sql): void
    {
        try {
            $this->db->connection('pgsql_owner')->statement($sql);
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                'No fue posible ejecutar la operación de privilegios sobre pgsql_owner.'
            );
        }
    }
}
