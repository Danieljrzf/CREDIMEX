<?php

namespace Tests\Feature\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;
use Tests\TestCase;

/**
 * SERIAL ONLY: comparte credimex_test, el schema credimex y la tabla migrations.
 */
#[Group('owner-migrations-serial')]
final class RbacAuthorizationTest extends TestCase
{
    private const TABLES = [
        'roles',
        'permisos',
        'rol_permisos',
        'usuarios',
        'dispositivos',
        'sesiones_token',
    ];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000002_create_permisos_table',
        '2026_09_16_000003_create_rol_permisos_table',
        '2026_09_16_000004_insert_initial_rbac_catalog',
        '2026_09_16_000005_create_usuarios_table',
        '2026_09_16_000006_create_dispositivos_table',
        '2026_09_16_000007_create_sesiones_token_table',
        '2026_09_16_000008_grant_auth_runtime_privileges',
    ];

    private const PASSWORD = 'clave-ficticia-rbac';

    private const CLIENTES = '/_prueba/rbac/clientes-consultar';

    private const AUDITORIA = '/_prueba/rbac/auditoria-consultar';

    private const INEXISTENTE = '/_prueba/rbac/no-existe';

    public function test_rbac_autoriza_por_relacion_explicita_de_rol_permisos(): void
    {
        $this->registrarRutasDePrueba();

        $paths = array_map(
            static fn (string $migration): string => database_path("migrations/{$migration}.php"),
            self::MIGRATIONS,
        );

        OwnerAwareMigrationTestHarness::fromApplication($this->app)
            ->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (): void {
                    $this->seedUsuariosDeCatalogo();

                    $this->getConToken(self::CLIENTES)->assertUnauthorized();

                    $cobrador = $this->tokenDe('usuario_rbac_cobrador', 'dispositivo-rbac-cobrador');
                    $this->getConToken(self::CLIENTES, $cobrador)->assertNoContent();
                    $this->assertProhibido($this->getConToken(self::AUDITORIA, $cobrador));

                    $supervisor = $this->tokenDe('usuario_rbac_supervisor', 'dispositivo-rbac-supervisor');
                    $this->getConToken(self::AUDITORIA, $supervisor)->assertNoContent();

                    $administrador = $this->tokenDe('usuario_rbac_administrador', 'dispositivo-rbac-administrador');
                    $this->getConToken(self::AUDITORIA, $administrador)->assertNoContent();

                    DB::table('roles')->where('codigo', 'cobrador')->update(['activo' => false]);
                    $this->assertProhibido($this->getConToken(self::CLIENTES, $cobrador));

                    DB::table('permisos')->where('codigo', 'auditoria.consultar')->update(['activo' => false]);
                    $this->assertProhibido($this->getConToken(self::AUDITORIA, $supervisor));

                    $this->assertProhibido($this->getConToken(self::INEXISTENTE, $administrador));

                    DB::table('roles')->where('codigo', 'cobrador')->update(['activo' => true]);
                    DB::table('permisos')->where('codigo', 'auditoria.consultar')->update(['activo' => true]);
                },
            );
    }

    private function registrarRutasDePrueba(): void
    {
        Route::middleware(['auth:api', 'permiso:clientes.consultar'])
            ->get(self::CLIENTES, static fn () => response()->noContent());
        Route::middleware(['auth:api', 'permiso:auditoria.consultar'])
            ->get(self::AUDITORIA, static fn () => response()->noContent());
        Route::middleware(['auth:api', 'permiso:no.existe'])
            ->get(self::INEXISTENTE, static fn () => response()->noContent());
    }

    private function seedUsuariosDeCatalogo(): void
    {
        $timestamp = CarbonImmutable::now('UTC');
        $credencial = Hash::make(self::PASSWORD);

        $this->insertUsuario(
            '51515151-5151-4515-8515-515151515151',
            'Usuario rbac cobrador',
            'usuario_rbac_cobrador',
            'dispositivo-rbac-cobrador',
            'cobrador',
            $credencial,
            $timestamp,
        );
        $this->insertUsuario(
            '52525252-5252-4525-8525-525252525252',
            'Usuario rbac supervisor',
            'usuario_rbac_supervisor',
            'dispositivo-rbac-supervisor',
            'supervisor',
            $credencial,
            $timestamp,
        );
        $this->insertUsuario(
            '53535353-5353-4535-8535-535353535353',
            'Usuario rbac administrador',
            'usuario_rbac_administrador',
            'dispositivo-rbac-administrador',
            'administrador',
            $credencial,
            $timestamp,
        );
    }

    private function insertUsuario(
        string $publicId,
        string $nombre,
        string $nombreUsuario,
        string $dispositivo,
        string $rolCodigo,
        string $credencial,
        CarbonImmutable $timestamp,
    ): void {
        $rolId = DB::table('roles')->where('codigo', $rolCodigo)->value('id');
        $this->assertNotNull($rolId);

        DB::table('usuarios')->insert([
            'id_publico' => $publicId,
            'nombre' => $nombre,
            'nombre_usuario' => $nombreUsuario,
            'credencial_hash' => $credencial,
            'estado' => 'ACTIVO',
            'rol_id' => $rolId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $usuarioId = (int) DB::table('usuarios')->where('nombre_usuario', $nombreUsuario)->value('id');

        DB::table('dispositivos')->insert([
            'usuario_id' => $usuarioId,
            'identificador_dispositivo' => $dispositivo,
            'estado' => 'ACTIVO',
        ]);
    }

    private function tokenDe(string $nombreUsuario, string $dispositivo): string
    {
        Auth::forgetGuards();
        $this->flushHeaders();

        $response = $this->postJson('/api/auth/login', [
            'nombre_usuario' => $nombreUsuario,
            'password' => self::PASSWORD,
            'identificador_dispositivo' => $dispositivo,
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }

    private function getConToken(string $uri, ?string $token = null): TestResponse
    {
        Auth::forgetGuards();

        if ($token === null) {
            $this->flushHeaders();
        } else {
            $this->withToken($token);
        }

        return $this->getJson($uri);
    }

    private function assertProhibido(TestResponse $response): void
    {
        $response->assertForbidden();
        $this->assertSame(['message' => 'Prohibido.'], $response->json());
    }
}
