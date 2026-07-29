<?php

namespace App\Infrastructure\Database;

use Illuminate\Contracts\Foundation\Application;
use Throwable;

final class PostgreSqlGrantManager
{
    public const OWNER_CONNECTION = 'pgsql_owner';

    public const APP_CONNECTION = 'pgsql';

    public const EXPECTED_DEFAULT_CONNECTION = 'pgsql';

    public const EXPECTED_DRIVER = 'pgsql';

    public const EXPECTED_SCHEMA = 'credimex';

    public const EXPECTED_SEARCH_PATH = 'credimex';

    public const EXPECTED_SCHEMAS_TEXT = '{credimex}';

    public const ROLE_NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public const OBJECT_NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    /** @var list<string> */
    public const ALLOWED_TABLE_PRIVILEGES = ['DELETE', 'INSERT', 'SELECT', 'UPDATE'];

    /** @var list<string> */
    public const ALLOWED_SEQUENCE_PRIVILEGES = ['USAGE'];

    /** @var list<string> */
    private const FORBIDDEN_OBJECTS = [
        'migrations',
        'migrations_id_seq',
    ];

    /**
     * @param  array<string, array{database?: mixed, owner_user?: mixed, app_user?: mixed}>  $environmentCatalog
     */
    public function __construct(
        private readonly PostgreSqlGrantContextInspector $inspector,
        private readonly PostgreSqlIdentifierQuoter $quoter,
        private readonly PostgreSqlGrantSqlExecutor $executor,
        private readonly string $environment,
        private readonly string $defaultConnection,
        private readonly ?string $pgsqlDriver,
        private readonly ?string $pgsqlOwnerDriver,
        private readonly ?string $configuredAppRole,
        private readonly string $schema,
        private readonly array $environmentCatalog,
    ) {
    }

    public static function fromApplication(Application $app): self
    {
        $config = $app->make('config');
        $db = $app->make('db');

        /** @var array<string, array{database?: mixed, owner_user?: mixed, app_user?: mixed}> $catalog */
        $catalog = $config->get('credimex.database.environments', []);

        return new self(
            inspector: new LaravelPostgreSqlGrantContextInspector($db),
            quoter: new LaravelPostgreSqlIdentifierQuoter($db),
            executor: new LaravelPostgreSqlGrantSqlExecutor($db),
            environment: (string) $config->get('app.env'),
            defaultConnection: (string) $config->get('database.default'),
            pgsqlDriver: $config->get('database.connections.pgsql.driver'),
            pgsqlOwnerDriver: $config->get('database.connections.pgsql_owner.driver'),
            configuredAppRole: $config->get('credimex.database.app_role'),
            schema: (string) $config->get('credimex.database.schema', self::EXPECTED_SCHEMA),
            environmentCatalog: $catalog,
        );
    }

    public function assertSafeContext(): PostgreSqlGrantContext
    {
        $this->assertConfigurationIsSafe();

        $appRole = (string) $this->configuredAppRole;
        $expected = $this->resolveEnvironmentExpectations();

        try {
            $parts = $this->inspector->inspect($appRole);
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                'No fue posible validar el contexto PostgreSQL para privilegios.'
            );
        }

        $context = new PostgreSqlGrantContext(
            environment: $this->environment,
            defaultConnection: $this->defaultConnection,
            pgsqlDriver: $this->pgsqlDriver,
            pgsqlOwnerDriver: $this->pgsqlOwnerDriver,
            configuredAppRole: $appRole,
            expectedDatabase: (string) $expected['database'],
            expectedAppUser: (string) $expected['app_user'],
            expectedOwnerUser: (string) $expected['owner_user'],
            expectedSchema: self::EXPECTED_SCHEMA,
            appConnection: $parts->appConnection,
            ownerConnection: $parts->ownerConnection,
            role: $parts->role,
        );

        $this->assertContextMatchesExpectations($context);

