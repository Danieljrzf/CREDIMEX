<?php

namespace App\Infrastructure\Database;

use Illuminate\Database\DatabaseManager;
use Throwable;

final class LaravelPostgreSqlIdentifierQuoter implements PostgreSqlIdentifierQuoter
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    public function quoteIdent(string $identifier): string
    {
        try {
            $row = $this->db->connection('pgsql_owner')->selectOne(
                'select quote_ident(?) as quoted',
                [$identifier]
            );
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                'No fue posible delimitar un identificador PostgreSQL con quote_ident.'
            );
        }

        if ($row === null || ! isset($row->quoted) || $row->quoted === '') {
            throw new PostgreSqlGrantException(
                'quote_ident no devolvió un identificador delimitado válido.'
            );
        }

        return (string) $row->quoted;
    }
}
