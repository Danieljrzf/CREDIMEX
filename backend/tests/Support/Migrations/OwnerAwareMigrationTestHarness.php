<?php

namespace Tests\Support\Migrations;

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Tests\Support\LaravelPostgreSqlContextInspector;
use Tests\Support\PostgreSqlContextInspector;
use Tests\Support\PostgreSqlTestSafetyGuard;
use Throwable;

final class OwnerAwareMigrationTestHarness
{
    public const OWNER_CONNECTION = 'pgsql_owner';

    private const IDENTIFIER_PATTERN = '/^[a-z][a-z0-9_]*$/';

    private const MAX_FILES_PER_SCENARIO = 8;

    private function __construct(
        private readonly Application $app,
        private readonly DatabaseManager $database,
        private readonly Migrator $migrator,
        private readonly Connection $ownerConnection,
        private readonly PostgreSqlContextInspector $contextInspector,
    ) {}

    public static function fromApplication(Application $app): self
    {
        /** @var DatabaseManager $database */
        $database = $app->make('db');

        /** @var Migrator $migrator */
        $migrator = $app->make('migrator');

        return new self(
            app: $app,
            database: $database,
            migrator: $migrator,
            ownerConnection: $database->connection(self::OWNER_CONNECTION),
            contextInspector: new LaravelPostgreSqlContextInspector($database),
        );
    }

    public function assertReady(): self
    {
        try {
            PostgreSqlTestSafetyGuard::enforceUsingApplication(
                $this->app,
                $this->contextInspector,
            );

            PostgreSqlGrantManager::fromApplication($this->app)->assertSafeContext();

            $config = $this->app->make('config');

            if ($config->get('credimex.database.schema') !== PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA) {
                throw OwnerAwareMigrationException::forStage(
                    'contexto',
                    'El esquema CREDIMEX configurado no coincide con el esquema exclusivo de testing.'
                );
            }

            if ($config->get('credimex.database.app_role') !== PostgreSqlTestSafetyGuard::EXPECTED_APP_USER) {
                throw OwnerAwareMigrationException::forStage(
                    'contexto',
                    'El rol app configurado no coincide con el rol exclusivo de testing.'
                );
            }

            $repositoryExists = $this->migrator->usingConnection(
                self::OWNER_CONNECTION,
                fn (): bool => $this->migrator->repositoryExists(),
            );

            if (! $repositoryExists) {
                throw OwnerAwareMigrationException::forStage(
                    'contexto',
                    'La tabla técnica migrations debe existir previamente en credimex_test.'
                );
            }
        } catch (OwnerAwareMigrationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw OwnerAwareMigrationException::forStage(
                'contexto',
                'No fue posible validar de forma segura el contexto PostgreSQL de testing.'
            );
        }

        return $this;
    }

    /**
     * @param  list<string>  $expectedAbsentTables
     * @param  Closure(OwnerAwareMigrationInspection): void  $assertions
     */
    public function runFile(
        string $absolutePath,
        array $expectedAbsentTables,
        Closure $assertions,
    ): void {
        $this->runFiles(
            absolutePaths: [$absolutePath],
            expectedAbsentTables: $expectedAbsentTables,
            assertions: $assertions,
        );
    }

    /**
     * @param  list<string>  $absolutePaths
     * @param  list<string>  $expectedAbsentTables
     * @param  Closure(OwnerAwareMigrationInspection): void  $assertions
     */
    public function runFiles(
        array $absolutePaths,
        array $expectedAbsentTables,
        Closure $assertions,
    ): void {
        $this->assertReady();

        $this->assertSafeMigrationPathList($absolutePaths);
        $canonicalPaths = array_map(
            fn (string $path): string => $this->resolveAllowedMigrationFile($path),
            $absolutePaths,
        );
        $this->assertNoDuplicatePaths($canonicalPaths);
        $this->assertSafeTableNames($expectedAbsentTables);
        $migrationNames = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $canonicalPaths,
        );
        $this->assertNoDuplicateMigrationNames($migrationNames);

