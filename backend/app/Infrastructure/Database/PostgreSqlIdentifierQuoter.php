<?php

namespace App\Infrastructure\Database;

interface PostgreSqlIdentifierQuoter
{
    /**
     * Devuelve el identificador delimitado por PostgreSQL quote_ident.
     */
    public function quoteIdent(string $identifier): string;
}
