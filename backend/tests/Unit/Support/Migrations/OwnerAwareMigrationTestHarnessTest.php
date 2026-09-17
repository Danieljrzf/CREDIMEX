<?php

namespace Tests\Unit\Support\Migrations;

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
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;

final class OwnerAwareMigrationTestHarnessTest extends TestCase
{
    private const FIXTURE = 'tests/Fixtures/migrations/0000_00_00_000000_create_zz_test_owner_migration_harness_table.php';

    private const TABLE = 'zz_test_owner_migration_harness';

    private const MIGRATION = '0000_00_00_000000_create_zz_test_owner_migration_harness_table';

    private const MULTI_MIGRATIONS = [
        '0000_00_00_000001_create_zz_test_owner_multi_parent_a_table',
        '0000_00_00_000002_create_zz_test_owner_multi_parent_b_table',
        '0000_00_00_000003_create_zz_test_owner_multi_child_table',
    ];

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

    public function test_rejects_empty_file_list_without_starting_transaction(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness();

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('lista no vacía');

        $harness->runFiles([], [], static function (): void {});
    }

    public function test_rejects_more_than_eight_files_without_resolving_paths(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness();

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('máximo permitido');

        $harness->runFiles(array_fill(0, 9, 'invalid'), [], static function (): void {});
    }

    public function test_rejects_invalid_path_in_second_position_before_begin(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness();

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('[path]');

        $harness->runFiles(
            [$this->fixturePath(), $this->backendPath.'/tests/Fixtures/migrations/missing.php'],
            [],
            static function (): void {},
        );
    }

    public function test_rejects_unsafe_context_before_validating_multiple_paths(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness(environment: 'local');

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('[contexto]');

        $harness->runFiles(
            [$this->fixturePath(), $this->backendPath.'/tests/Fixtures/migrations/missing.php'],
            [],
            static function (): void {},
        );
    }

    public function test_rejects_duplicate_canonical_path_before_begin(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness();
        $fixture = $this->fixturePath();

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('archivos duplicados');

        $harness->runFiles([$fixture, $fixture], [], static function (): void {});
    }

    public function test_rejects_duplicate_migration_names(): void
    {
        [$harness] = $this->makeHarness();
        $method = (new ReflectionClass($harness))->getMethod('assertNoDuplicateMigrationNames');

        $this->expectExceptionMessage('nombres de migración duplicados');

        $method->invoke($harness, ['same_migration', 'same_migration']);
    }

    public function test_rejects_registered_migration_before_begin(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [true],
            tableExists: [],
        );

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('ya está registrada');

