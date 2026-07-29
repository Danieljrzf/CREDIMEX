<?php

namespace App\Infrastructure\Database;

final class PostgreSqlRoleSnapshot
{
    public function __construct(
        public readonly string $name,
        public readonly bool $exists,
        public readonly bool $canLogin,
        public readonly bool $hasConnect,
    ) {
    }
}
