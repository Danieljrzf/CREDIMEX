<?php

namespace Tests\Unit\Infrastructure\Database;

use App\Infrastructure\Database\PostgreSqlBooleanConverter;
use App\Infrastructure\Database\PostgreSqlGrantException;
use PHPUnit\Framework\TestCase;

final class PostgreSqlBooleanConverterTest extends TestCase
{
    public function test_converts_t_to_true(): void
    {
        $this->assertTrue(PostgreSqlBooleanConverter::toBool('t'));
    }

    public function test_converts_f_to_false(): void
    {
        $this->assertFalse(PostgreSqlBooleanConverter::toBool('f'));
    }

    public function test_rejects_unknown_boolean_value_fail_closed(): void
    {
        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('No fue posible interpretar un valor booleano de PostgreSQL.');

        PostgreSqlBooleanConverter::toBool('yes');
    }
}