        return $context;
    }

    /**
     * @param  list<string>  $privileges
     */
    public function grantTable(string $table, array $privileges): void
    {
        $this->applyObjectPrivileges(
            operation: 'GRANT',
            objectType: 'TABLE',
            objectName: $table,
            privileges: $privileges,
            allowedPrivileges: self::ALLOWED_TABLE_PRIVILEGES,
        );
    }

    /**
     * @param  list<string>  $privileges
     */
    public function revokeTable(string $table, array $privileges): void
    {
        $this->applyObjectPrivileges(
            operation: 'REVOKE',
            objectType: 'TABLE',
            objectName: $table,
            privileges: $privileges,
            allowedPrivileges: self::ALLOWED_TABLE_PRIVILEGES,
        );
    }

    /**
     * @param  list<string>  $privileges
     */
    public function grantSequence(string $sequence, array $privileges): void
    {
        $this->applyObjectPrivileges(
            operation: 'GRANT',
            objectType: 'SEQUENCE',
            objectName: $sequence,
            privileges: $privileges,
            allowedPrivileges: self::ALLOWED_SEQUENCE_PRIVILEGES,
        );
    }

    /**
     * @param  list<string>  $privileges
     */
    public function revokeSequence(string $sequence, array $privileges): void
    {
        $this->applyObjectPrivileges(
            operation: 'REVOKE',
            objectType: 'SEQUENCE',
            objectName: $sequence,
            privileges: $privileges,
            allowedPrivileges: self::ALLOWED_SEQUENCE_PRIVILEGES,
        );
    }

    /**
     * @param  list<string>  $privileges
     * @param  list<string>  $allowedPrivileges
     */
    private function applyObjectPrivileges(
        string $operation,
        string $objectType,
        string $objectName,
        array $privileges,
        array $allowedPrivileges,
    ): void {
        $this->assertObjectNameIsSafe($objectName, $objectType);
        $normalizedPrivileges = $this->normalizePrivileges($privileges, $allowedPrivileges, $objectType);
        $context = $this->assertSafeContext();

        try {
            $quotedSchema = $this->quoter->quoteIdent(self::EXPECTED_SCHEMA);
            $quotedObject = $this->quoter->quoteIdent($objectName);
            $quotedRole = $this->quoter->quoteIdent($context->configuredAppRole);
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                "No fue posible delimitar identificadores para la operación {$operation} sobre {$objectType}."
            );
        }

        $sql = $this->composePrivilegeSql(
            operation: $operation,
            objectType: $objectType,
            normalizedPrivileges: $normalizedPrivileges,
            quotedSchema: $quotedSchema,
            quotedObject: $quotedObject,
            quotedRole: $quotedRole,
        );

        try {
            $this->executor->execute($sql);
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                "No fue posible ejecutar {$operation} sobre {$objectType} mediante pgsql_owner."
            );
        }
    }

    /**
     * @param  list<string>  $normalizedPrivileges
     */
    private function composePrivilegeSql(
        string $operation,
        string $objectType,
        array $normalizedPrivileges,
        string $quotedSchema,
        string $quotedObject,
        string $quotedRole,
    ): string {
        $privilegeList = implode(', ', $normalizedPrivileges);
        $preposition = $operation === 'GRANT' ? 'TO' : 'FROM';

        return sprintf(
            '%s %s ON %s %s.%s %s %s',
            $operation,
            $privilegeList,
            $objectType,
            $quotedSchema,
            $quotedObject,
            $preposition,
            $quotedRole
        );
    }

    private function assertConfigurationIsSafe(): void
    {
        $expected = $this->resolveEnvironmentExpectations();

        if ($this->defaultConnection !== self::EXPECTED_DEFAULT_CONNECTION) {
            throw new PostgreSqlGrantException(
                'La conexión default debe ser exactamente "'.self::EXPECTED_DEFAULT_CONNECTION.'". '
                ."Valor observado: [{$this->defaultConnection}]."
            );
        }

        if ($this->pgsqlDriver !== self::EXPECTED_DRIVER) {
            throw new PostgreSqlGrantException(
                'La conexión pgsql debe usar el driver "'.self::EXPECTED_DRIVER.'".'
            );
        }

        if ($this->pgsqlOwnerDriver !== self::EXPECTED_DRIVER) {
            throw new PostgreSqlGrantException(
                'La conexión pgsql_owner debe usar el driver "'.self::EXPECTED_DRIVER.'".'
            );
        }

        if ($this->configuredAppRole === null || trim((string) $this->configuredAppRole) === '') {
            throw new PostgreSqlGrantException(
                'El rol de aplicación (credimex.database.app_role) no puede estar vacío.'
            );
        }

        $appRole = (string) $this->configuredAppRole;

        if (preg_match(self::ROLE_NAME_PATTERN, $appRole) !== 1) {
            throw new PostgreSqlGrantException(
                'El rol de aplicación tiene un formato inválido.'
            );
        }

        if ($appRole !== (string) $expected['app_user']) {
            throw new PostgreSqlGrantException(
                'El rol de aplicación no coincide con el entorno actual. '
                .'Se bloqueó un posible cruce entre desarrollo y testing.'
            );
        }

        if ($this->schema !== self::EXPECTED_SCHEMA) {
            throw new PostgreSqlGrantException(
                'El esquema configurado debe ser exactamente "'.self::EXPECTED_SCHEMA.'".'
            );
        }
    }

    /**
     * @return array{database: string, owner_user: string, app_user: string}
     */
    private function resolveEnvironmentExpectations(): array
    {
        if (! array_key_exists($this->environment, $this->environmentCatalog)) {
            throw new PostgreSqlGrantException(
                'El entorno Laravel no está permitido para operaciones de privilegios. '
                ."Valor observado: [{$this->environment}]."
            );
        }

        $entry = $this->environmentCatalog[$this->environment];

        if (! is_array($entry)) {
            throw new PostgreSqlGrantException(
                'La configuración del entorno de privilegios es incompleta o inválida.'
            );
        }

        foreach (['database', 'owner_user', 'app_user'] as $key) {
            if (! array_key_exists($key, $entry) || ! is_string($entry[$key]) || trim($entry[$key]) === '') {
                throw new PostgreSqlGrantException(
                    'La configuración del entorno de privilegios es incompleta o inválida.'
                );
            }
        }

        return [
            'database' => $entry['database'],
            'owner_user' => $entry['owner_user'],
            'app_user' => $entry['app_user'],
        ];
    }

    private function assertContextMatchesExpectations(PostgreSqlGrantContext $context): void
    {
        $this->assertConnectionSnapshot(
            label: self::APP_CONNECTION,
            snapshot: $context->appConnection,
            expectedDatabase: $context->expectedDatabase,
            expectedUser: $context->expectedAppUser,
        );

        $this->assertConnectionSnapshot(
            label: self::OWNER_CONNECTION,
            snapshot: $context->ownerConnection,
            expectedDatabase: $context->expectedDatabase,
            expectedUser: $context->expectedOwnerUser,
        );

        if (! $context->role->exists) {
            throw new PostgreSqlGrantException(
                'El rol receptor de privilegios no existe en PostgreSQL.'
            );
        }

        if (! $context->role->canLogin) {
            throw new PostgreSqlGrantException(
                'El rol receptor de privilegios no tiene LOGIN habilitado.'
            );
        }

        if (! $context->role->hasConnect) {
            throw new PostgreSqlGrantException(
                'El rol receptor de privilegios no tiene CONNECT sobre la base actual.'
            );
        }

        if ($context->role->name !== $context->configuredAppRole) {
            throw new PostgreSqlGrantException(
                'El rol inspeccionado no coincide con el rol de aplicación configurado.'
            );
        }

        if ($context->configuredAppRole !== $context->expectedAppUser) {
            throw new PostgreSqlGrantException(
                'El rol de aplicación no coincide con el entorno actual. '
                .'Se bloqueó un posible cruce entre desarrollo y testing.'
            );
        }

        if ($context->appConnection->user !== $context->expectedAppUser) {
            throw new PostgreSqlGrantException(
                'El current_user de pgsql no coincide con el app_user del entorno.'
            );
        }

        if ($context->role->name !== $context->appConnection->user) {
            throw new PostgreSqlGrantException(
                'El rol receptor debe coincidir con current_user de la conexión pgsql.'
            );
        }
    }

    private function assertConnectionSnapshot(
        string $label,
        PostgreSqlConnectionSnapshot $snapshot,
        string $expectedDatabase,
        string $expectedUser,
    ): void {
        if ($snapshot->database !== $expectedDatabase) {
            throw new PostgreSqlGrantException(
                "La conexión [{$label}] debe usar la base \"{$expectedDatabase}\". "
                ."Valor observado: [{$snapshot->database}]."
            );
        }

        if ($snapshot->user !== $expectedUser) {
            throw new PostgreSqlGrantException(
                "La conexión [{$label}] debe usar el usuario \"{$expectedUser}\". "
                ."Valor observado: [{$snapshot->user}]."
            );
        }

        if ($snapshot->schema !== self::EXPECTED_SCHEMA) {
            throw new PostgreSqlGrantException(
                "La conexión [{$label}] debe usar el esquema \"".self::EXPECTED_SCHEMA.'". '
                ."Valor observado: [{$snapshot->schema}]."
            );
        }

        if ($snapshot->searchPath !== self::EXPECTED_SEARCH_PATH) {
            throw new PostgreSqlGrantException(
                "La conexión [{$label}] debe usar search_path \"".self::EXPECTED_SEARCH_PATH.'". '
                ."Valor observado: [{$snapshot->searchPath}]."
            );
        }

        if ($snapshot->schemasText !== self::EXPECTED_SCHEMAS_TEXT) {
            throw new PostgreSqlGrantException(
                "La conexión [{$label}] debe tener current_schemas(false) = \"".self::EXPECTED_SCHEMAS_TEXT.'". '
                ."Valor observado: [{$snapshot->schemasText}]."
            );
        }
    }

    private function assertObjectNameIsSafe(string $objectName, string $objectType): void
    {
        if ($objectName !== strtolower($objectName)) {
            throw new PostgreSqlGrantException(
                "El nombre de {$objectType} debe estar en minúsculas y sin cualificar."
            );
        }

        if (str_contains($objectName, '.')
            || str_contains($objectName, '"')
            || str_contains($objectName, ' ')
            || str_contains($objectName, ';')
            || preg_match(self::OBJECT_NAME_PATTERN, $objectName) !== 1
        ) {
            throw new PostgreSqlGrantException(
                "El nombre de {$objectType} es inválido. Debe ser un identificador simple sin esquema."
            );
        }

        if (in_array($objectName, self::FORBIDDEN_OBJECTS, true)) {
            throw new PostgreSqlGrantException(
                "La operación sobre el objeto protegido [{$objectName}] está prohibida."
            );
        }
    }

    /**
     * @param  list<string>  $privileges
     * @param  list<string>  $allowedPrivileges
     * @return list<string>
     */
    private function normalizePrivileges(
        array $privileges,
        array $allowedPrivileges,
        string $objectType,
    ): array {
        if ($privileges === []) {
            throw new PostgreSqlGrantException(
                "La lista de privilegios para {$objectType} no puede estar vacía."
            );
        }

        $normalized = [];

        foreach ($privileges as $privilege) {
            if (! is_string($privilege) || $privilege === '' || str_contains($privilege, ',') || str_contains($privilege, ' ')) {
                throw new PostgreSqlGrantException(
                    "Privilegio inválido para {$objectType}: debe ser un privilegio simple."
                );
            }

            $upper = strtoupper($privilege);

            if ($upper === 'ALL') {
                throw new PostgreSqlGrantException(
                    "El privilegio ALL está prohibido para {$objectType}."
                );
            }

            if (! in_array($upper, $allowedPrivileges, true)) {
                throw new PostgreSqlGrantException(
                    "Privilegio no permitido para {$objectType}: [{$upper}]."
                );
            }

            $normalized[] = $upper;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);

        return $normalized;
    }
}
