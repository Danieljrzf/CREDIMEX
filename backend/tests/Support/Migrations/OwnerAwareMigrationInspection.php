<?php

namespace Tests\Support\Migrations;

use App\Infrastructure\Database\PostgreSqlBooleanConverter;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use Tests\Support\PostgreSqlTestSafetyGuard;

final class OwnerAwareMigrationInspection
{
    private const IDENTIFIER_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        private readonly Connection $ownerConnection,
    ) {}

    public function tableExists(string $table): bool
    {
        $this->assertSafeIdentifier($table);

        return $this->ownerConnection->getSchemaBuilder()->hasTable($table);
    }

    public function migrationIsRegistered(string $migration): bool
    {
        return $this->ownerConnection
            ->table('migrations')
            ->where('migration', $migration)
            ->exists();
    }

    public function migrationBatch(string $migration): ?int
    {
        $batch = $this->ownerConnection
            ->table('migrations')
            ->where('migration', $migration)
            ->value('batch');

        return $batch === null ? null : (int) $batch;
    }

    /**
     * @return array<string, int>
     */
    public function migrationBatches(): array
    {
        $rows = $this->ownerConnection->select(
            'select migration, batch
             from migrations
             order by migration'
        ) ?? [];
        $migrations = [];

        foreach ($rows as $row) {
            $migrations[(string) $row->migration] = (int) $row->batch;
        }

        return $migrations;
    }

    /**
     * @return object{migration: string, batch: int}|null
     */
    public function latestMigration(): ?object
    {
        $rows = $this->ownerConnection->select(
            'select migration, batch
             from migrations
             order by batch desc, migration desc
             limit 1'
        ) ?? [];
        $row = $rows[0] ?? null;

        if ($row === null) {
            return null;
        }

        return (object) [
            'migration' => (string) $row->migration,
            'batch' => (int) $row->batch,
        ];
    }

    /**
     * @return object{schema_name: string, owner_name: string}|null
     */
    public function tableMetadata(string $table): ?object
    {
        $this->assertSafeIdentifier($table);

        return $this->ownerConnection->selectOne(
            'select n.nspname as schema_name, r.rolname as owner_name
             from pg_class c
             join pg_namespace n on n.oid = c.relnamespace
             join pg_roles r on r.oid = c.relowner
             where n.nspname = ? and c.relname = ? and c.relkind in (\'r\', \'p\')',
            [PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $table]
        );
    }

    public function hasIdentityPrimaryKey(string $table): bool
    {
        $this->assertSafeIdentifier($table);

        $row = $this->ownerConnection->selectOne(
            'select exists (
                select 1
                from information_schema.columns col
                where col.table_schema = ?
                  and col.table_name = ?
                  and col.is_identity = \'YES\'
                  and exists (
                      select 1
                      from information_schema.table_constraints tc
                      join information_schema.key_column_usage kcu
                        on kcu.constraint_catalog = tc.constraint_catalog
                       and kcu.constraint_schema = tc.constraint_schema
                       and kcu.constraint_name = tc.constraint_name
                      where tc.table_schema = col.table_schema
                        and tc.table_name = col.table_name
                        and tc.constraint_type = \'PRIMARY KEY\'
                        and kcu.column_name = col.column_name
                  )
            ) as value',
            [PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $table]
        );

        return PostgreSqlBooleanConverter::toBool($row?->value);
    }

    public function hasForeignKey(
        string $table,
        string $constraint,
        string $referencedTable,
        string $deleteRule = 'NO ACTION',
    ): bool {
        $this->assertSafeIdentifier($table);
        $this->assertSafeIdentifier($constraint);
        $this->assertSafeIdentifier($referencedTable);

        $row = $this->ownerConnection->selectOne(
            'select exists (
                select 1
                from information_schema.table_constraints tc
                join information_schema.referential_constraints rc
                  on rc.constraint_catalog = tc.constraint_catalog
                 and rc.constraint_schema = tc.constraint_schema
                 and rc.constraint_name = tc.constraint_name
                join information_schema.constraint_column_usage ccu
                  on ccu.constraint_catalog = tc.constraint_catalog
                 and ccu.constraint_schema = tc.constraint_schema
                 and ccu.constraint_name = tc.constraint_name
                where tc.table_schema = ?
                  and tc.table_name = ?
                  and tc.constraint_name = ?
                  and tc.constraint_type = \'FOREIGN KEY\'
                  and ccu.table_schema = ?
                  and ccu.table_name = ?
                  and rc.delete_rule = ?
            ) as value',
            [
                PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA,
                $table,
                $constraint,
                PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA,
                $referencedTable,
                $deleteRule,
            ]
        );

        return PostgreSqlBooleanConverter::toBool($row?->value);
    }

    public function appHasTablePrivilege(string $table, string $privilege): bool
    {
        $this->assertSafeIdentifier($table);
        $this->assertTablePrivilege($privilege);

        $row = $this->ownerConnection->selectOne(
            'select has_table_privilege(?, ?, ?) as value',
            [
                PostgreSqlTestSafetyGuard::EXPECTED_APP_USER,
                PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA.'.'.$table,
                $privilege,
            ]
        );

        return PostgreSqlBooleanConverter::toBool($row?->value);
    }

    public function appHasSequencePrivilege(string $sequence, string $privilege): bool
    {
        $this->assertSafeIdentifier($sequence);

        if ($privilege !== 'USAGE') {
            throw new InvalidArgumentException('Solo puede inspeccionarse el privilegio USAGE de secuencias.');
        }

        $row = $this->ownerConnection->selectOne(
            'select has_sequence_privilege(?, ?, ?) as value',
            [
                PostgreSqlTestSafetyGuard::EXPECTED_APP_USER,
                PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA.'.'.$sequence,
                $privilege,
            ]
        );

        return PostgreSqlBooleanConverter::toBool($row?->value);
    }

    private function assertSafeIdentifier(string $identifier): void
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw new InvalidArgumentException('El identificador PostgreSQL solicitado no es seguro.');
        }
    }

    private function assertTablePrivilege(string $privilege): void
    {
        if (! in_array($privilege, ['SELECT', 'INSERT', 'UPDATE', 'DELETE'], true)) {
            throw new InvalidArgumentException('El privilegio de tabla solicitado no está permitido.');
        }
    }
}
