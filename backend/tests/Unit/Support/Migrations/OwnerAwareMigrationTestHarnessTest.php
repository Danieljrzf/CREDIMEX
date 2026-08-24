<?php

namespace Tests\Unit\Support\Migrations;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Tests\Support\Migrations\OwnerAwareMigrationException;
use Tests\Support\Migrations\OwnerAwareMigrationInspection;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;

final class OwnerAwareMigrationTestHarnessTest extends TestCase
{
    private const FIXTURE = 'tests/Fixtures/migrations/0000_00_00_000000_create_zz_test_owner_migration_harness_table.php';

    private const TABLE = 'zz_test_owner_migration_harness';

    private const MIGRATION = '0000_00_00_000000_create_zz_test_owner_migration_harness_table';

    private string $backendPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backendPath = dirname(__DIR__, 4);
    }

    public function test_rejects_incorrect_environment_without_starting_migrator(): void
    {
        [$harness, $migrator] = $this->makeHarness(environment: 'local');

        $migrator->expects($this->never())->method('run');

        $this->expectException(OwnerAwareMigrationException::class);
        $this->expectExceptionMessage('[contexto]');

        $harness->assertReady();
    }

    public function test_rejects_incorrect_database(): void
    {
        [$harness] = $this->makeHarness(appDatabase: 'credimex_dev');

        $this->expectException(OwnerAwareMigrationException::class);
        $this->expectExceptionMessage('[contexto]');

        $harness->assertReady();
    }

    public function test_rejects_incorrect_owner(): void
    {
        [$harness] = $this->makeHarness(ownerUser: 'credimex_owner');

        $this->expectException(OwnerAwareMigrationException::class);
        $this->expectExceptionMessage('[contexto]');

        $harness->assertReady();
    }

    public function test_rejects_incorrect_app_user(): void
    {
        [$harness] = $this->makeHarness(appUser: 'credimex_app');

        $this->expectException(OwnerAwareMigrationException::class);
        $this->expectExceptionMessage('[contexto]');

        $harness->assertReady();
    }

    public function test_rejects_unsafe_context_before_an_invalid_path(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness(environment: 'local');

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        try {
            $harness->runFile(
                $this->backendPath.'/tests/Fixtures/migrations/missing.php',
                [self::TABLE],
                static function (): void {},
            );
            $this->fail('El contexto inseguro debía rechazarse antes del path.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[contexto]', $exception->getMessage());
            $this->assertStringNotContainsString('[path]', $exception->getMessage());
        }
    }

    public function test_rejects_nonexistent_path_without_starting_migrator(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness();
        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('[path]');

        $harness->runFile(
            $this->backendPath.'/tests/Fixtures/migrations/missing.php',
            [self::TABLE],
            static function (): void {},
        );
    }

    public function test_rejects_unsafe_context_before_an_invalid_expected_object_name(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness(environment: 'local');

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        try {
            $harness->runFile(
                $this->fixturePath(),
                ['INVALID-TABLE-NAME'],
                static function (): void {},
            );
            $this->fail('El contexto inseguro debía rechazarse antes del nombre esperado.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[contexto]', $exception->getMessage());
            $this->assertStringNotContainsString('identificador inválido', $exception->getMessage());
        }
    }

    public function test_rejects_file_outside_allowed_roots(): void
    {
        [$harness] = $this->makeHarness();

        $this->expectExceptionMessage('fuera de los roots permitidos');

        $harness->runFile(
            $this->backendPath.'/tests/Support/PostgreSqlTestSafetyGuard.php',
            [self::TABLE],
            static function (): void {},
        );
    }

    public function test_rejects_path_traversal_that_resolves_outside_allowed_roots(): void
    {
        [$harness] = $this->makeHarness();

        $this->expectExceptionMessage('fuera de los roots permitidos');

        $harness->runFile(
            $this->backendPath.'/tests/Fixtures/migrations/../../Support/PostgreSqlTestSafetyGuard.php',
            [self::TABLE],
            static function (): void {},
        );
    }

    public function test_rejects_non_php_extension(): void
    {
        [$harness] = $this->makeHarness();

        $this->expectExceptionMessage('extensión .php');

        $harness->runFile(
            $this->backendPath.'/composer.json',
            [self::TABLE],
            static function (): void {},
        );
    }

    public function test_rejects_directory_instead_of_individual_file(): void
    {
        [$harness] = $this->makeHarness();

        $this->expectExceptionMessage('no un directorio');

        $harness->runFile(
            $this->backendPath.'/tests/Fixtures/migrations',
            [self::TABLE],
            static function (): void {},
        );
    }

    public function test_public_api_does_not_accept_arbitrary_connection_configuration(): void
    {
        $reflection = new ReflectionClass(OwnerAwareMigrationTestHarness::class);
        $publicMethods = array_filter(
            $reflection->getMethods(),
            static fn ($method): bool => $method->isPublic(),
        );
        $forbiddenParameters = ['database', 'schema', 'owner', 'role', 'password', 'host', 'dsn'];

        $this->assertFalse($reflection->getConstructor()?->isPublic());

        foreach ($publicMethods as $method) {
            foreach ($method->getParameters() as $parameter) {
                $this->assertNotContains(strtolower($parameter->getName()), $forbiddenParameters);
            }
        }
    }

    public function test_rejects_preexisting_owner_transaction_without_rolling_it_back(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false],
            tableExists: [false],
            transactionLevels: [1, 1],
        );

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');
        $owner->expects($this->never())->method('rollBack');

        $this->expectExceptionMessage('ya tenía una transacción activa');

        $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
    }

    public function test_restores_previous_default_connection_after_success(): void
    {
        [$harness, $migrator, , $database] = $this->makeLifecycleHarness(
            migrationExists: [false, true, false, false],
            tableExists: [false, true, false, false],
        );

        $migrator->method('run')->willReturn([$this->fixturePath()]);
        $migrator->method('rollback')->willReturn([$this->fixturePath()]);

        $this->assertSame('pgsql', $database->getDefaultConnection());

        $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});

        $this->assertSame('pgsql', $database->getDefaultConnection());
    }

    public function test_migrator_exception_does_not_leave_owner_as_default(): void
    {
        [$harness, $migrator, , $database] = $this->makeLifecycleHarness(
            migrationExists: [false, false],
            tableExists: [false, false],
        );

        $migrator->method('run')->willThrowException(new RuntimeException('fallo controlado'));

        try {
            $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
            $this->fail('El escenario debía fallar durante up.');
        } catch (OwnerAwareMigrationException) {
            $this->assertSame('pgsql', $database->getDefaultConnection());
        }
    }

    public function test_rolls_back_outer_transaction_when_up_fails_and_sanitizes_error(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false, false],
            tableExists: [false, false],
        );

        $migrator->method('run')->willThrowException(
            new RuntimeException('SQLSTATE password=secreto host=interno select * from sensible')
        );
        $owner->expects($this->once())->method('rollBack')->with(0);

        try {
            $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
            $this->fail('El escenario debía fallar durante up.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[up]', $exception->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $exception->getMessage());
            $this->assertStringNotContainsString('secreto', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_rolls_back_outer_transaction_when_assertion_fails(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false, true, false],
            tableExists: [false, true, false],
        );

        $migrator->method('run')->willReturn([$this->fixturePath()]);
        $owner->expects($this->once())->method('rollBack')->with(0);

        $this->expectExceptionMessage('[assertion]');

        $harness->runFile(
            $this->fixturePath(),
            [self::TABLE],
            static fn (): never => throw new RuntimeException('detalle sensible'),
        );
    }

    public function test_rolls_back_outer_transaction_when_down_fails(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false, true, false],
            tableExists: [false, true, false],
        );

        $migrator->method('run')->willReturn([$this->fixturePath()]);
        $migrator->method('rollback')->willThrowException(new RuntimeException('driver detail'));
        $owner->expects($this->once())->method('rollBack')->with(0);

        $this->expectExceptionMessage('[down]');

        $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
    }

    public function test_cleanup_failure_is_not_silenced(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false, true, false],
            tableExists: [false, true, false],
            transactionLevels: [0, 1, 1],
        );

        $migrator->method('run')->willReturn([$this->fixturePath()]);
        $migrator->method('rollback')->willReturn([$this->fixturePath()]);
        $owner->method('rollBack')->willThrowException(new RuntimeException('SQLSTATE cleanup'));

        try {
            $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
            $this->fail('El cleanup debía fallar explícitamente.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[cleanup]', $exception->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_cleanup_failure_preserves_the_original_failed_stage(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false],
            tableExists: [false],
            transactionLevels: [0, 1, 1],
        );

        $migrator->method('run')->willThrowException(new RuntimeException('detalle sensible de up'));
        $owner->method('rollBack')->willThrowException(new RuntimeException('detalle sensible de cleanup'));

        try {
            $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
            $this->fail('El cleanup debía reportar también la etapa original fallida.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[cleanup]', $exception->getMessage());
            $this->assertStringContainsString('etapa [up]', $exception->getMessage());
            $this->assertStringNotContainsString('sensible', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    /**
     * @return array{
     *     OwnerAwareMigrationTestHarness,
     *     Migrator&MockObject,
     *     Connection&MockObject,
     *     DatabaseManager&MockObject
     * }
     */
    private function makeLifecycleHarness(
        array $migrationExists,
        array $tableExists,
        array $transactionLevels = [0, 1, 1, 0],
    ): array {
        [$harness, $migrator, $owner, $database] = $this->makeHarness();

        $query = $this->createMock(QueryBuilder::class);
        $query->method('where')->willReturnSelf();
        $query->method('exists')->willReturnOnConsecutiveCalls(...$migrationExists);

        $schema = $this->createMock(SchemaBuilder::class);
        $schema->method('hasTable')->willReturnOnConsecutiveCalls(...$tableExists);

        $owner->method('table')->with('migrations')->willReturn($query);
        $owner->method('getSchemaBuilder')->willReturn($schema);
        $owner->method('transactionLevel')->willReturnOnConsecutiveCalls(...$transactionLevels);
        $owner->method('getPdo')->willReturn($this->createStub(PDO::class));

        return [$harness, $migrator, $owner, $database];
    }

    /**
     * @return array{
     *     OwnerAwareMigrationTestHarness,
     *     Migrator&MockObject,
     *     Connection&MockObject,
     *     DatabaseManager&MockObject
     * }
     */
    private function makeHarness(
        string $environment = 'testing',
        string $appDatabase = 'credimex_test',
        string $appUser = 'credimex_test_app',
        string $ownerDatabase = 'credimex_test',
        string $ownerUser = 'credimex_test_owner',
    ): array {
        $config = new Repository([
            'app' => ['env' => $environment],
            'database' => [
                'default' => 'pgsql',
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

        $appConnection = $this->createMock(Connection::class);
        $appConnection->method('selectOne')->willReturn($this->contextRow($appDatabase, $appUser));

        $ownerConnection = $this->createMock(Connection::class);
        $ownerConnection->method('selectOne')->willReturnCallback(
            fn (string $query): object => str_contains($query, 'from pg_roles')
                ? (object) [
                    'role_name' => 'credimex_test_app',
                    'can_login' => true,
                    'has_connect' => true,
                ]
                : $this->contextRow($ownerDatabase, $ownerUser)
        );

        $database = $this->createMock(DatabaseManager::class);
        $database->method('connection')->willReturnCallback(
            static fn (?string $name = null): Connection => $name === 'pgsql' ? $appConnection : $ownerConnection
        );
        $currentDefaultConnection = 'pgsql';
        $database->method('getDefaultConnection')->willReturnCallback(
            static function () use (&$currentDefaultConnection): string {
                return $currentDefaultConnection;
            }
        );
        $database->method('setDefaultConnection')->willReturnCallback(
            static function (string $name) use (&$currentDefaultConnection): void {
                $currentDefaultConnection = $name;
            }
        );

        $migrator = $this->createMock(Migrator::class);
        $migrator->method('usingConnection')->willReturnCallback(
            static function (string $name, callable $callback) use ($database): mixed {
                $previousDefaultConnection = $database->getDefaultConnection();
                $database->setDefaultConnection($name);

                try {
                    return $callback();
                } finally {
                    $database->setDefaultConnection($previousDefaultConnection);
                }
            }
        );
        $migrator->method('repositoryExists')->willReturn(true);

        $application = $this->createMock(Application::class);
        $application->method('make')->willReturnCallback(
            static fn (string $abstract): mixed => match ($abstract) {
                'config' => $config,
                'db' => $database,
                'migrator' => $migrator,
                default => throw new RuntimeException('Binding inesperado en prueba.'),
            }
        );
        $application->method('basePath')->willReturnCallback(
            fn (string $path = ''): string => $this->backendPath.($path === '' ? '' : '/'.$path)
        );
        $application->method('databasePath')->willReturnCallback(
            fn (string $path = ''): string => $this->backendPath.'/database'.($path === '' ? '' : '/'.$path)
        );

        return [
            OwnerAwareMigrationTestHarness::fromApplication($application),
            $migrator,
            $ownerConnection,
            $database,
        ];
    }

    private function contextRow(string $database, string $user): object
    {
        return (object) [
            'database_name' => $database,
            'database_user' => $user,
            'schema_name' => 'credimex',
            'search_path' => 'credimex',
            'schemas_text' => '{credimex}',
        ];
    }

    private function fixturePath(): string
    {
        $path = realpath($this->backendPath.'/'.self::FIXTURE);

        self::assertNotFalse($path);

        return $path;
    }
}
