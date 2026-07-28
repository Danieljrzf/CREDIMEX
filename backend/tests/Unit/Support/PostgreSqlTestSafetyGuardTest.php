<?php

namespace Tests\Unit\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\PostgreSqlConnectionContext;
use Tests\Support\PostgreSqlContextInspector;
use Tests\Support\PostgreSqlTestSafetyException;
use Tests\Support\PostgreSqlTestSafetyGuard;
use Throwable;

final class PostgreSqlTestSafetyGuardTest extends TestCase
{
    private PostgreSqlTestSafetyGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new PostgreSqlTestSafetyGuard;
    }

    public function test_rejects_environment_other_than_testing(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('entorno Laravel debe ser exactamente "testing"');

        $this->guard->assertEnvironmentIsSafe(
            'local',
            PostgreSqlTestSafetyGuard::EXPECTED_DEFAULT_CONNECTION,
            PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
            PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
        );
    }

    public function test_rejects_default_connection_other_than_pgsql(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('conexión default debe ser exactamente "pgsql"');

        $this->guard->assertEnvironmentIsSafe(
            PostgreSqlTestSafetyGuard::EXPECTED_ENVIRONMENT,
            'sqlite',
            PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
            PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
        );
    }

    public function test_rejects_pgsql_driver_other_than_pgsql(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('conexión pgsql debe usar el driver "pgsql"');

        $this->guard->assertEnvironmentIsSafe(
            PostgreSqlTestSafetyGuard::EXPECTED_ENVIRONMENT,
            PostgreSqlTestSafetyGuard::EXPECTED_DEFAULT_CONNECTION,
            'mysql',
            PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
        );
    }

    public function test_rejects_pgsql_owner_driver_other_than_pgsql(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('conexión pgsql_owner debe usar el driver "pgsql"');

        $this->guard->assertEnvironmentIsSafe(
            PostgreSqlTestSafetyGuard::EXPECTED_ENVIRONMENT,
            PostgreSqlTestSafetyGuard::EXPECTED_DEFAULT_CONNECTION,
            PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
            'sqlite',
        );
    }

    public function test_rejects_app_database_other_than_credimex_test(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('base "credimex_test"');

        $this->guard->assertAppContext($this->validAppContext(database: 'credimex_dev'));
    }

    public function test_rejects_app_user_other_than_credimex_test_app(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('usuario "credimex_test_app"');

        $this->guard->assertAppContext($this->validAppContext(user: 'credimex_app'));
    }

    public function test_rejects_app_schema_other_than_credimex(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('esquema "credimex"');

        $this->guard->assertAppContext($this->validAppContext(schema: 'public'));
    }

    public function test_rejects_app_search_path_other_than_credimex(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('search_path "credimex"');

        $this->guard->assertAppContext($this->validAppContext(searchPath: 'credimex,public'));
    }

    public function test_rejects_owner_database_other_than_credimex_test(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('base "credimex_test"');

        $this->guard->assertOwnerContext($this->validOwnerContext(database: 'postgres'));
    }

    public function test_rejects_owner_user_other_than_credimex_test_owner(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('usuario "credimex_test_owner"');

        $this->guard->assertOwnerContext($this->validOwnerContext(user: 'credimex_owner'));
    }

    public function test_rejects_owner_schema_other_than_credimex(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('esquema "credimex"');

        $this->guard->assertOwnerContext($this->validOwnerContext(schema: 'public'));
    }

    public function test_rejects_owner_search_path_other_than_credimex(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('search_path "credimex"');

        $this->guard->assertOwnerContext($this->validOwnerContext(searchPath: 'public'));
    }

    public function test_rejects_schemas_text_other_than_credimex(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage('current_schemas(false)');

        $this->guard->assertAppContext($this->validAppContext(schemasText: '{credimex,public}'));
    }

    public function test_rejects_refresh_database_trait(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage(RefreshDatabase::class);
        $this->expectExceptionMessage('traits estándar de recreación de base');

        PostgreSqlTestSafetyGuard::assertForbiddenDatabaseTraitsNotPresent([
            RefreshDatabase::class => RefreshDatabase::class,
        ]);
    }

    public function test_rejects_database_migrations_trait(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage(DatabaseMigrations::class);
        $this->expectExceptionMessage('pgsql_owner');

        PostgreSqlTestSafetyGuard::assertForbiddenDatabaseTraitsNotPresent([
            DatabaseMigrations::class => DatabaseMigrations::class,
        ]);
    }

    public function test_rejects_database_truncation_trait(): void
    {
        $this->expectException(PostgreSqlTestSafetyException::class);
        $this->expectExceptionMessage(DatabaseTruncation::class);
        $this->expectExceptionMessage('trait propio aprobado');

        PostgreSqlTestSafetyGuard::assertForbiddenDatabaseTraitsNotPresent([
            DatabaseTruncation::class => DatabaseTruncation::class,
        ]);
    }

    public function test_sanitizes_pgsql_inspection_failure_without_sensitive_details(): void
    {
        $sensitive = 'password=SuperSecretPass host=db.internal.example DSN=pgsql:host=10.0.0.5;dbname=x SQL: select * from secret SQLSTATE[08006]';

        $inspector = new class($sensitive) implements PostgreSqlContextInspector
        {
            public function __construct(private readonly string $sensitive)
            {
            }

            public function inspect(string $connectionName): PostgreSqlConnectionContext
            {
                if ($connectionName === 'pgsql') {
                    throw new RuntimeException($this->sensitive);
                }

                throw new RuntimeException('unexpected connection: '.$connectionName);
            }
        };

        try {
            PostgreSqlTestSafetyGuard::enforceUsingApplication(
                $this->makeSafeConfigApplication(),
                $inspector,
            );
            $this->fail('Se esperaba PostgreSqlTestSafetyException.');
        } catch (PostgreSqlTestSafetyException $exception) {
            $this->assertSame(
                'No fue posible validar la conexión PostgreSQL [pgsql].',
                $exception->getMessage()
            );
            $this->assertStringNotContainsString('SuperSecretPass', $exception->getMessage());
            $this->assertStringNotContainsString('password=', $exception->getMessage());
            $this->assertStringNotContainsString('db.internal.example', $exception->getMessage());
            $this->assertStringNotContainsString('DSN=', $exception->getMessage());
            $this->assertStringNotContainsString('SQL:', $exception->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        } catch (Throwable $exception) {
            $this->fail('Excepción inesperada: '.$exception::class.' — '.$exception->getMessage());
        }
    }

    public function test_sanitizes_pgsql_owner_inspection_failure_without_sensitive_details(): void
    {
        $sensitive = 'password=OwnerSecretPass host=owner.internal.example DSN=pgsql:host=10.0.0.9;dbname=y SQL: select * from owner_secret SQLSTATE[28P01]';

        $inspector = new class($sensitive, $this->validAppContext()) implements PostgreSqlContextInspector
        {
            public function __construct(
                private readonly string $sensitive,
                private readonly PostgreSqlConnectionContext $appContext,
            ) {
            }

            public function inspect(string $connectionName): PostgreSqlConnectionContext
            {
                if ($connectionName === 'pgsql') {
                    return $this->appContext;
                }

                if ($connectionName === 'pgsql_owner') {
                    throw new RuntimeException($this->sensitive);
                }

                throw new RuntimeException('unexpected connection: '.$connectionName);
            }
        };

        try {
            PostgreSqlTestSafetyGuard::enforceUsingApplication(
                $this->makeSafeConfigApplication(),
                $inspector,
            );
            $this->fail('Se esperaba PostgreSqlTestSafetyException.');
        } catch (PostgreSqlTestSafetyException $exception) {
            $this->assertSame(
                'No fue posible validar la conexión PostgreSQL [pgsql_owner].',
                $exception->getMessage()
            );
            $this->assertStringNotContainsString('OwnerSecretPass', $exception->getMessage());
            $this->assertStringNotContainsString('password=', $exception->getMessage());
            $this->assertStringNotContainsString('owner.internal.example', $exception->getMessage());
            $this->assertStringNotContainsString('DSN=', $exception->getMessage());
            $this->assertStringNotContainsString('SQL:', $exception->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        } catch (Throwable $exception) {
            $this->fail('Excepción inesperada: '.$exception::class.' — '.$exception->getMessage());
        }
    }

    private function makeSafeConfigApplication(): Application
    {
        $config = $this->createStub(ConfigRepository::class);
        $config->method('get')->willReturnCallback(function (string $key) {
            return match ($key) {
                'app.env' => PostgreSqlTestSafetyGuard::EXPECTED_ENVIRONMENT,
                'database.default' => PostgreSqlTestSafetyGuard::EXPECTED_DEFAULT_CONNECTION,
                'database.connections.pgsql.driver' => PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
                'database.connections.pgsql_owner.driver' => PostgreSqlTestSafetyGuard::EXPECTED_DRIVER,
                default => null,
            };
        });

        $app = $this->createStub(Application::class);
        $app->method('make')->willReturnCallback(function (string $abstract) use ($config) {
            if ($abstract === 'config') {
                return $config;
            }

            throw new RuntimeException('make() inesperado en prueba unitaria: '.$abstract);
        });

        return $app;
    }

    private function validAppContext(
        ?string $database = null,
        ?string $user = null,
        ?string $schema = null,
        ?string $searchPath = null,
        ?string $schemasText = null,
    ): PostgreSqlConnectionContext {
        return new PostgreSqlConnectionContext(
            database: $database ?? PostgreSqlTestSafetyGuard::EXPECTED_DATABASE,
            user: $user ?? PostgreSqlTestSafetyGuard::EXPECTED_APP_USER,
            schema: $schema ?? PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA,
            searchPath: $searchPath ?? PostgreSqlTestSafetyGuard::EXPECTED_SEARCH_PATH,
            schemasText: $schemasText ?? PostgreSqlTestSafetyGuard::EXPECTED_SCHEMAS_TEXT,
        );
    }

    private function validOwnerContext(
        ?string $database = null,
        ?string $user = null,
        ?string $schema = null,
        ?string $searchPath = null,
        ?string $schemasText = null,
    ): PostgreSqlConnectionContext {
        return new PostgreSqlConnectionContext(
            database: $database ?? PostgreSqlTestSafetyGuard::EXPECTED_DATABASE,
            user: $user ?? PostgreSqlTestSafetyGuard::EXPECTED_OWNER_USER,
            schema: $schema ?? PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA,
            searchPath: $searchPath ?? PostgreSqlTestSafetyGuard::EXPECTED_SEARCH_PATH,
            schemasText: $schemasText ?? PostgreSqlTestSafetyGuard::EXPECTED_SCHEMAS_TEXT,
        );
    }
}
