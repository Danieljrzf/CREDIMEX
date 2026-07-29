<?php

namespace App\Infrastructure\Database;

interface PostgreSqlGrantContextInspector
{
    /**
     * Inspecciona conexiones pgsql / pgsql_owner y el rol receptor.
     *
     * @throws \Throwable
     */
    public function inspect(string $configuredAppRole): PostgreSqlGrantContextParts;
}
