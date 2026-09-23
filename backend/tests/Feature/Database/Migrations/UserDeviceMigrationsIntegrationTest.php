<?php

namespace Tests\Feature\Database\Migrations;

use App\Infrastructure\Database\PostgreSqlBooleanConverter;
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
final class UserDeviceMigrationsIntegrationTest extends TestCase
{
    private const TABLES = ['roles', 'usuarios', 'dispositivos'];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000005_create_usuarios_table',
        '2026_09_16_000006_create_dispositivos_table',
    ];

    private const ROLE_CODE = 'prueba_usuario';

    private const USER_PUBLIC_ID = '11111111-1111-4111-8111-111111111111';

    private const SECOND_USER_PUBLIC_ID = '22222222-2222-4222-8222-222222222222';

    private const DEVICE_IDENTIFIER = 'dispositivo-ficticio-001';

    public function test_user_and_device_migrations_enforce_physical_contract_and_leave_no_residue(): void
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

                    $this->assertUsuariosContract($duringUp);
                    $this->assertDispositivosContract($duringUp);
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

    private function assertUsuariosContract(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTableOwner($inspection, 'usuarios');
        $columns = $inspection->columns('usuarios');

        $this->assertSame(
            [
                'id',
                'id_publico',
                'nombre',
                'nombre_usuario',
                'credencial_hash',
                'estado',
                'rol_id',
                'es_cobrador',
                'created_at',
                'updated_at',
            ],
            $this->columnNames($columns),
        );
        $this->assertIdentityColumn($columns, 'id');
        $this->assertColumn($columns, 'id_publico', 'uuid', 'uuid', null, false, null);
        $this->assertColumn($columns, 'nombre', 'character varying', 'varchar', 160, false, null);
        $this->assertColumn($columns, 'nombre_usuario', 'character varying', 'varchar', 64, false, null);
        $this->assertColumn($columns, 'credencial_hash', 'text', 'text', null, false, null);
        $this->assertColumn($columns, 'estado', 'character varying', 'varchar', 16, false, "'ACTIVO'::character varying");
        $this->assertColumn($columns, 'rol_id', 'bigint', 'int8', null, false, null);
        $this->assertColumn($columns, 'es_cobrador', 'boolean', 'bool', null, false, 'false');
        $this->assertTimestampColumn($columns, 'created_at');
        $this->assertTimestampColumn($columns, 'updated_at');

        $this->assertConstraint($inspection, 'usuarios', 'usuarios_pkey', 'PRIMARY KEY', ['id']);
        $this->assertConstraint($inspection, 'usuarios', 'uq_usuarios_id_publico', 'UNIQUE', ['id_publico']);
        $this->assertConstraint($inspection, 'usuarios', 'uq_usuarios_nombre_usuario', 'UNIQUE', ['nombre_usuario']);
        $estadoCheck = $this->assertConstraint($inspection, 'usuarios', 'chk_usuarios_estado', 'CHECK', ['estado']);
        $this->assertStringContainsString('ACTIVO', $estadoCheck->definition);
        $this->assertStringContainsString('BLOQUEADO', $estadoCheck->definition);
        $this->assertStringNotContainsString('INACTIVO', $estadoCheck->definition);
        $this->assertForeignKey(
            $inspection,
            'usuarios',
            'fk_usuarios_rol_id',
            ['rol_id'],
            'roles',
            ['id'],
        );
        $this->assertNonUniqueIndex($inspection, 'usuarios', 'idx_usuarios_rol_id', ['rol_id']);
        $this->assertPrivileges($inspection, 'usuarios');
        $this->assertIdentitySequenceHasNoAppUsage($inspection, 'usuarios');
    }

    private function assertDispositivosContract(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTableOwner($inspection, 'dispositivos');
        $columns = $inspection->columns('dispositivos');

        $this->assertSame(
            ['id', 'usuario_id', 'identificador_dispositivo', 'estado', 'vinculado_en'],
            $this->columnNames($columns),
        );
        $this->assertIdentityColumn($columns, 'id');
        $this->assertColumn($columns, 'usuario_id', 'bigint', 'int8', null, false, null);
        $this->assertColumn(
            $columns,
            'identificador_dispositivo',
            'character varying',
            'varchar',
            255,
            false,
            null,
        );
        $this->assertColumn($columns, 'estado', 'character varying', 'varchar', 16, false, "'ACTIVO'::character varying");
        $this->assertVinculadoEnColumn($columns);

        $this->assertConstraint($inspection, 'dispositivos', 'dispositivos_pkey', 'PRIMARY KEY', ['id']);
        $this->assertConstraint(
            $inspection,
            'dispositivos',
            'uq_dispositivos_identificador_dispositivo',
            'UNIQUE',
            ['identificador_dispositivo'],
        );
        $estadoCheck = $this->assertConstraint(
            $inspection,
            'dispositivos',
            'chk_dispositivos_estado',
            'CHECK',
            ['estado'],
        );
        $this->assertStringContainsString('ACTIVO', $estadoCheck->definition);
        $this->assertStringContainsString('REVOCADO', $estadoCheck->definition);
        $this->assertStringNotContainsString('BLOQUEADO', $estadoCheck->definition);
        $this->assertForeignKey(
            $inspection,
            'dispositivos',
            'fk_dispositivos_usuario_id',
            ['usuario_id'],
            'usuarios',
            ['id'],
        );
        $this->assertNonUniqueIndex($inspection, 'dispositivos', 'idx_dispositivos_usuario_id', ['usuario_id']);
        $this->assertPrivileges($inspection, 'dispositivos');
        $this->assertIdentitySequenceHasNoAppUsage($inspection, 'dispositivos');
    }

    /**
     * @param  list<object>  $columns
     */
    private function assertVinculadoEnColumn(array $columns): void
    {
        $column = $this->findColumn($columns, 'vinculado_en');

        $this->assertSame('timestamp with time zone', $column->data_type);
        $this->assertSame('timestamptz', $column->udt_name);
        $this->assertNull($column->character_maximum_length);
        $this->assertFalse($column->nullable);
        $this->assertNotNull($column->default);
        $this->assertSame('CURRENT_TIMESTAMP', $column->default);
        $this->assertFalse($column->identity);
        $this->assertNull($column->identity_generation);
    }

    private function assertFunctionalConstraints(Connection $owner): void
    {
        $timestamp = now()->utc();

        $owner->table('roles')->insert([
            'codigo' => self::ROLE_CODE,
            'nombre' => 'Rol de prueba usuarios',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $roleId = $owner->table('roles')->where('codigo', self::ROLE_CODE)->value('id');
        $this->assertNotNull($roleId);
        $roleId = (int) $roleId;
        $this->assertNotSame(0, $roleId);

        $firstUserId = (int) $owner->table('usuarios')->insertGetId([
            'id_publico' => self::USER_PUBLIC_ID,
            'nombre' => 'Usuario ficticio uno',
            'nombre_usuario' => 'usuario_ficticio_uno',
            'credencial_hash' => 'hash-ficticio-no-real',
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $firstUser = $owner->table('usuarios')->where('id', $firstUserId)->first();
        $this->assertNotNull($firstUser);
        $this->assertSame('ACTIVO', $firstUser->estado);
        $this->assertFalse(PostgreSqlBooleanConverter::toBool($firstUser->es_cobrador));

        $owner->table('usuarios')->insert([
            'id_publico' => self::SECOND_USER_PUBLIC_ID,
            'nombre' => 'Usuario ficticio dos',
            'nombre_usuario' => 'usuario_ficticio_dos',
            'credencial_hash' => 'hash-ficticio-no-real-dos',
            'estado' => 'BLOQUEADO',
            'rol_id' => $roleId,
            'es_cobrador' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('usuarios')->insert([
            'id_publico' => '33333333-3333-4333-8333-333333333333',
            'nombre' => 'Usuario estado inválido',
            'nombre_usuario' => 'usuario_estado_invalido',
            'credencial_hash' => 'hash-ficticio-no-real',
            'estado' => 'INACTIVO',
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('usuarios')->insert([
            'id_publico' => self::USER_PUBLIC_ID,
            'nombre' => 'Usuario uuid duplicado',
            'nombre_usuario' => 'usuario_uuid_duplicado',
            'credencial_hash' => 'hash-ficticio-no-real',
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('usuarios')->insert([
            'id_publico' => '44444444-4444-4444-8444-444444444444',
            'nombre' => 'Usuario login duplicado',
            'nombre_usuario' => 'usuario_ficticio_uno',
            'credencial_hash' => 'hash-ficticio-no-real',
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('usuarios')->insert([
            'id_publico' => '55555555-5555-4555-8555-555555555555',
            'nombre' => 'Usuario rol inexistente',
            'nombre_usuario' => 'usuario_rol_inexistente',
            'credencial_hash' => 'hash-ficticio-no-real',
            'rol_id' => PHP_INT_MAX,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]));

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->table('roles')->where('id', $roleId)->delete(),
        );

        $this->assertOwnerOperationFails($owner, function () use ($owner, $roleId, $timestamp): void {
            $owner->table('usuarios')->insert([
                'id' => 999999,
                'id_publico' => '66666666-6666-4666-8666-666666666666',
                'nombre' => 'Usuario identity manual',
                'nombre_usuario' => 'usuario_identity_manual',
                'credencial_hash' => 'hash-ficticio-no-real',
                'rol_id' => $roleId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }, 'identity');

        $deviceId = (int) $owner->table('dispositivos')->insertGetId([
            'usuario_id' => $firstUserId,
            'identificador_dispositivo' => self::DEVICE_IDENTIFIER,
        ]);

        $device = $owner->selectOne(
            'select estado, vinculado_en, pg_typeof(vinculado_en)::text as tipo_vinculado
             from dispositivos
             where id = ?',
            [$deviceId]
        );
        $this->assertNotNull($device);
        $this->assertSame('ACTIVO', $device->estado);
        $this->assertNotNull($device->vinculado_en);
        $this->assertSame('timestamp with time zone', $device->tipo_vinculado);

        $secondUserId = (int) $owner->table('usuarios')->where('nombre_usuario', 'usuario_ficticio_dos')->value('id');
        $this->assertNotSame(0, $secondUserId);

        $owner->table('dispositivos')->insert([
            'usuario_id' => $secondUserId,
            'identificador_dispositivo' => 'dispositivo-ficticio-002',
            'estado' => 'REVOCADO',
        ]);

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('dispositivos')->insert([
            'usuario_id' => $secondUserId,
            'identificador_dispositivo' => 'dispositivo-ficticio-003',
            'estado' => 'BLOQUEADO',
        ]));

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('dispositivos')->insert([
            'usuario_id' => $secondUserId,
            'identificador_dispositivo' => self::DEVICE_IDENTIFIER,
        ]));

        $this->assertOwnerOperationFails($owner, fn () => $owner->table('dispositivos')->insert([
            'usuario_id' => PHP_INT_MAX,
            'identificador_dispositivo' => 'dispositivo-ficticio-inexistente',
        ]));

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->table('usuarios')->where('id', $firstUserId)->delete(),
        );

        $this->assertOwnerOperationFails($owner, function () use ($owner, $firstUserId): void {
            $owner->table('dispositivos')->insert([
                'id' => 888888,
                'usuario_id' => $firstUserId,
                'identificador_dispositivo' => 'dispositivo-identity-manual',
            ]);
        }, 'identity');
    }

    private function assertOwnerOperationFails(
        Connection $owner,
        Closure $operation,
        ?string $expectedMessageFragment = null,
    ): void {
        $failed = false;
        $message = '';

        try {
            $owner->transaction(static function () use ($operation): void {
                $operation();
            });
        } catch (QueryException $exception) {
            $failed = true;
            $message = $exception->getMessage();
        }

        $this->assertTrue($failed, 'La operación owner debía ser rechazada por PostgreSQL.');
        $this->assertSame(1, $owner->transactionLevel());
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('usuarios'));
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('dispositivos'));

        if ($expectedMessageFragment !== null) {
            $this->assertStringContainsStringIgnoringCase($expectedMessageFragment, $message);
        }
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
        string $table,
        string $name,
        array $columns,
        string $referencedTable,
        array $referencedColumns,
    ): void {
        $foreignKey = $inspection->foreignKeyMetadata($table, $name);

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

    /**
     * @param  list<string>  $columns
     */
    private function assertNonUniqueIndex(
        OwnerAwareMigrationInspection $inspection,
        string $table,
        string $name,
        array $columns,
    ): void {
        $index = $inspection->indexMetadata($table, $name);

        $this->assertNotNull($index);
        $this->assertSame($name, $index->name);
        $this->assertSame($columns, $index->columns);
        $this->assertFalse($index->unique);
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
