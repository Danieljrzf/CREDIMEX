<?php

namespace Tests\Feature\Support\Migrations;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Migrations\OwnerAwareMigrationInspection;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;
use Tests\TestCase;

/**
 * SERIAL ONLY: comparte credimex_test, el schema credimex y la tabla migrations.
 */
#[Group('owner-migrations-serial')]
final class OwnerAwareMigrationHarnessIntegrationTest extends TestCase
{
    private const FIXTURE_TABLE = 'zz_test_owner_migration_harness';

    private const FIXTURE_MIGRATION = '0000_00_00_000000_create_zz_test_owner_migration_harness_table';

    public function test_fixture_runs_with_owner_and_leaves_no_persistent_residue(): void
    {
        $database = $this->app->make('db');
        $owner = $database->connection(OwnerAwareMigrationTestHarness::OWNER_CONNECTION);
        $inspection = new OwnerAwareMigrationInspection($owner);
        $fixture = base_path('tests/Fixtures/migrations/'.self::FIXTURE_MIGRATION.'.php');
        $initialDefaultConnection = $database->getDefaultConnection();
        $initialTransactionLevel = $owner->transactionLevel();

        $this->assertSame('pgsql', $initialDefaultConnection);
        $this->assertSame(0, $initialTransactionLevel);
        $this->assertFalse($inspection->tableExists(self::FIXTURE_TABLE));
        $this->assertFalse($inspection->migrationIsRegistered(self::FIXTURE_MIGRATION));

        OwnerAwareMigrationTestHarness::fromApplication($this->app)
            ->assertReady()
            ->runFile(
                absolutePath: $fixture,
                expectedAbsentTables: [self::FIXTURE_TABLE],
                assertions: function (OwnerAwareMigrationInspection $duringUp): void {
                    $this->assertTrue($duringUp->tableExists(self::FIXTURE_TABLE));
                    $this->assertTrue($duringUp->migrationIsRegistered(self::FIXTURE_MIGRATION));

                    $metadata = $duringUp->tableMetadata(self::FIXTURE_TABLE);

                    $this->assertNotNull($metadata);
                    $this->assertSame('credimex', $metadata->schema_name);
                    $this->assertSame('credimex_test_owner', $metadata->owner_name);

                    $this->assertTrue($duringUp->hasIdentityPrimaryKey(self::FIXTURE_TABLE));
                    $this->assertTrue($duringUp->appHasTablePrivilege(self::FIXTURE_TABLE, 'SELECT'));
                    $this->assertFalse($duringUp->appHasTablePrivilege(self::FIXTURE_TABLE, 'INSERT'));
                    $this->assertFalse($duringUp->appHasTablePrivilege(self::FIXTURE_TABLE, 'UPDATE'));
                    $this->assertFalse($duringUp->appHasTablePrivilege(self::FIXTURE_TABLE, 'DELETE'));

                    foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE'] as $privilege) {
                        $this->assertFalse($duringUp->appHasTablePrivilege('migrations', $privilege));
                    }

                    $this->assertFalse($duringUp->appHasSequencePrivilege('migrations_id_seq', 'USAGE'));
                }
            );

        $this->assertFalse($inspection->tableExists(self::FIXTURE_TABLE));
        $this->assertFalse($inspection->migrationIsRegistered(self::FIXTURE_MIGRATION));
        $this->assertSame($initialDefaultConnection, $database->getDefaultConnection());
        $this->assertSame($initialTransactionLevel, $owner->transactionLevel());

        foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE'] as $privilege) {
            $this->assertFalse($inspection->appHasTablePrivilege('migrations', $privilege));
        }

        $this->assertFalse($inspection->appHasSequencePrivilege('migrations_id_seq', 'USAGE'));
    }
}
