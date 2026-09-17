<?php

namespace Tests\Feature\Database\Migrations;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Migrations\OwnerAwareMigrationInspection;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;
use Tests\TestCase;

/**
 * SERIAL ONLY: comparte credimex_test, el schema credimex y la tabla migrations.
 */
#[Group('owner-migrations-serial')]
final class RbacMigrationsIntegrationTest extends TestCase
{
    private const TABLES = ['roles', 'permisos', 'rol_permisos'];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000002_create_permisos_table',
        '2026_09_16_000003_create_rol_permisos_table',
    ];

    public function test_rbac_migrations_enforce_physical_contract_and_leave_no_residue(): void
    {
        $database = $this->app->make('db');
        $owner = $database->connection(OwnerAwareMigrationTestHarness::OWNER_CONNECTION);
        $inspection = new OwnerAwareMigrationInspection($owner);
        $initialDefaultConnection = $database->getDefaultConnection();
        $initialTransactionLevel = $owner->transactionLevel();
        $initialMigrations = $inspection->migrationBatches();
        $paths = array_map(
            static fn (string $migration): string => database_path("migrations/{$migration}.php"),
            self::MIGRATIONS,
        );

        foreach (self::TABLES as $table) {
            $this->assertFalse($inspection->tableExists($table));
        }

        foreach (self::MIGRATIONS as $migration) {
            $this->assertFalse($inspection->migrationIsRegistered($migration));
        }

        OwnerAwareMigrationTestHarness::fromApplication($this->app)
            ->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (OwnerAwareMigrationInspection $duringUp) use ($owner): void {
                    foreach (self::MIGRATIONS as $migration) {
                        $this->assertTrue($duringUp->migrationIsRegistered($migration));
                    }

                    $this->assertRolesContract($duringUp);
                    $this->assertPermisosContract($duringUp);
                    $this->assertRolPermisosContract($duringUp);
                    $this->assertFunctionalConstraints($owner);
                },
            );

        foreach (self::TABLES as $table) {
            $this->assertFalse($inspection->tableExists($table));
        }

        foreach (self::MIGRATIONS as $migration) {
            $this->assertFalse($inspection->migrationIsRegistered($migration));
        }

        $this->assertSame($initialMigrations, $inspection->migrationBatches());
        $this->assertSame($initialDefaultConnection, $database->getDefaultConnection());
        $this->assertSame($initialTransactionLevel, $owner->transactionLevel());

        foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $privilege) {
            $this->assertFalse($inspection->appHasTablePrivilege('migrations', $privilege));
        }

        $this->assertFalse($inspection->appHasSequencePrivilege('migrations_id_seq', 'USAGE'));
    }

    private function assertRolesContract(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTableOwner($inspection, 'roles');
        $columns = $inspection->columns('roles');

        $this->assertSame(['id', 'codigo', 'nombre', 'activo', 'created_at', 'updated_at'], $this->columnNames($columns));
        $this->assertIdentityColumn($columns, 'id');
        $this->assertColumn($columns, 'codigo', 'character varying', 'varchar', 64, false, null);
        $this->assertColumn($columns, 'nombre', 'character varying', 'varchar', 120, false, null);
        $this->assertColumn($columns, 'activo', 'boolean', 'bool', null, false, 'true');
        $this->assertTimestampColumn($columns, 'created_at');
        $this->assertTimestampColumn($columns, 'updated_at');

        $this->assertConstraint($inspection, 'roles', 'roles_pkey', 'PRIMARY KEY', ['id']);
        $this->assertConstraint($inspection, 'roles', 'uq_roles_codigo', 'UNIQUE', ['codigo']);
        $check = $this->assertConstraint($inspection, 'roles', 'chk_roles_codigo', 'CHECK', ['codigo']);
        $this->assertStringContainsString('^[a-z][a-z0-9_]*$', $check->definition);
        $this->assertPrivileges($inspection, 'roles');
        $this->assertIdentitySequenceHasNoAppUsage($inspection, 'roles');
    }

    private function assertPermisosContract(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTableOwner($inspection, 'permisos');
        $columns = $inspection->columns('permisos');

        $this->assertSame(
            ['id', 'codigo', 'modulo', 'descripcion', 'activo', 'created_at', 'updated_at'],
            $this->columnNames($columns),
        );
        $this->assertIdentityColumn($columns, 'id');
        $this->assertColumn($columns, 'codigo', 'character varying', 'varchar', 96, false, null);
        $this->assertColumn($columns, 'modulo', 'character varying', 'varchar', 64, false, null);
        $this->assertColumn($columns, 'descripcion', 'text', 'text', null, true, null);
        $this->assertColumn($columns, 'activo', 'boolean', 'bool', null, false, 'true');
        $this->assertTimestampColumn($columns, 'created_at');
        $this->assertTimestampColumn($columns, 'updated_at');

        $this->assertConstraint($inspection, 'permisos', 'permisos_pkey', 'PRIMARY KEY', ['id']);
        $this->assertConstraint($inspection, 'permisos', 'uq_permisos_codigo', 'UNIQUE', ['codigo']);
        $moduleCheck = $this->assertConstraint(
            $inspection,
            'permisos',
            'chk_permisos_modulo',
            'CHECK',
            ['modulo'],
        );
        $this->assertStringContainsString('^[a-z][a-z0-9_]*$', $moduleCheck->definition);
        $codeCheck = $this->assertConstraint(
            $inspection,
            'permisos',
            'chk_permisos_codigo',
            'CHECK',
            ['codigo'],
        );
        $this->assertStringContainsString('^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$', $codeCheck->definition);
        $this->assertPrivileges($inspection, 'permisos');
        $this->assertIdentitySequenceHasNoAppUsage($inspection, 'permisos');
    }

    private function assertRolPermisosContract(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTableOwner($inspection, 'rol_permisos');
        $columns = $inspection->columns('rol_permisos');

        $this->assertSame(['id', 'rol_id', 'permiso_id', 'created_at'], $this->columnNames($columns));
        $this->assertIdentityColumn($columns, 'id');
        $this->assertColumn($columns, 'rol_id', 'bigint', 'int8', null, false, null);
        $this->assertColumn($columns, 'permiso_id', 'bigint', 'int8', null, false, null);
        $this->assertTimestampColumn($columns, 'created_at');

        $this->assertConstraint($inspection, 'rol_permisos', 'rol_permisos_pkey', 'PRIMARY KEY', ['id']);
        $this->assertConstraint(
            $inspection,
            'rol_permisos',
            'uq_rol_permisos_rol_id_permiso_id',
            'UNIQUE',
            ['rol_id', 'permiso_id'],
        );
        $this->assertForeignKey(
            $inspection,
            'fk_rol_permisos_rol_id',
            ['rol_id'],
            'roles',
            ['id'],
        );
        $this->assertForeignKey(
            $inspection,
            'fk_rol_permisos_permiso_id',
            ['permiso_id'],
            'permisos',
            ['id'],
        );

        $index = $inspection->indexMetadata('rol_permisos', 'idx_rol_permisos_permiso_id');
        $this->assertNotNull($index);
        $this->assertSame('idx_rol_permisos_permiso_id', $index->name);
        $this->assertSame(['permiso_id'], $index->columns);
        $this->assertFalse($index->unique);

        $this->assertPrivileges($inspection, 'rol_permisos');
        $this->assertIdentitySequenceHasNoAppUsage($inspection, 'rol_permisos');
    }

    private function assertFunctionalConstraints(Connection $owner): void
    {
        $timestamp = now()->utc();
        $roleIds = [];

        foreach (['cobrador', 'cobranza_diaria'] as $codigo) {
            $roleIds[$codigo] = (int) $owner->table('roles')->insertGetId([
                'codigo' => $codigo,
                'nombre' => $codigo,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        foreach (['Administrador', '_cobrador', 'cobrador-activo', 'cobrador activo'] as $codigo) {
            $this->assertOwnerOperationFails($owner, fn () => $owner->table('roles')->insert([
                'codigo' => $codigo,
                'nombre' => 'inválido',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]));
        }

        $permissionIds = [];

        foreach ([
            ['codigo' => 'clientes.registrar', 'modulo' => 'clientes'],
            ['codigo' => 'pagos.reversar', 'modulo' => 'caja_diaria'],
        ] as $permission) {
            $permissionIds[$permission['codigo']] = (int) $owner->table('permisos')->insertGetId([
                ...$permission,
                'descripcion' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        foreach (['Clientes', '_clientes', 'clientes-activos', 'clientes activos'] as $position => $modulo) {
            $this->assertOwnerOperationFails($owner, fn () => $owner->table('permisos')->insert([
                'codigo' => "pruebas.modulo_invalido_{$position}",
                'modulo' => $modulo,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]));
        }

        foreach ([
            'clientesXregistrar',
            'clientes registrar',
            'clientes-registrar',
            '.clientes',
            'clientes.',
            'clientes..registrar',
            'clientes.registrar.extra',
            'Clientes.registrar',
            'clientes.Registrar',
        ] as $codigo) {
            $this->assertOwnerOperationFails($owner, fn () => $owner->table('permisos')->insert([
                'codigo' => $codigo,
                'modulo' => 'clientes',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]));
        }

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('roles')->insert([
            'codigo' => 'cobrador',
            'nombre' => 'Duplicado',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));
        $this->assertOwnerOperationFails($owner, fn () => $owner->table('permisos')->insert([
            'codigo' => 'clientes.registrar',
            'modulo' => 'clientes',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));

        $owner->table('rol_permisos')->insert([
            'rol_id' => $roleIds['cobrador'],
            'permiso_id' => $permissionIds['clientes.registrar'],
            'created_at' => $timestamp,
        ]);

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('rol_permisos')->insert([
            'rol_id' => $roleIds['cobrador'],
            'permiso_id' => $permissionIds['clientes.registrar'],
            'created_at' => $timestamp,
        ]));
        $this->assertOwnerOperationFails($owner, fn () => $owner->table('rol_permisos')->insert([
            'rol_id' => PHP_INT_MAX,
            'permiso_id' => $permissionIds['clientes.registrar'],
            'created_at' => $timestamp,
        ]));
        $this->assertOwnerOperationFails($owner, fn () => $owner->table('rol_permisos')->insert([
            'rol_id' => $roleIds['cobrador'],
            'permiso_id' => PHP_INT_MAX,
            'created_at' => $timestamp,
        ]));
        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->table('roles')->where('id', $roleIds['cobrador'])->delete(),
        );
        $this->assertOwnerOperationFails($owner, fn () => $owner->table('roles')->insert([
            'id' => 999999,
            'codigo' => 'identity_manual',
            'nombre' => 'Identity manual',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));
    }

    private function assertOwnerOperationFails(Connection $owner, Closure $operation): void
    {
        $failed = false;

        try {
            $owner->transaction(static function () use ($operation): void {
                $operation();
            });
        } catch (QueryException) {
            $failed = true;
        }

        $this->assertTrue($failed, 'La operación owner debía ser rechazada por PostgreSQL.');
        $this->assertSame(1, $owner->transactionLevel());
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('roles'));
    }

    private function assertTableOwner(OwnerAwareMigrationInspection $inspection, string $table): void
    {
        $this->assertTrue($inspection->tableExists($table));
        $metadata = $inspection->tableMetadata($table);
        $this->assertNotNull($metadata);
        $this->assertSame('credimex', $metadata->schema_name);
        $this->assertSame('credimex_test_owner', $metadata->owner_name);
    }

    /**
     * @param  list<object>  $columns
     * @return list<string>
     */
    private function columnNames(array $columns): array
    {
        return array_map(static fn (object $column): string => $column->name, $columns);
    }

    /**
     * @param  list<object>  $columns
     */
    private function assertIdentityColumn(array $columns, string $name): void
    {
        $column = $this->findColumn($columns, $name);

        $this->assertSame('bigint', $column->data_type);
        $this->assertSame('int8', $column->udt_name);
        $this->assertFalse($column->nullable);
        $this->assertNull($column->default);
        $this->assertTrue($column->identity);
        $this->assertSame('ALWAYS', $column->identity_generation);
    }

    /**
     * @param  list<object>  $columns
     */
    private function assertTimestampColumn(array $columns, string $name): void
    {
        $this->assertColumn($columns, $name, 'timestamp with time zone', 'timestamptz', null, false, null);
    }

    /**
     * @param  list<object>  $columns
     */
    private function assertColumn(
        array $columns,
        string $name,
        string $dataType,
        string $udtName,
        ?int $length,
        bool $nullable,
        ?string $default,
    ): void {
        $column = $this->findColumn($columns, $name);

        $this->assertSame($dataType, $column->data_type);
        $this->assertSame($udtName, $column->udt_name);
        $this->assertSame($length, $column->character_maximum_length);
        $this->assertSame($nullable, $column->nullable);
        $this->assertSame($default, $column->default);
        $this->assertFalse($column->identity);
        $this->assertNull($column->identity_generation);
    }

    /**
     * @param  list<object>  $columns
     */
    private function findColumn(array $columns, string $name): object
    {
        foreach ($columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        $this->fail("No existe la columna esperada {$name}.");
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertConstraint(
        OwnerAwareMigrationInspection $inspection,
        string $table,
        string $name,
        string $type,
        array $columns,
    ): object {
        $constraint = $inspection->constraintMetadata($table, $name);

        $this->assertNotNull($constraint);
        $this->assertSame($name, $constraint->name);
        $this->assertSame($type, $constraint->type);
        $this->assertSame($columns, $constraint->columns);

        return $constraint;
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $referencedColumns
     */
    private function assertForeignKey(
        OwnerAwareMigrationInspection $inspection,
        string $name,
        array $columns,
        string $referencedTable,
        array $referencedColumns,
    ): void {
        $foreignKey = $inspection->foreignKeyMetadata('rol_permisos', $name);

        $this->assertNotNull($foreignKey);
        $this->assertSame($name, $foreignKey->name);
        $this->assertSame($columns, $foreignKey->columns);
        $this->assertSame($referencedTable, $foreignKey->referenced_table);
        $this->assertSame($referencedColumns, $foreignKey->referenced_columns);
        $this->assertSame('NO ACTION', $foreignKey->update_rule);
        $this->assertSame('NO ACTION', $foreignKey->delete_rule);
        $this->assertFalse($foreignKey->deferrable);
        $this->assertFalse($foreignKey->initially_deferred);
    }

    private function assertPrivileges(OwnerAwareMigrationInspection $inspection, string $table): void
    {
        $this->assertTrue($inspection->appHasTablePrivilege($table, 'SELECT'));

        foreach (['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $privilege) {
            $this->assertFalse($inspection->appHasTablePrivilege($table, $privilege));
        }
    }

    private function assertIdentitySequenceHasNoAppUsage(
        OwnerAwareMigrationInspection $inspection,
        string $table,
    ): void {
        $sequence = $inspection->identitySequence($table);

        $this->assertNotNull($sequence);
        $this->assertFalse($inspection->appHasSequencePrivilege($sequence, 'USAGE'));
    }
}
