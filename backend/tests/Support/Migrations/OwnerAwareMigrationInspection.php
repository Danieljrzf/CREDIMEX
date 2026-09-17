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

    /**
     * @return list<object{
     *     name: string,
     *     ordinal: int,
     *     data_type: string,
     *     udt_name: string,
     *     character_maximum_length: int|null,
     *     nullable: bool,
     *     default: string|null,
     *     identity: bool,
     *     identity_generation: string|null
     * }>
     */
    public function columns(string $table): array
    {
        $this->assertSafeIdentifier($table);
        $rows = $this->ownerConnection->select(
            'select column_name, ordinal_position, data_type, udt_name,
                    character_maximum_length, is_nullable, column_default,
                    is_identity, identity_generation
             from information_schema.columns
             where table_schema = ? and table_name = ?
             order by ordinal_position',
            [PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $table]
        );

        return array_map(static fn (object $row): object => (object) [
            'name' => (string) $row->column_name,
            'ordinal' => (int) $row->ordinal_position,
            'data_type' => (string) $row->data_type,
            'udt_name' => (string) $row->udt_name,
            'character_maximum_length' => $row->character_maximum_length === null
                ? null
                : (int) $row->character_maximum_length,
            'nullable' => $row->is_nullable === 'YES',
            'default' => $row->column_default === null ? null : (string) $row->column_default,
            'identity' => $row->is_identity === 'YES',
            'identity_generation' => $row->identity_generation === null
                ? null
                : (string) $row->identity_generation,
        ], $rows);
    }

    /**
     * @return object{name: string, type: string, columns: list<string>, definition: string}|null
     */
    public function constraintMetadata(string $table, string $constraint): ?object
    {
        $this->assertSafeIdentifier($table);
        $this->assertSafeIdentifier($constraint);
        $row = $this->ownerConnection->selectOne(
            'select con.conname,
                    case con.contype
                        when \'p\' then \'PRIMARY KEY\'
                        when \'u\' then \'UNIQUE\'
                        when \'c\' then \'CHECK\'
                        when \'f\' then \'FOREIGN KEY\'
                    end as constraint_type,
                    coalesce((
                        select json_agg(att.attname order by key.ordinality)::text
                        from unnest(con.conkey) with ordinality as key(attnum, ordinality)
                        join pg_attribute att
                          on att.attrelid = con.conrelid
                         and att.attnum = key.attnum
                    ), \'[]\') as columns_json,
                    pg_get_constraintdef(con.oid, true) as definition
             from pg_constraint con
             join pg_class rel on rel.oid = con.conrelid
             join pg_namespace ns on ns.oid = rel.relnamespace
             where ns.nspname = ? and rel.relname = ? and con.conname = ?',
            [PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $table, $constraint]
        );

        if ($row === null) {
            return null;
        }

        return (object) [
            'name' => (string) $row->conname,
            'type' => (string) $row->constraint_type,
            'columns' => $this->decodeIdentifierList((string) $row->columns_json),
            'definition' => (string) $row->definition,
        ];
    }

    /**
     * @return object{
     *     name: string,
     *     columns: list<string>,
     *     referenced_table: string,
     *     referenced_columns: list<string>,
     *     update_rule: string,
     *     delete_rule: string,
     *     deferrable: bool,
     *     initially_deferred: bool
     * }|null
     */
    public function foreignKeyMetadata(string $table, string $constraint): ?object
    {
        $this->assertSafeIdentifier($table);
        $this->assertSafeIdentifier($constraint);
        $row = $this->ownerConnection->selectOne(
            'select con.conname,
                    ref.relname as referenced_table,
                    (
                        select json_agg(src_att.attname order by key.ordinality)::text
                        from unnest(con.conkey) with ordinality as key(attnum, ordinality)
                        join pg_attribute src_att
                          on src_att.attrelid = con.conrelid
                         and src_att.attnum = key.attnum
                    ) as columns_json,
                    (
                        select json_agg(ref_att.attname order by key.ordinality)::text
                        from unnest(con.confkey) with ordinality as key(attnum, ordinality)
                        join pg_attribute ref_att
                          on ref_att.attrelid = con.confrelid
                         and ref_att.attnum = key.attnum
                    ) as referenced_columns_json,
                    case con.confupdtype
                        when \'a\' then \'NO ACTION\'
                        when \'r\' then \'RESTRICT\'
                        when \'c\' then \'CASCADE\'
                        when \'n\' then \'SET NULL\'
                        when \'d\' then \'SET DEFAULT\'
                    end as update_rule,
                    case con.confdeltype
                        when \'a\' then \'NO ACTION\'
                        when \'r\' then \'RESTRICT\'
                        when \'c\' then \'CASCADE\'
                        when \'n\' then \'SET NULL\'
                        when \'d\' then \'SET DEFAULT\'
                    end as delete_rule,
                    con.condeferrable,
                    con.condeferred
             from pg_constraint con
             join pg_class rel on rel.oid = con.conrelid
             join pg_namespace ns on ns.oid = rel.relnamespace
             join pg_class ref on ref.oid = con.confrelid
             join pg_namespace ref_ns on ref_ns.oid = ref.relnamespace
             where ns.nspname = ?
               and rel.relname = ?
               and con.conname = ?
               and con.contype = \'f\'
               and ref_ns.nspname = ?',
            [
                PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA,
                $table,
                $constraint,
                PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA,
            ]
        );

        if ($row === null) {
            return null;
        }

        return (object) [
            'name' => (string) $row->conname,
            'columns' => $this->decodeIdentifierList((string) $row->columns_json),
            'referenced_table' => (string) $row->referenced_table,
            'referenced_columns' => $this->decodeIdentifierList((string) $row->referenced_columns_json),
            'update_rule' => (string) $row->update_rule,
            'delete_rule' => (string) $row->delete_rule,
            'deferrable' => PostgreSqlBooleanConverter::toBool($row->condeferrable),
            'initially_deferred' => PostgreSqlBooleanConverter::toBool($row->condeferred),
        ];
    }

    /**
     * @return object{name: string, columns: list<string>, unique: bool, definition: string}|null
     */
    public function indexMetadata(string $table, string $index): ?object
    {
        $this->assertSafeIdentifier($table);
        $this->assertSafeIdentifier($index);
        $row = $this->ownerConnection->selectOne(
            'select idx.relname as index_name,
                    ind.indisunique,
                    (
                        select json_agg(att.attname order by key.ordinality)::text
                        from unnest(ind.indkey::smallint[]) with ordinality as key(attnum, ordinality)
                        join pg_attribute att
                          on att.attrelid = rel.oid
                         and att.attnum = key.attnum
                    ) as columns_json,
                    pg_get_indexdef(idx.oid) as definition
             from pg_index ind
             join pg_class rel on rel.oid = ind.indrelid
             join pg_namespace ns on ns.oid = rel.relnamespace
             join pg_class idx on idx.oid = ind.indexrelid
             where ns.nspname = ? and rel.relname = ? and idx.relname = ?',
            [PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $table, $index]
        );

        if ($row === null) {
            return null;
        }

        return (object) [
            'name' => (string) $row->index_name,
            'columns' => $this->decodeIdentifierList((string) $row->columns_json),
            'unique' => PostgreSqlBooleanConverter::toBool($row->indisunique),
            'definition' => (string) $row->definition,
        ];
    }

    public function identitySequence(string $table, string $column = 'id'): ?string
    {
        $this->assertSafeIdentifier($table);
        $this->assertSafeIdentifier($column);
        $row = $this->ownerConnection->selectOne(
            'select seq.relname as sequence_name
             from pg_class rel
             join pg_namespace ns on ns.oid = rel.relnamespace
             join pg_attribute att
               on att.attrelid = rel.oid
              and att.attname = ?
              and not att.attisdropped
             join pg_depend dep
               on dep.refobjid = rel.oid
              and dep.refobjsubid = att.attnum
              and dep.deptype in (\'a\', \'i\')
             join pg_class seq
               on seq.oid = dep.objid
              and seq.relkind = \'S\'
             where ns.nspname = ? and rel.relname = ?',
            [$column, PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $table]
        );

        return $row === null ? null : (string) $row->sequence_name;
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
        if (! in_array($privilege, ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'], true)) {
            throw new InvalidArgumentException('El privilegio de tabla solicitado no está permitido.');
        }
    }

    /**
     * @return list<string>
     */
    private function decodeIdentifierList(string $json): array
    {
        $identifiers = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($identifiers)) {
            throw new InvalidArgumentException('La metadata PostgreSQL no contiene una lista de identificadores válida.');
        }

        foreach ($identifiers as $identifier) {
            if (! is_string($identifier)) {
                throw new InvalidArgumentException('La metadata PostgreSQL contiene un identificador inválido.');
            }

            $this->assertSafeIdentifier($identifier);
        }

        return array_values($identifiers);
    }
}
