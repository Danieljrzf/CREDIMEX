<?php

namespace Tests\Support;

use Illuminate\Database\DatabaseManager;

final class LaravelPostgreSqlContextInspector implements PostgreSqlContextInspector
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    public function inspect(string $connectionName): PostgreSqlConnectionContext
    {
        $row = $this->db->connection($connectionName)->selectOne(
            "select current_database() as database_name,
                    current_user as database_user,
                    current_schema() as schema_name,
                    current_setting('search_path') as search_path,
                    current_schemas(false)::text as schemas_text"
        );

        if ($row === null) {
            throw new PostgreSqlTestSafetyException(
                "La inspección de la conexión PostgreSQL [{$connectionName}] no devolvió filas."
            );
        }

        return new PostgreSqlConnectionContext(
            database: (string) $row->database_name,
            user: (string) $row->database_user,
            schema: (string) $row->schema_name,
            searchPath: (string) $row->search_path,
            schemasText: (string) $row->schemas_text,
        );
    }
}