        $inspection = new OwnerAwareMigrationInspection($this->ownerConnection);
        $baselineMigrations = $this->assertPreconditions(
            $inspection,
            $migrationNames,
            $expectedAbsentTables,
        );

        $previousDefaultConnection = $this->database->getDefaultConnection();
        $initialTransactionLevel = null;
        $outerTransactionStarted = false;
        $stage = 'contexto';
        $currentMigrationName = null;
        $currentMigrationPosition = null;
        $failure = null;
        $cleanupFailure = null;

        try {
            $initialTransactionLevel = $this->ownerConnection->transactionLevel();

            if ($initialTransactionLevel !== 0) {
                throw OwnerAwareMigrationException::forStage(
                    'contexto',
                    'La conexión owner ya tenía una transacción activa antes del escenario.'
                );
            }

            $this->ownerConnection->beginTransaction();
            $outerTransactionStarted = true;

            if ($this->ownerConnection->transactionLevel() !== $initialTransactionLevel + 1) {
                throw OwnerAwareMigrationException::forStage(
                    'contexto',
                    'No fue posible establecer la transacción exterior owner en el nivel esperado.'
                );
            }

            $this->migrator->usingConnection(self::OWNER_CONNECTION, function () use (
                $canonicalPaths,
                $migrationNames,
                $expectedAbsentTables,
                $assertions,
                $inspection,
                $baselineMigrations,
                &$stage,
                &$currentMigrationName,
                &$currentMigrationPosition,
            ): void {
                $scenarioBatches = [];
                $totalMigrations = count($canonicalPaths);

                foreach ($canonicalPaths as $index => $canonicalPath) {
                    $stage = 'up';
                    $currentMigrationName = $migrationNames[$index];
                    $currentMigrationPosition = $index + 1;
                    $ran = $this->migrator->run([$canonicalPath], ['step' => true]);

                    if ($ran !== [$canonicalPath]) {
                        throw OwnerAwareMigrationException::forStage(
                            'up',
                            $this->migrationFailureDetail(
                                $currentMigrationName,
                                $currentMigrationPosition,
                                $totalMigrations,
                                'El Migrator no ejecutó exactamente el archivo solicitado.',
                            ),
                        );
                    }

                    if (! $inspection->migrationIsRegistered($currentMigrationName)) {
                        throw OwnerAwareMigrationException::forStage(
                            'up',
                            $this->migrationFailureDetail(
                                $currentMigrationName,
                                $currentMigrationPosition,
                                $totalMigrations,
                                'La migración no quedó registrada temporalmente después de up.',
                            ),
                        );
                    }

                    $batch = $inspection->migrationBatch($currentMigrationName);

                    if ($batch === null) {
                        throw OwnerAwareMigrationException::forStage(
                            'up',
                            $this->migrationFailureDetail(
                                $currentMigrationName,
                                $currentMigrationPosition,
                                $totalMigrations,
                                'No fue posible identificar el batch temporal de la migración.',
                            ),
                        );
                    }

                    $scenarioBatches[$currentMigrationName] = $batch;
                    $this->assertMigrationSnapshot(
                        inspection: $inspection,
                        baselineMigrations: $baselineMigrations,
                        scenarioMigrations: $scenarioBatches,
                        stage: 'up',
                    );
                }

                foreach ($expectedAbsentTables as $table) {
                    if (! $inspection->tableExists($table)) {
                        throw OwnerAwareMigrationException::forStage(
                            'up',
                            'Un objeto esperado no fue creado por el escenario completo.'
                        );
                    }
                }

                $stage = 'assertion';
                $currentMigrationName = null;
                $currentMigrationPosition = null;
                $assertions($inspection);

                foreach (array_reverse(array_keys($canonicalPaths)) as $index) {
                    $stage = 'down';
                    $canonicalPath = $canonicalPaths[$index];
                    $currentMigrationName = $migrationNames[$index];
                    $currentMigrationPosition = $index + 1;
                    $latestMigration = $inspection->latestMigration();

                    if ($latestMigration === null ||
                        $latestMigration->migration !== $currentMigrationName ||
                        $latestMigration->batch !== $scenarioBatches[$currentMigrationName]) {
                        throw OwnerAwareMigrationException::forStage(
                            'down',
                            $this->migrationFailureDetail(
                                $currentMigrationName,
                                $currentMigrationPosition,
                                $totalMigrations,
                                'La última fila de migrations no corresponde al rollback solicitado.',
                            ),
                        );
                    }

                    $rolledBack = $this->migrator->rollback([$canonicalPath], ['step' => 1]);

                    if ($rolledBack !== [$canonicalPath]) {
                        throw OwnerAwareMigrationException::forStage(
                            'down',
                            $this->migrationFailureDetail(
                                $currentMigrationName,
                                $currentMigrationPosition,
                                $totalMigrations,
                                'El Migrator no revirtió exactamente el archivo solicitado.',
                            ),
                        );
                    }

                    if ($inspection->migrationIsRegistered($currentMigrationName)) {
                        throw OwnerAwareMigrationException::forStage(
                            'down',
                            $this->migrationFailureDetail(
                                $currentMigrationName,
                                $currentMigrationPosition,
                                $totalMigrations,
                                'El registro temporal de migrations no fue eliminado por rollback.',
                            ),
                        );
                    }

                    unset($scenarioBatches[$currentMigrationName]);
                    $this->assertMigrationSnapshot(
                        inspection: $inspection,
                        baselineMigrations: $baselineMigrations,
                        scenarioMigrations: $scenarioBatches,
                        stage: 'down',
                    );
                }

                foreach ($expectedAbsentTables as $table) {
                    if ($inspection->tableExists($table)) {
                        throw OwnerAwareMigrationException::forStage(
                            'down',
                            'Un objeto de la migración permaneció después del rollback controlado.'
                        );
                    }
                }
            });
        } catch (OwnerAwareMigrationException $exception) {
            $failure = $exception;
        } catch (Throwable) {
            $failure = OwnerAwareMigrationException::forStage(
                $stage,
                $this->stageFailureDetail(
                    $stage,
                    $currentMigrationName,
                    $currentMigrationPosition,
                    count($canonicalPaths),
                ),
            );
        } finally {
            try {
                $this->cleanupOuterTransaction(
                    outerTransactionStarted: $outerTransactionStarted,
                    initialTransactionLevel: $initialTransactionLevel,
                    previousDefaultConnection: $previousDefaultConnection,
                    inspection: $inspection,
                    migrationNames: $migrationNames,
                    expectedAbsentTables: $expectedAbsentTables,
                );
            } catch (Throwable) {
                $cleanupFailure = OwnerAwareMigrationException::forCleanup($failure);
            }
        }

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    /**
     * @param  array<mixed>  $paths
     */
    private function assertSafeMigrationPathList(array $paths): void
    {
        if (! array_is_list($paths) || $paths === []) {
            throw OwnerAwareMigrationException::forStage(
                'path',
                'El escenario debe contener una lista no vacía de archivos.',
            );
        }

        if (count($paths) > self::MAX_FILES_PER_SCENARIO) {
            throw OwnerAwareMigrationException::forStage(
                'path',
                'El escenario excede el máximo permitido de archivos.',
            );
        }

        foreach ($paths as $path) {
            if (! is_string($path)) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'La lista de migraciones contiene un path inválido.',
                );
            }
        }
    }

    /**
     * @param  list<string>  $paths
     */
    private function assertNoDuplicatePaths(array $paths): void
    {
        $normalizedPaths = array_map(
            static fn (string $path): string => PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path,
            $paths,
        );

        if (count(array_unique($normalizedPaths)) !== count($normalizedPaths)) {
            throw OwnerAwareMigrationException::forStage(
                'path',
                'El escenario contiene archivos duplicados.',
            );
        }
    }

    /**
     * @param  list<string>  $migrationNames
     */
    private function assertNoDuplicateMigrationNames(array $migrationNames): void
    {
        if (count(array_unique($migrationNames)) !== count($migrationNames)) {
            throw OwnerAwareMigrationException::forStage(
                'path',
                'El escenario contiene nombres de migración duplicados.',
            );
        }
    }

    private function resolveAllowedMigrationFile(string $path): string
    {
        try {
            if (! $this->isAbsolutePath($path)) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'La migración debe indicarse mediante un path absoluto.'
                );
            }

            if (! file_exists($path)) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'El archivo de migración indicado no existe.'
                );
            }

            if (is_dir($path)) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'Debe indicarse un archivo individual, no un directorio.'
                );
            }

            if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'El archivo de migración debe tener extensión .php.'
                );
            }

            $canonicalPath = realpath($path);

            if ($canonicalPath === false || ! is_file($canonicalPath)) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'No fue posible resolver el archivo de migración de forma canónica.'
                );
            }

            foreach ($this->allowedRoots() as $root) {
                if ($this->pathIsInsideRoot($canonicalPath, $root)) {
                    return $canonicalPath;
                }
            }
        } catch (OwnerAwareMigrationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw OwnerAwareMigrationException::forStage(
                'path',
                'No fue posible validar de forma segura el archivo de migración.'
            );
        }

        throw OwnerAwareMigrationException::forStage(
            'path',
            'El archivo de migración está fuera de los roots permitidos.'
        );
    }

    /**
     * @return list<string>
     */
    private function allowedRoots(): array
    {
        $roots = [
            $this->app->databasePath('migrations'),
            $this->app->basePath('tests/Fixtures/migrations'),
        ];

        return array_map(function (string $root): string {
            $canonicalRoot = realpath($root);

            if ($canonicalRoot === false || ! is_dir($canonicalRoot)) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'Un root permitido de migraciones no existe o no puede resolverse.'
                );
            }

            return rtrim($canonicalRoot, DIRECTORY_SEPARATOR);
        }, $roots);
    }

    private function pathIsInsideRoot(string $path, string $root): bool
    {
        $prefix = $root.DIRECTORY_SEPARATOR;

        if (PHP_OS_FAMILY === 'Windows') {
            return str_starts_with(strtolower($path), strtolower($prefix));
        }

        return str_starts_with($path, $prefix);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return preg_match('/^(?:[a-zA-Z]:[\\\\\/]|\\\\\\\\)/', $path) === 1;
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param  list<string>  $tables
     */
    private function assertSafeTableNames(array $tables): void
    {
        if (! array_is_list($tables) || count(array_unique($tables, SORT_REGULAR)) !== count($tables)) {
            throw OwnerAwareMigrationException::forStage(
                'path',
                'La lista de objetos esperados debe ser una lista sin duplicados.',
            );
        }

        foreach ($tables as $table) {
            if (! is_string($table) || preg_match(self::IDENTIFIER_PATTERN, $table) !== 1) {
                throw OwnerAwareMigrationException::forStage(
                    'path',
                    'La lista de objetos esperados contiene un identificador inválido.'
                );
            }
        }
    }

    /**
     * @param  list<string>  $expectedAbsentTables
     * @return array<string, int>
     */
    private function assertPreconditions(
        OwnerAwareMigrationInspection $inspection,
        array $migrationNames,
        array $expectedAbsentTables,
    ): array {
        try {
            foreach ($migrationNames as $migrationName) {
                if ($inspection->migrationIsRegistered($migrationName)) {
                    throw OwnerAwareMigrationException::forStage(
                        'contexto',
                        'Una migración del escenario ya está registrada persistentemente en migrations.'
                    );
                }
            }

            foreach ($expectedAbsentTables as $table) {
                if ($inspection->tableExists($table)) {
                    throw OwnerAwareMigrationException::forStage(
                        'contexto',
                        'Un objeto esperado de la migración ya existe antes del escenario.'
                    );
                }
            }

            return $inspection->migrationBatches();
        } catch (OwnerAwareMigrationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw OwnerAwareMigrationException::forStage(
                'contexto',
                'No fue posible comprobar las precondiciones persistentes del escenario.'
            );
        }
    }

    /**
     * @param  list<string>  $expectedAbsentTables
     */
    private function cleanupOuterTransaction(
        bool $outerTransactionStarted,
        ?int $initialTransactionLevel,
        string $previousDefaultConnection,
        OwnerAwareMigrationInspection $inspection,
        array $migrationNames,
        array $expectedAbsentTables,
    ): void {
        try {
            if ($outerTransactionStarted) {
                if ($initialTransactionLevel === null ||
                    $this->ownerConnection->transactionLevel() < $initialTransactionLevel + 1) {
                    throw OwnerAwareMigrationException::forStage(
                        'cleanup',
                        'La transacción exterior owner dejó de estar activa antes del cleanup.'
                    );
                }

                $this->ownerConnection->rollBack($initialTransactionLevel);
            }

            if ($initialTransactionLevel !== null &&
                $this->ownerConnection->transactionLevel() !== $initialTransactionLevel) {
                throw OwnerAwareMigrationException::forStage(
                    'cleanup',
                    'La conexión owner no regresó al nivel transaccional inicial.'
                );
            }

            if ($outerTransactionStarted) {
                $this->ownerConnection->getPdo();

                foreach ($migrationNames as $migrationName) {
                    if ($inspection->migrationIsRegistered($migrationName)) {
                        throw OwnerAwareMigrationException::forStage(
                            'cleanup',
                            'El rollback exterior dejó un registro persistente en migrations.'
                        );
                    }
                }

                foreach ($expectedAbsentTables as $table) {
                    if ($inspection->tableExists($table)) {
                        throw OwnerAwareMigrationException::forStage(
                            'cleanup',
                            'El rollback exterior dejó un objeto persistente.'
                        );
                    }
                }
            }
        } finally {
            $this->database->setDefaultConnection($previousDefaultConnection);

            if ($this->database->getDefaultConnection() !== $previousDefaultConnection) {
                throw OwnerAwareMigrationException::forStage(
                    'cleanup',
                    'La conexión default no regresó al valor previo del escenario.'
                );
            }
        }
    }

    /**
     * @param  array<string, int>  $baselineMigrations
     * @param  array<string, int>  $scenarioMigrations
     */
    private function assertMigrationSnapshot(
        OwnerAwareMigrationInspection $inspection,
        array $baselineMigrations,
        array $scenarioMigrations,
        string $stage,
    ): void {
        $expected = $baselineMigrations + $scenarioMigrations;
        $actual = $inspection->migrationBatches();

        ksort($expected);
        ksort($actual);

        if ($actual !== $expected) {
            throw OwnerAwareMigrationException::forStage(
                $stage,
                'El historial migrations contiene cambios ajenos al escenario controlado.',
            );
        }
    }

    private function stageFailureDetail(
        string $stage,
        ?string $migrationName,
        ?int $migrationPosition,
        int $totalMigrations,
    ): string {
        $detail = match ($stage) {
            'up' => 'La migración no pudo ejecutarse mediante el Migrator.',
            'assertion' => 'Las verificaciones del escenario no se cumplieron.',
            'down' => 'La migración no pudo revertirse mediante el Migrator.',
            default => 'No fue posible preparar la transacción exterior owner.',
        };

        if ($migrationName === null || $migrationPosition === null || ! in_array($stage, ['up', 'down'], true)) {
            return $detail;
        }

        return $this->migrationFailureDetail(
            $migrationName,
            $migrationPosition,
            $totalMigrations,
            $detail,
        );
    }

    private function migrationFailureDetail(
        string $migrationName,
        int $migrationPosition,
        int $totalMigrations,
        string $detail,
    ): string {
        return "Migración [{$migrationName}] ({$migrationPosition}/{$totalMigrations}): {$detail}";
    }
}
