<?php

namespace Tests\Feature\Database\Migrations;

use App\Infrastructure\Database\PostgreSqlBooleanConverter;
use Illuminate\Database\Connection;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Migrations\OwnerAwareMigrationException;
use Tests\Support\Migrations\OwnerAwareMigrationInspection;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;
use Tests\TestCase;

/**
 * SERIAL ONLY: comparte credimex_test, el schema credimex y la tabla migrations.
 */
#[Group('owner-migrations-serial')]
final class RbacInitialDataMigrationTest extends TestCase
{
    private const TABLES = ['roles', 'permisos', 'rol_permisos'];

    private const DDL = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000002_create_permisos_table',
        '2026_09_16_000003_create_rol_permisos_table',
    ];

    private const DATA = '2026_09_16_000004_insert_initial_rbac_catalog';

    private const FIXTURE_ROLE = '0000_00_00_000010_insert_zz_test_rbac_preexisting_role';

    private const FIXTURE_PERMISSION = '0000_00_00_000011_insert_zz_test_rbac_preexisting_permission';

    private const FIXTURE_ALIEN = '0000_00_00_000012_insert_zz_test_rbac_alien_catalog';

    private const ROLE_CODES = ['administrador', 'cobrador', 'supervisor'];

    private const PERMISSIONS = [
        'clientes.consultar' => ['modulo' => 'clientes', 'descripcion' => 'Consultar clientes'],
        'clientes.registrar' => ['modulo' => 'clientes', 'descripcion' => 'Registrar clientes'],
        'clientes.editar' => ['modulo' => 'clientes', 'descripcion' => 'Editar clientes'],
        'creditos.consultar' => ['modulo' => 'creditos', 'descripcion' => 'Consultar créditos'],
        'creditos.autorizar' => ['modulo' => 'creditos', 'descripcion' => 'Autorizar créditos'],
        'creditos.renovar' => ['modulo' => 'creditos', 'descripcion' => 'Renovar créditos'],
        'pagos.registrar' => ['modulo' => 'pagos', 'descripcion' => 'Registrar pagos'],
        'caja.consultar' => ['modulo' => 'caja', 'descripcion' => 'Consultar caja'],
        'rutas.consultar' => ['modulo' => 'rutas', 'descripcion' => 'Consultar rutas'],
        'asignaciones.asignar' => ['modulo' => 'asignaciones', 'descripcion' => 'Asignar cobradores'],
        'asignaciones.reasignar' => ['modulo' => 'asignaciones', 'descripcion' => 'Reasignar cobradores'],
        'reportes.consultar' => ['modulo' => 'reportes', 'descripcion' => 'Consultar reportes'],
        'auditoria.consultar' => ['modulo' => 'auditoria', 'descripcion' => 'Consultar auditoría'],
    ];

    private const ROLE_NAMES = [
        'cobrador' => 'Cobrador',
        'supervisor' => 'Supervisor',
        'administrador' => 'Administrador',
    ];

    private const COBRADOR_PERMISOS = [
        'caja.consultar',
        'clientes.consultar',
        'clientes.editar',
        'clientes.registrar',
        'creditos.autorizar',
        'creditos.consultar',
        'creditos.renovar',
        'pagos.registrar',
        'rutas.consultar',
    ];

    public function test_loads_canonical_catalog_and_leaves_no_residue(): void
    {
        $this->runScenario(
            paths: $this->basePaths(),
            migrations: [...self::DDL, self::DATA],
            assertions: function (OwnerAwareMigrationInspection $inspection, Connection $owner): void {
                $this->assertCanonicalCatalog($owner);
                $this->assertPrivileges($inspection);
            },
        );
    }

    public function test_rejects_preexisting_canonical_role_before_writes(): void
    {
        $this->assertScenarioFailsOnDataUp(
            paths: $this->pathsWithFixture(self::FIXTURE_ROLE),
            migrations: [...self::DDL, self::FIXTURE_ROLE, self::DATA],
        );
    }

    public function test_rejects_preexisting_canonical_permission_before_writes(): void
    {
        $this->assertScenarioFailsOnDataUp(
            paths: $this->pathsWithFixture(self::FIXTURE_PERMISSION),
            migrations: [...self::DDL, self::FIXTURE_PERMISSION, self::DATA],
        );
    }

    public function test_preserves_alien_data_and_does_not_delete_it_on_down(): void
    {
        $this->runScenario(
            paths: $this->pathsWithFixture(self::FIXTURE_ALIEN),
            migrations: [...self::DDL, self::FIXTURE_ALIEN, self::DATA],
            assertions: function (OwnerAwareMigrationInspection $inspection, Connection $owner): void {
                $this->assertCanonicalCatalog($owner);
                $this->assertTrue($owner->table('roles')->where('codigo', 'prueba_ajeno')->exists());
                $this->assertTrue($owner->table('permisos')->where('codigo', 'prueba.consultar')->exists());
                $this->assertSame(4, $owner->table('roles')->count());
                $this->assertSame(14, $owner->table('permisos')->count());
                $this->assertSame(35, $owner->table('rol_permisos')->count());
                $this->assertNotEquals(
                    1,
                    (int) $owner->table('roles')->where('codigo', 'cobrador')->value('id'),
                );
                $this->assertPrivileges($inspection);
            },
        );
    }

    public function test_down_rejects_altered_canonical_row(): void
    {
        $this->assertScenarioFailsOnDataDown(
            paths: $this->basePaths(),
            migrations: [...self::DDL, self::DATA],
            mutate: function (Connection $owner): void {
                $owner->table('roles')->where('codigo', 'cobrador')->update([
                    'nombre' => 'Alterado',
                ]);
            },
        );
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $migrations
     * @param  callable(OwnerAwareMigrationInspection, Connection): void  $assertions
     */
    private function runScenario(array $paths, array $migrations, callable $assertions): void
    {
        [$database, $owner, $inspection, $initialDefault, $initialLevel, $initialMigrations] = $this->baseline();

        OwnerAwareMigrationTestHarness::fromApplication($this->app)->runFiles(
            absolutePaths: $paths,
            expectedAbsentTables: self::TABLES,
            assertions: function (OwnerAwareMigrationInspection $duringUp) use ($owner, $assertions): void {
                $assertions($duringUp, $owner);
            },
        );

        $this->assertNoResidue($inspection, $migrations, $database, $initialDefault, $initialLevel, $initialMigrations);
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $migrations
     */
    private function assertScenarioFailsOnDataUp(array $paths, array $migrations): void
    {
        [$database, $owner, $inspection, $initialDefault, $initialLevel, $initialMigrations] = $this->baseline();

        try {
            OwnerAwareMigrationTestHarness::fromApplication($this->app)->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (): void {
                    $this->fail('000004 no debía completar el up.');
                },
            );
            $this->fail('El escenario debía fallar en el up de 000004.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[up]', $exception->getMessage());
            $this->assertStringContainsString(self::DATA, $exception->getMessage());
        }

        $this->assertNoResidue($inspection, $migrations, $database, $initialDefault, $initialLevel, $initialMigrations);
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $migrations
     * @param  callable(Connection): void  $mutate
     */
    private function assertScenarioFailsOnDataDown(array $paths, array $migrations, callable $mutate): void
    {
        [$database, $owner, $inspection, $initialDefault, $initialLevel, $initialMigrations] = $this->baseline();

        try {
            OwnerAwareMigrationTestHarness::fromApplication($this->app)->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (OwnerAwareMigrationInspection $duringUp) use ($owner, $mutate): void {
                    $this->assertCanonicalCatalog($owner);
                    $this->assertPrivileges($duringUp);
                    $mutate($owner);
                },
            );
            $this->fail('El escenario debía fallar en el down de 000004.');
        } catch (OwnerAwareMigrationException $exception) {
            $this->assertStringContainsString('[down]', $exception->getMessage());
            $this->assertStringContainsString(self::DATA, $exception->getMessage());
        }

        $this->assertNoResidue($inspection, $migrations, $database, $initialDefault, $initialLevel, $initialMigrations);
    }

    private function assertCanonicalCatalog(Connection $owner): void
    {
        $roles = $owner->table('roles')->whereIn('codigo', self::ROLE_CODES)->orderBy('codigo')->get();
        $this->assertCount(3, $roles);

        $roleIds = [];

        foreach ($roles as $role) {
            $codigo = (string) $role->codigo;
            $this->assertSame(self::ROLE_NAMES[$codigo], $role->nombre);
            $this->assertTrue(PostgreSqlBooleanConverter::toBool($role->activo));
            $this->assertNotNull($role->created_at);
            $this->assertNotNull($role->updated_at);
            $roleIds[$codigo] = (int) $role->id;
            $this->assertGreaterThan(0, $roleIds[$codigo]);
        }

        $this->assertCount(3, array_unique(array_values($roleIds)));

        $permisos = $owner->table('permisos')->whereIn('codigo', array_keys(self::PERMISSIONS))->orderBy('codigo')->get();
        $this->assertCount(13, $permisos);

        $permisoIds = [];

        foreach ($permisos as $permiso) {
            $codigo = (string) $permiso->codigo;
            $this->assertSame(self::PERMISSIONS[$codigo]['modulo'], $permiso->modulo);
            $this->assertSame(self::PERMISSIONS[$codigo]['descripcion'], $permiso->descripcion);
            $this->assertTrue(PostgreSqlBooleanConverter::toBool($permiso->activo));
            $this->assertNotNull($permiso->created_at);
            $this->assertNotNull($permiso->updated_at);
            $permisoIds[$codigo] = (int) $permiso->id;
            $this->assertGreaterThan(0, $permisoIds[$codigo]);
        }

        $this->assertCount(13, array_unique(array_values($permisoIds)));

        $pairs = $owner->table('rol_permisos')
            ->join('roles', 'roles.id', '=', 'rol_permisos.rol_id')
            ->join('permisos', 'permisos.id', '=', 'rol_permisos.permiso_id')
            ->whereIn('roles.codigo', self::ROLE_CODES)
            ->get(['roles.codigo as rol', 'permisos.codigo as permiso', 'rol_permisos.created_at']);

        $this->assertCount(35, $pairs);

        $byRole = ['cobrador' => [], 'supervisor' => [], 'administrador' => []];

        foreach ($pairs as $pair) {
            $byRole[(string) $pair->rol][] = (string) $pair->permiso;
            $this->assertNotNull($pair->created_at);
        }

        sort($byRole['cobrador']);
        $this->assertSame(self::COBRADOR_PERMISOS, $byRole['cobrador']);
        $this->assertCount(13, array_unique($byRole['supervisor']));
        $this->assertCount(13, array_unique($byRole['administrador']));
        $this->assertEqualsCanonicalizing(array_keys(self::PERMISSIONS), $byRole['supervisor']);
        $this->assertEqualsCanonicalizing(array_keys(self::PERMISSIONS), $byRole['administrador']);

        $timestamps = [];

        foreach ($roles as $role) {
            $timestamps[] = (string) $role->created_at;
            $timestamps[] = (string) $role->updated_at;
        }

        foreach ($permisos as $permiso) {
            $timestamps[] = (string) $permiso->created_at;
            $timestamps[] = (string) $permiso->updated_at;
        }

        foreach ($pairs as $pair) {
            $timestamps[] = (string) $pair->created_at;
        }

        $this->assertCount(1, array_unique($timestamps));
    }

    /**
     * @return list<string>
     */
    private function basePaths(): array
    {
        return array_map(
            static fn (string $migration): string => database_path("migrations/{$migration}.php"),
            [...self::DDL, self::DATA],
        );
    }

    /**
     * @return list<string>
     */
    private function pathsWithFixture(string $fixture): array
    {
        return [
            database_path('migrations/'.self::DDL[0].'.php'),
            database_path('migrations/'.self::DDL[1].'.php'),
            database_path('migrations/'.self::DDL[2].'.php'),
            base_path("tests/Fixtures/migrations/{$fixture}.php"),
            database_path('migrations/'.self::DATA.'.php'),
        ];
    }

    /**
     * @return array{0: mixed, 1: Connection, 2: OwnerAwareMigrationInspection, 3: string, 4: int, 5: array<string, int>}
     */
    private function baseline(): array
    {
        $database = $this->app->make('db');
        $owner = $database->connection(OwnerAwareMigrationTestHarness::OWNER_CONNECTION);
        $inspection = new OwnerAwareMigrationInspection($owner);

        foreach (self::TABLES as $table) {
            $this->assertFalse($inspection->tableExists($table));
        }

        return [
            $database,
            $owner,
            $inspection,
            $database->getDefaultConnection(),
            $owner->transactionLevel(),
            $inspection->migrationBatches(),
        ];
    }

    /**
     * @param  list<string>  $migrations
     * @param  array<string, int>  $initialMigrations
     */
    private function assertNoResidue(
        OwnerAwareMigrationInspection $inspection,
        array $migrations,
        mixed $database,
        string $initialDefault,
        int $initialLevel,
        array $initialMigrations,
    ): void {
        foreach (self::TABLES as $table) {
            $this->assertFalse($inspection->tableExists($table));
        }

        foreach ($migrations as $migration) {
            $this->assertFalse($inspection->migrationIsRegistered($migration));
        }

        $this->assertSame($initialMigrations, $inspection->migrationBatches());
        $this->assertSame($initialDefault, $database->getDefaultConnection());
        $this->assertSame($initialLevel, $database->connection(OwnerAwareMigrationTestHarness::OWNER_CONNECTION)->transactionLevel());

        foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $privilege) {
            $this->assertFalse($inspection->appHasTablePrivilege('migrations', $privilege));
        }

        $this->assertFalse($inspection->appHasSequencePrivilege('migrations_id_seq', 'USAGE'));
    }

    private function assertPrivileges(OwnerAwareMigrationInspection $inspection): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue($inspection->appHasTablePrivilege($table, 'SELECT'));

            foreach (['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'] as $privilege) {
                $this->assertFalse($inspection->appHasTablePrivilege($table, $privilege));
            }

            $sequence = $inspection->identitySequence($table);
            $this->assertNotNull($sequence);
            $this->assertFalse($inspection->appHasSequencePrivilege($sequence, 'USAGE'));
        }
    }
}
