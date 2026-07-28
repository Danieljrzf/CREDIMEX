<?php

namespace Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Throwable;

final class PostgreSqlTestSafetyGuard
{
    public const EXPECTED_ENVIRONMENT = 'testing';

    public const EXPECTED_DEFAULT_CONNECTION = 'pgsql';

    public const EXPECTED_DRIVER = 'pgsql';

    public const EXPECTED_DATABASE = 'credimex_test';

    public const EXPECTED_APP_USER = 'credimex_test_app';

    public const EXPECTED_OWNER_USER = 'credimex_test_owner';

    public const EXPECTED_SCHEMA = 'credimex';

    public const EXPECTED_SEARCH_PATH = 'credimex';

    public const EXPECTED_SCHEMAS_TEXT = '{credimex}';

    /**
     * @param  array<class-string, class-string>  $usedTraits
     */
    public static function assertForbiddenDatabaseTraitsNotPresent(array $usedTraits): void
    {
        $forbidden = [
            RefreshDatabase::class,
            DatabaseMigrations::class,
            DatabaseTruncation::class,
        ];

        foreach ($forbidden as $trait) {
            if (isset($usedTraits[$trait])) {
                throw new PostgreSqlTestSafetyException(
                    "Trait de base de datos prohibido detectado: {$trait}. "
                    .'Los traits estándar de recreación de base (RefreshDatabase, DatabaseMigrations, DatabaseTruncation) '
                    .'están prohibidos en CREDIMEX. Las migraciones requieren la conexión pgsql_owner. '
                    .'Debe utilizarse en el futuro un trait propio aprobado.'
                );
            }
        }
    }

    public function assertEnvironmentIsSafe(
        string $environment,
        string $defaultConnection,
        ?string $pgsqlDriver,
        ?string $pgsqlOwnerDriver,
    ): void {
        if ($environment !== self::EXPECTED_ENVIRONMENT) {
            throw new PostgreSqlTestSafetyException(
                'El entorno Laravel debe ser exactamente "'.self::EXPECTED_ENVIRONMENT.'". '
                ."Valor observado: [{$environment}]."
            );
        }

        if ($defaultConnection !== self::EXPECTED_DEFAULT_CONNECTION) {
            throw new PostgreSqlTestSafetyException(
                'La conexión default debe ser exactamente "'.self::EXPECTED_DEFAULT_CONNECTION.'". '
                ."Valor observado: [{$defaultConnection}]."
            );
        }

        if ($pgsqlDriver !== self::EXPECTED_DRIVER) {
            throw new PostgreSqlTestSafetyException(
                'La conexión pgsql debe usar el driver "'.self::EXPECTED_DRIVER.'". '
                .'Valor observado: ['.($pgsqlDriver ?? 'null').'].'
            );
        }

        if ($pgsqlOwnerDriver !== self::EXPECTED_DRIVER) {
            throw new PostgreSqlTestSafetyException(
                'La conexión pgsql_owner debe usar el driver "'.self::EXPECTED_DRIVER.'". '
                .'Valor observado: ['.($pgsqlOwnerDriver ?? 'null').'].'
            );
        }
    }

    public function assertAppContext(PostgreSqlConnectionContext $context): void
    {
        $this->assertContextMatches(
            connectionLabel: 'pgsql',
            context: $context,
            expectedDatabase: self::EXPECTED_DATABASE,
            expectedUser: self::EXPECTED_APP_USER,
        );
    }

    public function assertOwnerContext(PostgreSqlConnectionContext $context): void
    {
        $this->assertContextMatches(
            connectionLabel: 'pgsql_owner',
            context: $context,
            expectedDatabase: self::EXPECTED_DATABASE,
            expectedUser: self::EXPECTED_OWNER_USER,
        );
    }

    public static function enforceUsingApplication(
        Application $app,
        ?PostgreSqlContextInspector $inspector = null,
    ): void {
        $config = $app->make('config');
        $guard = new self;

        $guard->assertEnvironmentIsSafe(
            (string) $config->get('app.env'),
            (string) $config->get('database.default'),
            $config->get('database.connections.pgsql.driver'),
            $config->get('database.connections.pgsql_owner.driver'),
        );

        $inspector ??= new LaravelPostgreSqlContextInspector($app->make('db'));

        try {
            $appContext = $inspector->inspect('pgsql');
        } catch (Throwable) {
            throw new PostgreSqlTestSafetyException(
                'No fue posible validar la conexión PostgreSQL [pgsql].'
            );
        }

        $guard->assertAppContext($appContext);

        try {
            $ownerContext = $inspector->inspect('pgsql_owner');
        } catch (Throwable) {
            throw new PostgreSqlTestSafetyException(
                'No fue posible validar la conexión PostgreSQL [pgsql_owner].'
            );
        }

        $guard->assertOwnerContext($ownerContext);
    }

    private function assertContextMatches(
        string $connectionLabel,
        PostgreSqlConnectionContext $context,
        string $expectedDatabase,
        string $expectedUser,
    ): void {
        if ($context->database !== $expectedDatabase) {
            throw new PostgreSqlTestSafetyException(
                "La conexión [{$connectionLabel}] debe usar la base \"{$expectedDatabase}\". "
                ."Valor observado: [{$context->database}]."
            );
        }

        if ($context->user !== $expectedUser) {
            throw new PostgreSqlTestSafetyException(
                "La conexión [{$connectionLabel}] debe usar el usuario \"{$expectedUser}\". "
                ."Valor observado: [{$context->user}]."
            );
        }

        if ($context->schema !== self::EXPECTED_SCHEMA) {
            throw new PostgreSqlTestSafetyException(
                "La conexión [{$connectionLabel}] debe usar el esquema \"".self::EXPECTED_SCHEMA.'". '
                ."Valor observado: [{$context->schema}]."
            );
        }

        if ($context->searchPath !== self::EXPECTED_SEARCH_PATH) {
            throw new PostgreSqlTestSafetyException(
                "La conexión [{$connectionLabel}] debe usar search_path \"".self::EXPECTED_SEARCH_PATH.'". '
                ."Valor observado: [{$context->searchPath}]."
            );
        }

        if ($context->schemasText !== self::EXPECTED_SCHEMAS_TEXT) {
            throw new PostgreSqlTestSafetyException(
                "La conexión [{$connectionLabel}] debe tener current_schemas(false) = \"".self::EXPECTED_SCHEMAS_TEXT.'". '
                ."Valor observado: [{$context->schemasText}]."
            );
        }
    }
}
