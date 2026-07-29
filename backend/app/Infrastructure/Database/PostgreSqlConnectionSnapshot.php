<?php

namespace App\Infrastructure\Database;

final class PostgreSqlConnectionSnapshot
{
    public function __construct(
        public readonly string $database,
        public readonly string $user,
        public readonly string $schema,
        public readonly string $searchPath,
        public readonly string $schemasText,
    ) {
    }
}
