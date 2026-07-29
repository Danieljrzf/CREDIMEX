<?php

namespace App\Infrastructure\Database;

/**
 * Conversión explícita de booleanos reportados por PostgreSQL/PDO.
 */
final class PostgreSqlBooleanConverter
{
    public static function toBool(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true') {
            return true;
        }

        if ($value === false || $value === 0 || $value === '0' || $value === 'f' || $value === 'false') {
            return false;
        }

        throw new PostgreSqlGrantException(
            'No fue posible interpretar un valor booleano de PostgreSQL.'
        );
    }
}
