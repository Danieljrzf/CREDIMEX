<?php

namespace Tests\Support;

interface PostgreSqlContextInspector
{
    /**
     * Obtiene el contexto real de una conexión PostgreSQL.
     */
    public function inspect(string $connectionName): PostgreSqlConnectionContext;
}
