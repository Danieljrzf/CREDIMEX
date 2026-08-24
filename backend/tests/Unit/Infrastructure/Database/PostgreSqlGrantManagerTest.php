<?php

namespace Tests\Unit\Infrastructure\Database;

use App\Infrastructure\Database\PostgreSqlConnectionSnapshot;
use App\Infrastructure\Database\PostgreSqlGrantContextInspector;
use App\Infrastructure\Database\PostgreSqlGrantContextParts;
use App\Infrastructure\Database\PostgreSqlGrantException;
use App\Infrastructure\Database\PostgreSqlGrantManager;
use App\Infrastructure\Database\PostgreSqlGrantSqlExecutor;
use App\Infrastructure\Database\PostgreSqlIdentifierQuoter;
use App\Infrastructure\Database\PostgreSqlRoleSnapshot;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class PostgreSqlGrantManagerTest extends TestCase
{
    /** @var list<string> */
    private array $executedSql = [];

    private int $inspectorCalls = 0;

    private int $quoterCalls = 0;

    private int $executorCalls = 0;

    public function test_rejects_empty_app_role(): void
    {
        $manager = $this->makeManager(configuredAppRole: null);

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        $manager->assertSafeContext();
    }

    public function test_rejects_blank_app_role(): void
    {
        $manager = $this->makeManager(configuredAppRole: '   ');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        $manager->assertSafeContext();
    }

    public function test_rejects_invalid_app_role_format(): void
    {
        $manager = $this->makeManager(configuredAppRole: 'Credimex-App');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('formato inválido');

        $manager->assertSafeContext();
    }

    public function test_rejects_disallowed_environment(): void
    {
        $manager = $this->makeManager(environment: 'production');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('no está permitido');

        $manager->assertSafeContext();
    }

    public function test_rejects_incomplete_environment_catalog_entry(): void
    {
        $manager = $this->makeManager(
            environmentCatalog: [
                'testing' => [
                    'database' => 'credimex_test',
                    'owner_user' => 'credimex_test_owner',
                ],
            ],
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('configuración del entorno de privilegios es incompleta');

        $manager->assertSafeContext();
    }

    public function test_rejects_test_role_in_local_environment(): void
    {
        $manager = $this->makeManager(
            environment: 'local',
            configuredAppRole: 'credimex_test_app',
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('cruce entre desarrollo y testing');

        $manager->assertSafeContext();
    }

    public function test_rejects_dev_role_in_testing_environment(): void
    {
        $manager = $this->makeManager(
            environment: 'testing',
            configuredAppRole: 'credimex_app',
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('cruce entre desarrollo y testing');

        $manager->assertSafeContext();
    }

    public function test_rejects_default_connection_other_than_pgsql(): void
    {
        $manager = $this->makeManager(defaultConnection: 'sqlite');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('conexión default debe ser exactamente "pgsql"');

        $manager->assertSafeContext();
    }

    public function test_owner_migration_factory_requires_owner_as_runtime_default(): void
    {
        [$app, $database] = $this->makeOwnerMigrationApplication(runtimeDefault: 'pgsql');

        try {
            PostgreSqlGrantManager::fromOwnerMigration($app);
            $this->fail('El contexto ordinario no debe aceptarse como migración owner.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('pgsql_owner como conexión default temporal', $exception->getMessage());
            $this->assertSame('pgsql', $database->getDefaultConnection());
        }
    }

    public function test_owner_migration_factory_requires_active_owner_transaction(): void
    {
        [$app, $database] = $this->makeOwnerMigrationApplication(transactionLevel: 0);

        try {
            PostgreSqlGrantManager::fromOwnerMigration($app);
            $this->fail('El contexto owner sin transacción no debe aceptarse.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('transacción activa', $exception->getMessage());
            $this->assertSame(PostgreSqlGrantManager::OWNER_CONNECTION, $database->getDefaultConnection());
        }
    }

    public function test_owner_migration_factory_restores_runtime_default_after_construction(): void
    {
        [$app, $database] = $this->makeOwnerMigrationApplication();

        $manager = PostgreSqlGrantManager::fromOwnerMigration($app);

        $this->assertInstanceOf(PostgreSqlGrantManager::class, $manager);
        $this->assertSame(PostgreSqlGrantManager::OWNER_CONNECTION, $database->getDefaultConnection());
    }

    public function test_owner_migration_factory_restores_runtime_default_after_failure(): void
    {
        [$app, $database] = $this->makeOwnerMigrationApplication(failConfigResolution: true);

        try {
            PostgreSqlGrantManager::fromOwnerMigration($app);
            $this->fail('La preparación del helper debía fallar.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('preparar el helper', $exception->getMessage());
            $this->assertStringNotContainsString('detalle sensible', $exception->getMessage());
            $this->assertSame(PostgreSqlGrantManager::OWNER_CONNECTION, $database->getDefaultConnection());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_rejects_pgsql_driver_other_than_pgsql(): void
    {
        $manager = $this->makeManager(pgsqlDriver: 'mysql');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('conexión pgsql debe usar el driver "pgsql"');

        $manager->assertSafeContext();
    }

    public function test_rejects_pgsql_owner_driver_other_than_pgsql(): void
    {
        $manager = $this->makeManager(pgsqlOwnerDriver: 'sqlite');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('conexión pgsql_owner debe usar el driver "pgsql"');

        $manager->assertSafeContext();
    }

    public function test_rejects_configured_schema_other_than_credimex(): void
    {
        $manager = $this->makeManager(schema: 'public');

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('esquema configurado debe ser exactamente "credimex"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_app_database(): void
    {
        $manager = $this->makeManager(
            appSnapshot: $this->snapshot(database: 'credimex_dev'),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('base "credimex_test"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_app_user(): void
    {
        $manager = $this->makeManager(
            appSnapshot: $this->snapshot(user: 'credimex_app'),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('usuario "credimex_test_app"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_owner_database(): void
    {
        $manager = $this->makeManager(
            ownerSnapshot: $this->snapshot(
                database: 'postgres',
                user: 'credimex_test_owner',
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('base "credimex_test"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_owner_user(): void
    {
        $manager = $this->makeManager(
            ownerSnapshot: $this->snapshot(user: 'credimex_owner'),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('usuario "credimex_test_owner"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_app_schema(): void
    {
        $manager = $this->makeManager(
            appSnapshot: $this->snapshot(schema: 'public'),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('esquema "credimex"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_app_search_path(): void
    {
        $manager = $this->makeManager(
            appSnapshot: $this->snapshot(searchPath: 'credimex,public'),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('search_path "credimex"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_app_schemas_text(): void
    {
        $manager = $this->makeManager(
            appSnapshot: $this->snapshot(schemasText: '{public}'),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('current_schemas(false)');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_owner_schema(): void
    {
        $manager = $this->makeManager(
            ownerSnapshot: $this->snapshot(
                user: 'credimex_test_owner',
                schema: 'public',
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('esquema "credimex"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_owner_search_path(): void
    {
        $manager = $this->makeManager(
            ownerSnapshot: $this->snapshot(
                user: 'credimex_test_owner',
                searchPath: 'public',
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('search_path "credimex"');

        $manager->assertSafeContext();
    }

    public function test_rejects_incorrect_owner_schemas_text(): void
    {
        $manager = $this->makeManager(
            ownerSnapshot: $this->snapshot(
                user: 'credimex_test_owner',
                schemasText: '{credimex,public}',
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('current_schemas(false)');

        $manager->assertSafeContext();
    }

    public function test_rejects_nonexistent_role(): void
    {
        $manager = $this->makeManager(
            role: new PostgreSqlRoleSnapshot(
                name: 'credimex_test_app',
                exists: false,
                canLogin: false,
                hasConnect: false,
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('no existe');

        $manager->assertSafeContext();
    }

    public function test_rejects_role_without_login(): void
    {
        $manager = $this->makeManager(
            role: new PostgreSqlRoleSnapshot(
                name: 'credimex_test_app',
                exists: true,
                canLogin: false,
                hasConnect: true,
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('LOGIN');

        $manager->assertSafeContext();
    }

    public function test_rejects_role_without_connect(): void
    {
        $manager = $this->makeManager(
            role: new PostgreSqlRoleSnapshot(
                name: 'credimex_test_app',
                exists: true,
                canLogin: true,
                hasConnect: false,
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('CONNECT');

        $manager->assertSafeContext();
    }

    public function test_rejects_inspected_role_name_different_from_configured_app_role(): void
    {
        $manager = $this->makeManager(
            role: new PostgreSqlRoleSnapshot(
                name: 'other_role',
                exists: true,
                canLogin: true,
                hasConnect: true,
            ),
        );

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('no coincide con el rol de aplicación configurado');

        $manager->assertSafeContext();
    }

    public function test_rejects_object_name_with_dot(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('identificador simple');

        $manager->grantTable('credimex.roles', ['SELECT']);
    }

    public function test_rejects_object_name_with_quotes(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('identificador simple');

        $manager->grantTable('rol"es', ['SELECT']);
    }

    public function test_rejects_object_name_with_spaces(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('identificador simple');

        $manager->grantTable('my table', ['SELECT']);
    }

    public function test_rejects_object_name_with_uppercase(): void
    {
        $calls = $this->makeCountingDependencies();
        $manager = $this->makeManager(
            inspector: $calls['inspector'],
            quoter: $calls['quoter'],
            executor: $calls['executor'],
        );

        try {
            $manager->grantTable('Roles', ['SELECT']);
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('minúsculas', $exception->getMessage());
            $this->assertSame(0, $this->inspectorCalls);
            $this->assertSame(0, $this->quoterCalls);
            $this->assertSame(0, $this->executorCalls);
        }
    }

    public function test_rejects_migrations_table_before_inspecting_postgresql(): void
    {
        $calls = $this->makeCountingDependencies();
        $manager = $this->makeManager(
            inspector: $calls['inspector'],
            quoter: $calls['quoter'],
            executor: $calls['executor'],
        );

        try {
            $manager->grantTable('migrations', ['SELECT']);
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('objeto protegido [migrations]', $exception->getMessage());
            $this->assertSame(0, $this->inspectorCalls);
            $this->assertSame(0, $this->quoterCalls);
            $this->assertSame(0, $this->executorCalls);
        }
    }

    public function test_rejects_migrations_id_seq_before_inspecting_postgresql(): void
    {
        $calls = $this->makeCountingDependencies();
        $manager = $this->makeManager(
            inspector: $calls['inspector'],
            quoter: $calls['quoter'],
            executor: $calls['executor'],
        );

        try {
            $manager->grantSequence('migrations_id_seq', ['USAGE']);
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('objeto protegido [migrations_id_seq]', $exception->getMessage());
            $this->assertSame(0, $this->inspectorCalls);
            $this->assertSame(0, $this->quoterCalls);
            $this->assertSame(0, $this->executorCalls);
        }
    }

    public function test_rejects_invalid_privilege_before_inspecting_postgresql(): void
    {
        $calls = $this->makeCountingDependencies();
        $manager = $this->makeManager(
            inspector: $calls['inspector'],
            quoter: $calls['quoter'],
            executor: $calls['executor'],
        );

        try {
            $manager->grantTable('roles', ['TRUNCATE']);
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertStringContainsString('Privilegio no permitido', $exception->getMessage());
            $this->assertSame(0, $this->inspectorCalls);
            $this->assertSame(0, $this->quoterCalls);
            $this->assertSame(0, $this->executorCalls);
        }
    }

    public function test_rejects_empty_privilege_list(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('no puede estar vacía');

        $manager->grantTable('roles', []);
    }

    public function test_rejects_unknown_privilege(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('Privilegio no permitido');

        $manager->grantTable('roles', ['TRUNCATE']);
    }

    public function test_rejects_all_privilege(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('ALL');

        $manager->grantTable('roles', ['ALL']);
    }

    public function test_rejects_combined_privilege_string(): void
    {
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('privilegio simple');

        $manager->grantTable('roles', ['SELECT,INSERT']);
    }

    public function test_normalizes_duplicate_privileges_and_deterministic_order(): void
    {
        $manager = $this->makeValidManager();

        $manager->grantTable('roles', ['update', 'SELECT', 'select', 'INSERT']);

        $this->assertSame(
            ['GRANT INSERT, SELECT, UPDATE ON TABLE "credimex"."roles" TO "credimex_test_app"'],
            $this->executedSql
        );
    }

    public function test_table_accepts_only_select_insert_update_delete(): void
    {
        $manager = $this->makeValidManager();

        $manager->grantTable('roles', ['SELECT', 'INSERT', 'UPDATE', 'DELETE']);

        $this->assertSame(
            ['GRANT DELETE, INSERT, SELECT, UPDATE ON TABLE "credimex"."roles" TO "credimex_test_app"'],
            $this->executedSql
        );

        $this->executedSql = [];
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('Privilegio no permitido');

        $manager->grantTable('roles', ['USAGE']);
    }

    public function test_sequence_accepts_only_usage(): void
    {
        $manager = $this->makeValidManager();

        $manager->grantSequence('roles_id_seq', ['USAGE']);

        $this->assertSame(
            ['GRANT USAGE ON SEQUENCE "credimex"."roles_id_seq" TO "credimex_test_app"'],
            $this->executedSql
        );

        $this->executedSql = [];
        $manager = $this->makeValidManager();

        $this->expectException(PostgreSqlGrantException::class);
        $this->expectExceptionMessage('Privilegio no permitido');

        $manager->grantSequence('roles_id_seq', ['SELECT']);
    }

    public function test_grant_uses_to_and_revoke_uses_from(): void
    {
        $manager = $this->makeValidManager();

        $manager->grantTable('roles', ['SELECT']);
        $manager->revokeTable('roles', ['SELECT']);
        $manager->grantSequence('roles_id_seq', ['USAGE']);
        $manager->revokeSequence('roles_id_seq', ['USAGE']);

        $this->assertSame([
            'GRANT SELECT ON TABLE "credimex"."roles" TO "credimex_test_app"',
            'REVOKE SELECT ON TABLE "credimex"."roles" FROM "credimex_test_app"',
            'GRANT USAGE ON SEQUENCE "credimex"."roles_id_seq" TO "credimex_test_app"',
            'REVOKE USAGE ON SEQUENCE "credimex"."roles_id_seq" FROM "credimex_test_app"',
        ], $this->executedSql);
    }

    public function test_sanitizes_inspector_postgresql_grant_exception(): void
    {
        $sensitive = 'password=SecretPass DSN=pgsql:host=10.1.2.3 SQLSTATE[08006] SQL: select 1 host=db.internal';

        $inspector = new class($sensitive) implements PostgreSqlGrantContextInspector
        {
            public function __construct(private readonly string $sensitive)
            {
            }

            public function inspect(string $configuredAppRole): PostgreSqlGrantContextParts
            {
                throw new PostgreSqlGrantException($this->sensitive);
            }
        };

        $manager = $this->makeManager(inspector: $inspector);

        try {
            $manager->assertSafeContext();
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertSame(
                'No fue posible validar el contexto PostgreSQL para privilegios.',
                $exception->getMessage()
            );
            $this->assertSensitiveDataAbsent($exception, 'SecretPass');
        } catch (Throwable $exception) {
            $this->fail('Excepción inesperada: '.$exception::class);
        }
    }

    public function test_sanitizes_quoter_postgresql_grant_exception(): void
    {
        $sensitive = 'password=QuoteSecret DSN=pgsql:host=10.9.8.7 SQLSTATE[42000] SQL: select quote_ident host=quote.internal';

        $quoter = new class($sensitive) implements PostgreSqlIdentifierQuoter
        {
            public function __construct(private readonly string $sensitive)
            {
            }

            public function quoteIdent(string $identifier): string
            {
                throw new PostgreSqlGrantException($this->sensitive);
            }
        };

        $manager = $this->makeManager(quoter: $quoter);

        try {
            $manager->grantTable('roles', ['SELECT']);
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertSame(
                'No fue posible delimitar identificadores para la operación GRANT sobre TABLE.',
                $exception->getMessage()
            );
            $this->assertSensitiveDataAbsent($exception, 'QuoteSecret');
        } catch (Throwable $exception) {
            $this->fail('Excepción inesperada: '.$exception::class);
        }
    }

    public function test_sanitizes_executor_postgresql_grant_exception(): void
    {
        $sensitive = 'password=ExecSecret host=db.internal SQLSTATE[42501] SQL: GRANT ALL';

        $executor = new class($sensitive) implements PostgreSqlGrantSqlExecutor
        {
            public function __construct(private readonly string $sensitive)
            {
            }

            public function execute(string $sql): void
            {
                throw new PostgreSqlGrantException($this->sensitive);
            }
        };

        $manager = $this->makeManager(executor: $executor);

        try {
            $manager->grantTable('roles', ['SELECT']);
            $this->fail('Se esperaba PostgreSqlGrantException.');
        } catch (PostgreSqlGrantException $exception) {
            $this->assertSame(
                'No fue posible ejecutar GRANT sobre TABLE mediante pgsql_owner.',
                $exception->getMessage()
            );
            $this->assertSensitiveDataAbsent($exception, 'ExecSecret');
        } catch (Throwable $exception) {
            $this->fail('Excepción inesperada: '.$exception::class);
        }
    }

    private function assertSensitiveDataAbsent(PostgreSqlGrantException $exception, string $secret): void
    {
        $message = $exception->getMessage();

        $this->assertStringNotContainsString($secret, $message);
        $this->assertStringNotContainsString('password=', $message);
        $this->assertStringNotContainsString('DSN=', $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('SQL:', $message);
        $this->assertStringNotContainsString('host=', $message);
        $this->assertNull($exception->getPrevious());
    }

    /**
     * @return array{
     *     inspector: PostgreSqlGrantContextInspector,
     *     quoter: PostgreSqlIdentifierQuoter,
     *     executor: PostgreSqlGrantSqlExecutor
     * }
     */
    private function makeCountingDependencies(): array
    {
        $this->inspectorCalls = 0;
        $this->quoterCalls = 0;
        $this->executorCalls = 0;

        $appSnapshot = $this->snapshot();
        $ownerSnapshot = $this->snapshot(user: 'credimex_test_owner');
        $role = new PostgreSqlRoleSnapshot(
            name: 'credimex_test_app',
            exists: true,
            canLogin: true,
            hasConnect: true,
        );

        $inspector = new class($this, $appSnapshot, $ownerSnapshot, $role) implements PostgreSqlGrantContextInspector
        {
            public function __construct(
                private readonly PostgreSqlGrantManagerTest $test,
                private readonly PostgreSqlConnectionSnapshot $appSnapshot,
                private readonly PostgreSqlConnectionSnapshot $ownerSnapshot,
                private readonly PostgreSqlRoleSnapshot $role,
            ) {
            }

            public function inspect(string $configuredAppRole): PostgreSqlGrantContextParts
            {
                $this->test->incrementInspectorCalls();

                return new PostgreSqlGrantContextParts(
                    appConnection: $this->appSnapshot,
                    ownerConnection: $this->ownerSnapshot,
                    role: $this->role,
                );
            }
        };

        $quoter = new class($this) implements PostgreSqlIdentifierQuoter
        {
            public function __construct(private readonly PostgreSqlGrantManagerTest $test)
            {
            }

            public function quoteIdent(string $identifier): string
            {
                $this->test->incrementQuoterCalls();

                return '"'.$identifier.'"';
            }
        };

        $executor = new class($this) implements PostgreSqlGrantSqlExecutor
        {
            public function __construct(private readonly PostgreSqlGrantManagerTest $test)
            {
            }

            public function execute(string $sql): void
            {
                $this->test->incrementExecutorCalls();
                $this->test->recordSql($sql);
            }
        };

        return [
            'inspector' => $inspector,
            'quoter' => $quoter,
            'executor' => $executor,
        ];
    }

    public function incrementInspectorCalls(): void
    {
        $this->inspectorCalls++;
    }

    public function incrementQuoterCalls(): void
    {
        $this->quoterCalls++;
    }

    public function incrementExecutorCalls(): void
    {
        $this->executorCalls++;
    }

    private function makeValidManager(): PostgreSqlGrantManager
    {
        return $this->makeManager();
    }

    /**
     * @return array{Application&MockObject, DatabaseManager&MockObject}
     */
    private function makeOwnerMigrationApplication(
        string $runtimeDefault = PostgreSqlGrantManager::OWNER_CONNECTION,
        int $transactionLevel = 1,
        bool $failConfigResolution = false,
    ): array {
        $config = new Repository([
            'app' => ['env' => 'testing'],
            'database' => [
                'default' => $runtimeDefault,
                'connections' => [
                    'pgsql' => ['driver' => 'pgsql'],
                    'pgsql_owner' => ['driver' => 'pgsql'],
                ],
            ],
            'credimex' => [
                'database' => [
                    'schema' => 'credimex',
                    'app_role' => 'credimex_test_app',
                    'environments' => [
                        'testing' => [
                            'database' => 'credimex_test',
                            'owner_user' => 'credimex_test_owner',
                            'app_user' => 'credimex_test_app',
                        ],
                    ],
                ],
            ],
        ]);

        $ownerConnection = $this->createMock(Connection::class);
        $ownerConnection->method('transactionLevel')->willReturn($transactionLevel);

        $database = $this->createMock(DatabaseManager::class);
        $database->method('getDefaultConnection')->willReturnCallback(
            static fn (): string => (string) $config->get('database.default')
        );
        $database->method('setDefaultConnection')->willReturnCallback(
            static function (string $name) use ($config): void {
                $config->set('database.default', $name);
            }
        );
        $database->method('connection')->willReturn($ownerConnection);

        $application = $this->createMock(Application::class);
        $application->method('make')->willReturnCallback(
            static function (string $abstract) use (
                $config,
                $database,
                $failConfigResolution,
            ): mixed {
                if ($abstract === 'db') {
                    return $database;
                }

                if ($abstract === 'config' && ! $failConfigResolution) {
                    return $config;
                }

                throw new RuntimeException('detalle sensible de resolución');
            }
        );

        return [$application, $database];
    }

    /**
     * @param  array<string, array{database?: mixed, owner_user?: mixed, app_user?: mixed}>|null  $environmentCatalog
     */
    private function makeManager(
        string $environment = 'testing',
        string $defaultConnection = 'pgsql',
        ?string $pgsqlDriver = 'pgsql',
        ?string $pgsqlOwnerDriver = 'pgsql',
        ?string $configuredAppRole = 'credimex_test_app',
        string $schema = 'credimex',
        ?PostgreSqlConnectionSnapshot $appSnapshot = null,
        ?PostgreSqlConnectionSnapshot $ownerSnapshot = null,
        ?PostgreSqlRoleSnapshot $role = null,
        ?PostgreSqlGrantContextInspector $inspector = null,
        ?PostgreSqlIdentifierQuoter $quoter = null,
        ?PostgreSqlGrantSqlExecutor $executor = null,
        ?array $environmentCatalog = null,
    ): PostgreSqlGrantManager {
        $appSnapshot ??= $this->snapshot();
        $ownerSnapshot ??= $this->snapshot(user: 'credimex_test_owner');
        $role ??= new PostgreSqlRoleSnapshot(
            name: $configuredAppRole ?? 'credimex_test_app',
            exists: true,
            canLogin: true,
            hasConnect: true,
        );

        $inspector ??= new class($appSnapshot, $ownerSnapshot, $role) implements PostgreSqlGrantContextInspector
        {
            public function __construct(
                private readonly PostgreSqlConnectionSnapshot $appSnapshot,
                private readonly PostgreSqlConnectionSnapshot $ownerSnapshot,
                private readonly PostgreSqlRoleSnapshot $role,
            ) {
            }

            public function inspect(string $configuredAppRole): PostgreSqlGrantContextParts
            {
                return new PostgreSqlGrantContextParts(
                    appConnection: $this->appSnapshot,
                    ownerConnection: $this->ownerSnapshot,
                    role: $this->role,
                );
            }
        };

        $quoter ??= new class implements PostgreSqlIdentifierQuoter
        {
            public function quoteIdent(string $identifier): string
            {
                return '"'.$identifier.'"';
            }
        };

        $executor ??= new class($this) implements PostgreSqlGrantSqlExecutor
        {
            public function __construct(private readonly PostgreSqlGrantManagerTest $test)
            {
            }

            public function execute(string $sql): void
            {
                $this->test->recordSql($sql);
            }
        };

        return new PostgreSqlGrantManager(
            inspector: $inspector,
            quoter: $quoter,
            executor: $executor,
            environment: $environment,
            defaultConnection: $defaultConnection,
            pgsqlDriver: $pgsqlDriver,
            pgsqlOwnerDriver: $pgsqlOwnerDriver,
            configuredAppRole: $configuredAppRole,
            schema: $schema,
            environmentCatalog: $environmentCatalog ?? [
                'local' => [
                    'database' => 'credimex_dev',
                    'owner_user' => 'credimex_owner',
                    'app_user' => 'credimex_app',
                ],
                'testing' => [
                    'database' => 'credimex_test',
                    'owner_user' => 'credimex_test_owner',
                    'app_user' => 'credimex_test_app',
                ],
            ],
        );
    }

    public function recordSql(string $sql): void
    {
        $this->executedSql[] = $sql;
    }

    private function snapshot(
        string $database = 'credimex_test',
        string $user = 'credimex_test_app',
        string $schema = 'credimex',
        string $searchPath = 'credimex',
        string $schemasText = '{credimex}',
    ): PostgreSqlConnectionSnapshot {
        return new PostgreSqlConnectionSnapshot(
            database: $database,
            user: $user,
            schema: $schema,
            searchPath: $searchPath,
            schemasText: $schemasText,
        );
    }
}
