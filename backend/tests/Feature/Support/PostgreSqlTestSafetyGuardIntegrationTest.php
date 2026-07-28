<?php

namespace Tests\Feature\Support;

use Illuminate\Support\Facades\DB;
use Tests\Support\PostgreSqlTestSafetyGuard;
use Tests\TestCase;

final class PostgreSqlTestSafetyGuardIntegrationTest extends TestCase
{
    public function test_real_testing_environment_passes_the_safety_guard(): void
    {
        $appRow = DB::connection('pgsql')->selectOne(
            "select current_database() as database_name,
                    current_user as database_user,
                    current_schema() as schema_name,
                    current_setting('search_path') as search_path,
                    current_schemas(false)::text as schemas_text"
        );

        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_DATABASE, $appRow->database_name);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_APP_USER, $appRow->database_user);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $appRow->schema_name);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_SEARCH_PATH, $appRow->search_path);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_SCHEMAS_TEXT, $appRow->schemas_text);

        $ownerRow = DB::connection('pgsql_owner')->selectOne(
            "select current_database() as database_name,
                    current_user as database_user,
                    current_schema() as schema_name,
                    current_setting('search_path') as search_path,
                    current_schemas(false)::text as schemas_text"
        );

        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_DATABASE, $ownerRow->database_name);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_OWNER_USER, $ownerRow->database_user);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_SCHEMA, $ownerRow->schema_name);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_SEARCH_PATH, $ownerRow->search_path);
        $this->assertSame(PostgreSqlTestSafetyGuard::EXPECTED_SCHEMAS_TEXT, $ownerRow->schemas_text);
    }
}
