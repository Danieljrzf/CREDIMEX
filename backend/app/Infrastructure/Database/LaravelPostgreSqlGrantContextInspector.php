<?php

namespace App\Infrastructure\Database;

use Illuminate\Database\DatabaseManager;
use Throwable;

final class LaravelPostgreSqlGrantContextInspector implements PostgreSqlGrantContextInspector
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    public function inspect(string $configuredAppRole): PostgreSqlGrantContextParts
    {
        $appConnection = $this->inspectConnection('pgsql');
        $ownerConnection = $this->inspectConnection('pgsql_owner');
        $role = $this->inspectRole($configuredAppRole);

        return new PostgreSqlGrantContextParts(
            appConnection: $appConnection,
            ownerConnection: $ownerConnection,
            role: $role,
        );
    }

    private function inspectConnection(string $connectionName): PostgreSqlConnectionSnapshot
    {
        try {
            $row = $this->db->connection($connectionName)->selectOne(
                "select current_database() as database_name,
                        current_user as database_user,
                        current_schema() as schema_name,
                        current_setting('search_path') as search_path,
                        current_schemas(false)::text as schemas_text"
            );
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                "No fue posible inspeccionar la conexión PostgreSQL [{$connectionName}]."
            );
        }

        if ($row === null) {
            throw new PostgreSqlGrantException(
                "La inspección de la conexión PostgreSQL [{$connectionName}] no devolvió filas."
            );
        }

        return new PostgreSqlConnectionSnapshot(
            database: (string) $row->database_name,
            user: (string) $row->database_user,
            schema: (string) $row->schema_name,
            searchPath: (string) $row->search_path,
            schemasText: (string) $row->schemas_text,
        );
    }

    private function inspectRole(string $roleName): PostgreSqlRoleSnapshot
    {
        try {
            $row = $this->db->connection('pgsql_owner')->selectOne(
                'select r.rolname as role_name,
                        r.rolcanlogin as can_login,
                        has_database_privilege(r.rolname, current_database(), \'CONNECT\') as has_connect
                 from pg_roles r
                 where r.rolname = ?',
                [$roleName]
            );
        } catch (Throwable) {
            throw new PostgreSqlGrantException(
                'No fue posible inspeccionar el rol receptor de privilegios.'
            );
        }

        if ($row === null) {
            return new PostgreSqlRoleSnapshot(
                name: $roleName,
                exists: false,
                canLogin: false,
                hasConnect: false,
            );
        }

        return new PostgreSqlRoleSnapshot(
            name: (string) $row->role_name,
            exists: true,
            canLogin: PostgreSqlBooleanConverter::toBool($row->can_login),
            hasConnect: PostgreSqlBooleanConverter::toBool($row->has_connect),
        );
    }
}