        $harness->runFile($this->fixturePath(), [], static function (): void {});
    }

    public function test_rejects_preexisting_expected_table_before_begin(): void
    {
        [$harness, $migrator, $owner] = $this->makeLifecycleHarness(
            migrationExists: [false],
            tableExists: [true],
        );

        $migrator->expects($this->never())->method('run');
        $owner->expects($this->never())->method('beginTransaction');

        $this->expectExceptionMessage('ya existe');

        $harness->runFile($this->fixturePath(), [self::TABLE], static function (): void {});
    }

    public function test_sanitizes_migration_baseline_failure_before_begin(): void
    {
        [$harness, $migrator, $owner] = $this->makeHarness();
        $query = $this->createMock(QueryBuilder::class);

        $query->method('where')->willReturnSelf();
        $query->method('exists')->willReturn(false);
        $owner->method('table')->with('migrations')->willReturn($query);
        $owner->method('select')->willThrowException(
            new RuntimeException('SQLSTATE password=secreto host=interno select * from migrations')
        );
        $owner->expects($this->never())->method('transactionLevel');
        $owner->expects($this->never())->method('beginTransaction');
        $owner->expects($this->never())->method('rollBack');
        $migrator->expects($this->never())->method('run');

        try {
            $harness->runFiles(
                [$this->fixturePath()],
                [],
                static function (): void {},
            );
            $this->fail('El fallo al capturar el baseline debía rechazarse.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertSame('contexto', $exception->stage);
            $this->assertStringContainsString('[contexto]', $exception->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $exception->getMessage());
            $this->assertStringNotContainsString('secreto', $exception->getMessage());
            $this->assertStringNotContainsString('interno', $exception->getMessage());
            $this->assertStringNotContainsString('select * from migrations', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_run_files_preserves_up_order_and_uses_inverse_rollback_order(): void
    {
        [$harness, , , $database, $state] = $this->makeStatefulMultiHarness();
        $paths = $this->multiFixturePaths();
        $assertionsExecuted = false;

        $harness->runFiles(
            $paths,
            [],
            static function () use (&$assertionsExecuted): void {
                $assertionsExecuted = true;
            },
        );

        $this->assertTrue($assertionsExecuted);
        $this->assertSame(self::MULTI_MIGRATIONS, $state->runOrder);
        $this->assertSame(array_reverse(self::MULTI_MIGRATIONS), $state->rollbackOrder);
        $this->assertSame(['existing_migration' => 7], $state->registered);
        $this->assertSame('pgsql', $database->getDefaultConnection());
    }

    public function test_intermediate_up_failure_skips_logical_rollback_and_uses_outer_rollback(): void
    {
        [$harness, , $owner, , $state] = $this->makeStatefulMultiHarness(failUpAt: 2);

        $owner->expects($this->once())->method('rollBack')->with(0);

        try {
            $harness->runFiles($this->multiFixturePaths(), [], static function (): void {});
            $this->fail('El segundo up debía fallar.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[up]', $exception->getMessage());
            $this->assertStringContainsString(self::MULTI_MIGRATIONS[1], $exception->getMessage());
            $this->assertSame([], $state->rollbackOrder);
            $this->assertSame(['existing_migration' => 7], $state->registered);
        }
    }

    public function test_last_up_failure_reports_last_migration_and_skips_logical_rollback(): void
    {
        [$harness, , $owner, , $state] = $this->makeStatefulMultiHarness(failUpAt: 3);

        $owner->expects($this->once())->method('rollBack')->with(0);

        try {
            $harness->runFiles($this->multiFixturePaths(), [], static function (): void {});
            $this->fail('El último up debía fallar.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[up]', $exception->getMessage());
            $this->assertStringContainsString(self::MULTI_MIGRATIONS[2], $exception->getMessage());
            $this->assertSame([], $state->rollbackOrder);
            $this->assertSame(['existing_migration' => 7], $state->registered);
        }
    }

    public function test_assertion_failure_skips_logical_rollback_and_restores_state(): void
    {
        [$harness, , $owner, , $state] = $this->makeStatefulMultiHarness();

        $owner->expects($this->once())->method('rollBack')->with(0);

        $this->expectExceptionMessage('[assertion]');

        try {
            $harness->runFiles(
                $this->multiFixturePaths(),
                [],
                static fn (): never => throw new RuntimeException('sensitive assertion'),
            );
        } finally {
            $this->assertSame([], $state->rollbackOrder);
            $this->assertSame(['existing_migration' => 7], $state->registered);
        }
    }

    public function test_rollback_failure_stops_inverse_cycle_and_uses_outer_rollback(): void
    {
        [$harness, , $owner, , $state] = $this->makeStatefulMultiHarness(
            failRollbackMigration: self::MULTI_MIGRATIONS[2],
        );

        $owner->expects($this->once())->method('rollBack')->with(0);

        $this->expectExceptionMessage('[down]');

        try {
            $harness->runFiles($this->multiFixturePaths(), [], static function (): void {});
        } finally {
            $this->assertSame([self::MULTI_MIGRATIONS[2]], $state->rollbackOrder);
            $this->assertSame(['existing_migration' => 7], $state->registered);
        }
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
        $query->method('value')->with('batch')->willReturn(1);

        $schema = $this->createMock(SchemaBuilder::class);
        $schema->method('hasTable')->willReturnOnConsecutiveCalls(...$tableExists);

        $owner->method('table')->with('migrations')->willReturn($query);
        $owner->method('getSchemaBuilder')->willReturn($schema);
        $owner->method('transactionLevel')->willReturnOnConsecutiveCalls(...$transactionLevels);
        $owner->method('getPdo')->willReturn($this->createStub(PDO::class));
        $migrationRow = (object) ['migration' => self::MIGRATION, 'batch' => 1];
        $owner->method('select')->willReturnOnConsecutiveCalls(
            [],
            [$migrationRow],
            [$migrationRow],
            [],
        );

        return [$harness, $migrator, $owner, $database];
    }

    /**
     * @return array{
     *     OwnerAwareMigrationTestHarness,
     *     Migrator&MockObject,
     *     Connection&MockObject,
     *     DatabaseManager&MockObject,
     *     object{
     *         registered: array<string, int>,
     *         runOrder: list<string>,
     *         rollbackOrder: list<string>
     *     }
     * }
     */
    private function makeStatefulMultiHarness(
        ?int $failUpAt = null,
        ?string $failRollbackMigration = null,
    ): array {
        [$harness, $migrator, $owner, $database] = $this->makeHarness();
        $state = (object) [
            'registered' => ['existing_migration' => 7],
            'runOrder' => [],
            'rollbackOrder' => [],
        ];
        $queriedMigration = null;

        $query = $this->createMock(QueryBuilder::class);
        $query->method('where')->willReturnCallback(
            static function (string $column, string $value) use (&$queriedMigration, $query): QueryBuilder {
                self::assertSame('migration', $column);
                $queriedMigration = $value;

                return $query;
            }
        );
        $query->method('exists')->willReturnCallback(
            static function () use (&$queriedMigration, $state): bool {
                return array_key_exists((string) $queriedMigration, $state->registered);
            }
        );
        $query->method('value')->with('batch')->willReturnCallback(
            static function () use (&$queriedMigration, $state): ?int {
                return $state->registered[(string) $queriedMigration] ?? null;
            }
        );

        $owner->method('table')->with('migrations')->willReturn($query);
        $owner->method('getSchemaBuilder')->willReturn($this->createMock(SchemaBuilder::class));
        $owner->method('transactionLevel')->willReturnOnConsecutiveCalls(0, 1, 1, 0);
        $owner->method('getPdo')->willReturn($this->createStub(PDO::class));
        $owner->method('select')->willReturnCallback(
            static function (string $sql) use ($state): array {
                $rows = [];

                foreach ($state->registered as $migration => $batch) {
                    $rows[] = (object) ['migration' => $migration, 'batch' => $batch];
                }

                if (str_contains($sql, 'order by batch desc')) {
                    usort(
                        $rows,
                        static fn (object $left, object $right): int => [$right->batch, $right->migration] <=> [$left->batch, $left->migration]
                    );

                    return array_slice($rows, 0, 1);
                }

                usort(
                    $rows,
                    static fn (object $left, object $right): int => $left->migration <=> $right->migration
                );

                return $rows;
            }
        );
        $owner->method('rollBack')->willReturnCallback(
            static function () use ($state): void {
                $state->registered = ['existing_migration' => 7];
            }
        );

        $migrator->method('run')->willReturnCallback(
            static function (array $paths, array $options) use ($state, $failUpAt): array {
                self::assertTrue($options['step']);
                $migration = pathinfo($paths[0], PATHINFO_FILENAME);
                $state->runOrder[] = $migration;

                if ($failUpAt !== null && count($state->runOrder) === $failUpAt) {
                    throw new RuntimeException('sensitive up failure');
                }

                $state->registered[$migration] = max($state->registered) + 1;

                return $paths;
            }
        );
        $migrator->method('rollback')->willReturnCallback(
            static function (array $paths, array $options) use ($state, $failRollbackMigration): array {
                self::assertSame(1, $options['step']);
                $migration = pathinfo($paths[0], PATHINFO_FILENAME);
                $state->rollbackOrder[] = $migration;

                if ($migration === $failRollbackMigration) {
                    throw new RuntimeException('sensitive rollback failure');
                }

                unset($state->registered[$migration]);

                return $paths;
            }
        );

        return [$harness, $migrator, $owner, $database, $state];
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

    /**
     * @return list<string>
     */
    private function multiFixturePaths(): array
    {
        return array_map(function (string $migration): string {
            $path = realpath($this->backendPath."/tests/Fixtures/migrations/{$migration}.php");

            self::assertNotFalse($path);

            return $path;
        }, self::MULTI_MIGRATIONS);
    }
}
