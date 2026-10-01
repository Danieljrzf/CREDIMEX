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
final class SessionTokenMigrationIntegrationTest extends TestCase
{
    private const TABLES = ['roles', 'usuarios', 'dispositivos', 'sesiones_token'];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000005_create_usuarios_table',
        '2026_09_16_000006_create_dispositivos_table',
        '2026_09_16_000007_create_sesiones_token_table',
    ];

    private const ROLE_CODE = 'prueba_sesion';

    private const USER_PUBLIC_ID = '12121212-1212-4121-8121-121212121212';

    private const USER_WITHOUT_DEVICE_PUBLIC_ID = '13131313-1313-4131-8131-131313131313';

    private const DEVICE_IDENTIFIER = 'dispositivo-sesion-ficticio-001';

    private const EXPIRES_AT = '2031-06-15 18:45:00+00';

    private const CREDENTIAL_HASH = 'credencial-ficticia-no-es-secreto-real';

    private const SESSION_CONSTRAINTS = [
        'uq_sesiones_token_token_hash',
        'chk_sesiones_token_token_hash_length',
        'chk_sesiones_token_estado',
        'fk_sesiones_token_usuario_id',
        'fk_sesiones_token_dispositivo_id',
        'sesiones_token_pkey',
    ];

    public function test_session_token_migration_enforces_physical_contract_and_leaves_no_residue(): void
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

        $this->assertSanctumArtifactsAreAbsent($inspection);

        OwnerAwareMigrationTestHarness::fromApplication($this->app)
            ->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (OwnerAwareMigrationInspection $duringUp) use ($owner): void {
                    foreach (self::MIGRATIONS as $migration) {
                        $this->assertTrue($duringUp->migrationIsRegistered($migration));
                    }

                    $this->assertSesionesTokenContract($duringUp);
                    $this->assertFunctionalConstraints($owner);
                    $this->assertSanctumArtifactsAreAbsent($duringUp);
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
        $this->assertSame(0, $owner->transactionLevel());

        foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $privilege) {
            $this->assertFalse($inspection->appHasTablePrivilege('migrations', $privilege));
        }

        $this->assertFalse($inspection->appHasSequencePrivilege('migrations_id_seq', 'USAGE'));
        $this->assertSanctumArtifactsAreAbsent($inspection);
    }

    private function assertSesionesTokenContract(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTableOwner($inspection, 'sesiones_token');
        $columns = $inspection->columns('sesiones_token');

        $this->assertSame(
            ['id', 'usuario_id', 'dispositivo_id', 'token_hash', 'expira_en', 'estado'],
            $this->columnNames($columns),
        );
        $this->assertIdentityColumn($columns, 'id');
        $this->assertColumn($columns, 'usuario_id', 'bigint', 'int8', null, false, null);
        $this->assertColumn($columns, 'dispositivo_id', 'bigint', 'int8', null, true, null);
        $this->assertColumn($columns, 'token_hash', 'bytea', 'bytea', null, false, null);
        $this->assertColumn($columns, 'expira_en', 'timestamp with time zone', 'timestamptz', null, false, null);
        $this->assertColumn($columns, 'estado', 'character varying', 'varchar', 16, false, "'VIGENTE'::character varying");

        $this->assertConstraint($inspection, 'sesiones_token', 'sesiones_token_pkey', 'PRIMARY KEY', ['id']);
        $this->assertConstraint(
            $inspection,
            'sesiones_token',
            'uq_sesiones_token_token_hash',
            'UNIQUE',
            ['token_hash'],
        );
        $hashCheck = $this->assertConstraint(
            $inspection,
            'sesiones_token',
            'chk_sesiones_token_token_hash_length',
            'CHECK',
            ['token_hash'],
        );
        $this->assertStringContainsString('octet_length', $hashCheck->definition);
        $this->assertStringContainsString('32', $hashCheck->definition);
        $estadoCheck = $this->assertConstraint(
            $inspection,
            'sesiones_token',
            'chk_sesiones_token_estado',
            'CHECK',
            ['estado'],
        );
        $this->assertStringContainsString('VIGENTE', $estadoCheck->definition);
        $this->assertStringContainsString('REVOCADA', $estadoCheck->definition);
        $this->assertStringNotContainsString('EXPIRADA', $estadoCheck->definition);
        $this->assertForeignKey(
            $inspection,
            'sesiones_token',
            'fk_sesiones_token_usuario_id',
            ['usuario_id'],
            'usuarios',
            ['id'],
        );
        $this->assertForeignKey(
            $inspection,
            'sesiones_token',
            'fk_sesiones_token_dispositivo_id',
            ['dispositivo_id'],
            'dispositivos',
            ['id'],
        );
        $this->assertNonUniqueIndex($inspection, 'sesiones_token', 'idx_sesiones_token_usuario_id', ['usuario_id']);
        $this->assertNonUniqueIndex(
            $inspection,
            'sesiones_token',
            'idx_sesiones_token_dispositivo_id',
            ['dispositivo_id'],
        );
        $this->assertPrivileges($inspection, 'sesiones_token');
        $this->assertIdentitySequenceHasNoAppUsage($inspection, 'sesiones_token');
    }

    private function assertFunctionalConstraints(Connection $owner): void
    {
        $timestamp = now()->utc();

        $owner->table('roles')->insert([
            'codigo' => self::ROLE_CODE,
            'nombre' => 'Rol de prueba sesiones',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $roleId = $owner->table('roles')->where('codigo', self::ROLE_CODE)->value('id');
        $this->assertNotNull($roleId);
        $roleId = (int) $roleId;
        $this->assertNotSame(0, $roleId);

        $owner->table('usuarios')->insert([
            'id_publico' => self::USER_PUBLIC_ID,
            'nombre' => 'Usuario ficticio sesion',
            'nombre_usuario' => 'usuario_sesion_ficticio',
            'credencial_hash' => self::CREDENTIAL_HASH,
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $userId = $owner->table('usuarios')->where('id_publico', self::USER_PUBLIC_ID)->value('id');
        $this->assertNotNull($userId);
        $userId = (int) $userId;
        $this->assertNotSame(0, $userId);

        $owner->table('usuarios')->insert([
            'id_publico' => self::USER_WITHOUT_DEVICE_PUBLIC_ID,
            'nombre' => 'Usuario ficticio sin dispositivo',
            'nombre_usuario' => 'usuario_sesion_sin_dispositivo',
            'credencial_hash' => self::CREDENTIAL_HASH,
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $userWithoutDeviceId = $owner->table('usuarios')
            ->where('id_publico', self::USER_WITHOUT_DEVICE_PUBLIC_ID)
            ->value('id');
        $this->assertNotNull($userWithoutDeviceId);
        $userWithoutDeviceId = (int) $userWithoutDeviceId;
        $this->assertNotSame($userId, $userWithoutDeviceId);

        $owner->table('dispositivos')->insert([
            'usuario_id' => $userId,
            'identificador_dispositivo' => self::DEVICE_IDENTIFIER,
        ]);

        $deviceId = $owner->table('dispositivos')
            ->where('identificador_dispositivo', self::DEVICE_IDENTIFIER)
            ->value('id');
        $this->assertNotNull($deviceId);
        $deviceId = (int) $deviceId;
        $this->assertNotSame(0, $deviceId);

        $hashWithDevice = $this->tokenHash('token-ficticio-con-dispositivo');
        $sessionWithDeviceId = $this->insertSession($owner, $userId, $deviceId, $hashWithDevice, 'VIGENTE');
        $this->assertStoredTokenHash($owner, $sessionWithDeviceId, $hashWithDevice);

        $hashWithoutDevice = $this->tokenHash('token-ficticio-sin-dispositivo');
        $sessionWithoutDeviceId = $this->insertSession($owner, $userId, null, $hashWithoutDevice, 'VIGENTE');
        $storedWithoutDevice = $owner->selectOne(
            'select dispositivo_id from sesiones_token where id = ?',
            [$sessionWithoutDeviceId],
        );
        $this->assertNotNull($storedWithoutDevice);
        $this->assertNull($storedWithoutDevice->dispositivo_id);

        $hashRevoked = $this->tokenHash('token-ficticio-revocada');
        $revokedId = $this->insertSession($owner, $userId, null, $hashRevoked, 'REVOCADA');
        $revoked = $owner->selectOne('select estado from sesiones_token where id = ?', [$revokedId]);
        $this->assertNotNull($revoked);
        $this->assertSame('REVOCADA', $revoked->estado);

        $hashDefaultState = $this->tokenHash('token-ficticio-estado-por-defecto');
        $defaultState = $owner->selectOne(
            'insert into sesiones_token (usuario_id, dispositivo_id, token_hash, expira_en)
             values (?, null, decode(?, \'hex\'), ?::timestamptz)
             returning estado',
            [$userId, bin2hex($hashDefaultState), self::EXPIRES_AT],
        );
        $this->assertNotNull($defaultState);
        $this->assertSame('VIGENTE', $defaultState->estado);

        $hashForUserWithoutDevice = $this->tokenHash('token-ficticio-usuario-sin-dispositivo');
        $this->insertSession($owner, $userWithoutDeviceId, null, $hashForUserWithoutDevice, 'VIGENTE');

        $duplicateHash = $this->tokenHash('token-ficticio-unico');
        $this->insertSession($owner, $userId, $deviceId, $duplicateHash, 'VIGENTE');
        $this->assertOwnerOperationFails(
            $owner,
            fn () => $this->insertSession($owner, $userId, $deviceId, $duplicateHash, 'VIGENTE'),
            'uq_sesiones_token_token_hash',
        );

        $shortHash = substr($this->tokenHash('token-ficticio-longitud-corta'), 0, 31);
        $this->assertSame(31, strlen($shortHash));
        $this->assertOwnerOperationFails(
            $owner,
            fn () => $this->insertSession($owner, $userId, null, $shortHash, 'VIGENTE'),
            'chk_sesiones_token_token_hash_length',
        );

        $longHash = $this->tokenHash('token-ficticio-longitud-larga')."\x00";
        $this->assertSame(33, strlen($longHash));
        $this->assertOwnerOperationFails(
            $owner,
            fn () => $this->insertSession($owner, $userId, null, $longHash, 'VIGENTE'),
            'chk_sesiones_token_token_hash_length',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $this->insertSession(
                $owner,
                $userId,
                null,
                $this->tokenHash('token-ficticio-estado-invalido'),
                'INACTIVA',
            ),
            'chk_sesiones_token_estado',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $this->insertSession(
                $owner,
                PHP_INT_MAX,
                null,
                $this->tokenHash('token-ficticio-usuario-inexistente'),
                'VIGENTE',
            ),
            'fk_sesiones_token_usuario_id',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $this->insertSession(
                $owner,
                $userId,
                PHP_INT_MAX,
                $this->tokenHash('token-ficticio-dispositivo-inexistente'),
                'VIGENTE',
            ),
            'fk_sesiones_token_dispositivo_id',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->selectOne(
                'insert into sesiones_token (usuario_id, dispositivo_id, token_hash, estado)
                 values (?, null, decode(?, \'hex\'), ?)
                 returning id',
                [
                    $userId,
                    bin2hex($this->tokenHash('token-ficticio-sin-expiracion')),
                    'VIGENTE',
                ],
            ),
            'expira_en',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->table('usuarios')->where('id', $userWithoutDeviceId)->delete(),
            'fk_sesiones_token_usuario_id',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->table('dispositivos')->where('id', $deviceId)->delete(),
            'fk_sesiones_token_dispositivo_id',
        );

        $this->assertOwnerOperationFails(
            $owner,
            fn () => $owner->selectOne(
                'insert into sesiones_token (id, usuario_id, dispositivo_id, token_hash, expira_en, estado)
                 values (?, ?, ?, decode(?, \'hex\'), ?::timestamptz, ?)
                 returning id',
                [
                    999999,
                    $userId,
                    $deviceId,
                    bin2hex($this->tokenHash('token-ficticio-identity-manual')),
                    self::EXPIRES_AT,
                    'VIGENTE',
                ],
            ),
            'GENERATED ALWAYS',
        );
    }

    private function tokenHash(string $fixture): string
    {
        $hash = hash('sha256', $fixture, true);
        $this->assertSame(32, strlen($hash));

        return $hash;
    }

    private function insertSession(
        Connection $owner,
        int $usuarioId,
        ?int $dispositivoId,
        string $tokenHash,
        string $estado,
    ): int {
        $row = $owner->selectOne(
            'insert into sesiones_token (usuario_id, dispositivo_id, token_hash, expira_en, estado)
             values (?, ?, decode(?, \'hex\'), ?::timestamptz, ?)
             returning id',
            [$usuarioId, $dispositivoId, bin2hex($tokenHash), self::EXPIRES_AT, $estado],
        );
        $this->assertNotNull($row);

        return (int) $row->id;
    }

    private function assertStoredTokenHash(Connection $owner, int $sessionId, string $tokenHash): void
    {
        $stored = $owner->selectOne(
            'select octet_length(token_hash) as longitud, encode(token_hash, \'hex\') as hash_hex
             from sesiones_token
             where id = ?',
            [$sessionId],
        );

        $this->assertNotNull($stored);
        $this->assertSame(32, (int) $stored->longitud);
        $this->assertSame(bin2hex($tokenHash), $stored->hash_hex);
    }

    private function assertOwnerOperationFails(
        Connection $owner,
        Closure $operation,
        string $expectedConstraint,
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
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('roles'));
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('usuarios'));
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('dispositivos'));
        $this->assertTrue($owner->getSchemaBuilder()->hasTable('sesiones_token'));
        $this->assertStringContainsStringIgnoringCase($expectedConstraint, $message);

        $presentConstraints = array_values(array_filter(
            self::SESSION_CONSTRAINTS,
            static fn (string $constraint): bool => str_contains($message, $constraint),
        ));

        if (in_array($expectedConstraint, self::SESSION_CONSTRAINTS, true)) {
            $this->assertSame([$expectedConstraint], $presentConstraints);
        } else {
            $this->assertSame([], $presentConstraints);
        }
    }

    private function assertSanctumArtifactsAreAbsent(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertFalse($inspection->tableExists('personal_access_tokens'));
        $this->assertFileDoesNotExist(config_path('sanctum.php'));
        $this->assertFalse(class_exists('Laravel\\Sanctum\\Sanctum'));
        $this->assertFalse(trait_exists('Laravel\\Sanctum\\HasApiTokens'));

        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
        $this->assertIsArray($composer);
        $this->assertArrayNotHasKey('laravel/sanctum', $composer['require'] ?? []);
        $this->assertArrayNotHasKey('laravel/sanctum', $composer['require-dev'] ?? []);
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
