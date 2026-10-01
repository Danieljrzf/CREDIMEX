<?php

namespace Tests\Feature\Creditos;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;
use Tests\TestCase;

/**
 * SERIAL ONLY: comparte credimex_test, el schema credimex y la tabla migrations.
 */
#[Group('owner-migrations-serial')]
final class CreditoNucleoTest extends TestCase
{
    private const TABLES = [
        'roles',
        'permisos',
        'rol_permisos',
        'usuarios',
        'dispositivos',
        'sesiones_token',
        'clientes',
        'contactos_alternativos',
        'referencias',
        'domicilios_ubicaciones',
        'documentos_cliente',
        'confirmaciones_no_duplicado',
        'rutas',
        'asignaciones_cliente_ruta',
        'parametros_sistema',
        'planes_credito',
        'versiones_plan',
        'solicitudes_credito',
        'autorizaciones_credito',
        'creditos',
        'versiones_condiciones_credito',
    ];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000002_create_permisos_table',
        '2026_09_16_000003_create_rol_permisos_table',
        '2026_09_16_000005_create_usuarios_table',
        '2026_09_16_000006_create_dispositivos_table',
        '2026_09_16_000007_create_sesiones_token_table',
        '2026_09_16_000009_create_clientes_nucleo_tables',
        '2026_09_16_000010_create_creditos_nucleo_tables',
    ];

    private const PASSWORD = 'clave-ficticia-creditos';

    private const RUTA_ID = '81818181-8181-4818-8818-818181818181';

    private const CLIENTE_ID = '82828282-8282-4828-8828-828282828282';

    public function test_nucleo_de_creditos_autoriza_y_consulta(): void
    {
        $paths = array_map(
            static fn (string $migration): string => database_path("migrations/{$migration}.php"),
            self::MIGRATIONS,
        );

        OwnerAwareMigrationTestHarness::fromApplication($this->app)
            ->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (): void {
                    $this->seedFixtures();
                    $cobrador = $this->tokenDe('usuario_credito_cobrador', 'dispositivo-credito-cobrador');

                    $this->jsonConToken('GET', '/api/creditos')->assertUnauthorized();
                    $sinPermiso = $this->tokenDe('usuario_sin_creditos', 'dispositivo-sin-creditos');
                    $this->jsonConToken('GET', '/api/creditos', $sinPermiso)->assertForbidden();

                    $this->jsonConToken('POST', '/api/creditos', $cobrador, $this->alta(99900))->assertUnprocessable();

                    $creado = $this->jsonConToken('POST', '/api/creditos', $cobrador, $this->alta(100000));
                    $creado->assertCreated();
                    $creado->assertJsonPath('resultado', 'APROBADA');
                    $creado->assertJsonPath('credito.estado', 'PENDIENTE_DESEMBOLSO');
                    $creado->assertJsonPath('credito.principal_centavos', 100000);
                    $creado->assertJsonPath('credito.interes_centavos', 20000);
                    $creado->assertJsonPath('credito.comision_centavos', 20000);
                    $creado->assertJsonPath('credito.total_a_pagar_centavos', 120000);
                    $creado->assertJsonPath('credito.saldo_inicial_centavos', 120000);
                    $creado->assertJsonPath('credito.saldo_actual_centavos', 0);
                    $creado->assertJsonPath('credito.cuota_base_centavos', 6000);
                    $creado->assertJsonPath('credito.ruta_id_publico', self::RUTA_ID);
                    $this->assertNotSame(140000, $creado->json('credito.saldo_inicial_centavos'));
                    $idPublico = (string) $creado->json('credito.id_publico');
                    $this->assertSame('7', $idPublico[14]);
                    $this->assertSinIdentificadorInterno($creado->json('credito'));

                    $detalle = $this->jsonConToken('GET', '/api/creditos/'.$idPublico, $cobrador);
                    $detalle->assertOk();
                    $detalle->assertJsonPath('id_publico', $idPublico);
                    $detalle->assertJsonPath('comision_centavos', 20000);
                    $this->assertSinIdentificadorInterno($detalle->json());

                    $listado = $this->jsonConToken('GET', '/api/creditos', $cobrador);
                    $listado->assertOk();
                    $listado->assertJsonPath('0.id_publico', $idPublico);

                    $pendiente = $this->jsonConToken('POST', '/api/creditos', $cobrador, $this->alta(500000));
                    $pendiente->assertCreated();
                    $pendiente->assertJsonPath('resultado', 'PENDIENTE_AUTORIZACION');
                    $pendiente->assertJsonPath('credito', null);
                    $this->assertSame(1, DB::table('creditos')->count());

                    $this->jsonConToken('POST', '/api/creditos', $cobrador, $this->alta(100000, '99999999-9999-4999-8999-999999999999'))
                        ->assertUnprocessable();

                    $antesCreditos = DB::table('creditos')->count();
                    $antesSolicitudes = DB::table('solicitudes_credito')->count();
                    DB::unprepared(<<<'SQL'
                        CREATE FUNCTION credimex.fallar_insercion_credito()
                        RETURNS trigger
                        LANGUAGE plpgsql
                        AS $$
                        BEGIN
                            RAISE EXCEPTION 'fallo controlado';
                        END;
                        $$;

                        CREATE TRIGGER trg_fallar_insercion_credito
                        BEFORE INSERT ON credimex.creditos
                        FOR EACH ROW
                        EXECUTE FUNCTION credimex.fallar_insercion_credito();
                        SQL);

                    try {
                        $this->jsonConToken('POST', '/api/creditos', $cobrador, $this->alta(200000))->assertStatus(500);
                        $this->assertSame($antesCreditos, DB::table('creditos')->count());
                        $this->assertSame($antesSolicitudes, DB::table('solicitudes_credito')->count());
                    } finally {
                        DB::unprepared(<<<'SQL'
                            DROP TRIGGER IF EXISTS trg_fallar_insercion_credito ON credimex.creditos;
                            DROP FUNCTION IF EXISTS credimex.fallar_insercion_credito();
                            SQL);
                    }

                    $clienteId = (int) DB::table('clientes')->where('id_publico', self::CLIENTE_ID)->value('id');
                    $usuarioId = (int) DB::table('usuarios')->where('nombre_usuario', 'usuario_credito_cobrador')->value('id');
                    $versionPlanId = (int) DB::table('versiones_plan')->where('plazo_cuotas', 20)->value('id');

                    for ($i = 0; $i < 5; $i++) {
                        $this->insertarActivo($clienteId, $usuarioId, $versionPlanId, $i);
                    }

                    $this->jsonConToken('POST', '/api/creditos', $cobrador, $this->alta(100000))->assertUnprocessable();
                    $this->assertSame(5, DB::table('creditos')->where('estado', 'ACTIVO')->where('saldo_actual_centavos', '>', 0)->count());
                },
            );
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    private function jsonConToken(string $metodo, string $uri, ?string $token = null, array $cuerpo = []): TestResponse
    {
        Auth::forgetGuards();

        if ($token === null) {
            $this->flushHeaders();
        } else {
            $this->withToken($token);
        }

        return $this->json($metodo, $uri, $cuerpo);
    }

    /**
     * @param  array<string, mixed>  $cliente
     */
    private function assertSinIdentificadorInterno(array $cliente): void
    {
        $this->assertArrayNotHasKey('id', $cliente);
        $this->assertArrayNotHasKey('cliente_id', $cliente);
        $this->assertArrayNotHasKey('solicitud_credito_id', $cliente);
    }

    /**
     * @return array<string, mixed>
     */
    private function alta(int $monto, string $cliente = self::CLIENTE_ID): array
    {
        return [
            'cliente_id_publico' => $cliente,
            'monto_centavos' => $monto,
            'plazo_cuotas' => 20,
        ];
    }

    private function seedFixtures(): void
    {
        $timestamp = CarbonImmutable::now('UTC');
        $credencial = Hash::make(self::PASSWORD);

        foreach (['cobrador' => 'Cobrador', 'sin_creditos' => 'Sin creditos'] as $codigo => $nombre) {
            DB::table('roles')->insert([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $rolCobrador = (int) DB::table('roles')->where('codigo', 'cobrador')->value('id');

        foreach (['creditos.consultar', 'creditos.autorizar'] as $codigo) {
            DB::table('permisos')->insert([
                'codigo' => $codigo,
                'modulo' => 'creditos',
                'descripcion' => $codigo,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            DB::table('rol_permisos')->insert([
                'rol_id' => $rolCobrador,
                'permiso_id' => (int) DB::table('permisos')->where('codigo', $codigo)->value('id'),
                'created_at' => $timestamp,
            ]);
        }

        $this->insertUsuario('83838383-8383-4838-8838-838383838383', 'usuario_credito_cobrador', 'dispositivo-credito-cobrador', 'cobrador', $credencial, $timestamp);
        $this->insertUsuario('84848484-8484-4848-8848-848484848484', 'usuario_sin_creditos', 'dispositivo-sin-creditos', 'sin_creditos', $credencial, $timestamp);

        DB::table('rutas')->insert([
            'id_publico' => self::RUTA_ID,
            'nombre' => 'Ruta creditos',
            'estado' => 'ACTIVA',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $clienteId = $this->insertCliente(self::CLIENTE_ID, 'ACTIVO', $timestamp);
        DB::table('asignaciones_cliente_ruta')->insert([
            'cliente_id' => $clienteId,
            'ruta_id' => (int) DB::table('rutas')->where('id_publico', self::RUTA_ID)->value('id'),
            'estado' => 'VIGENTE',
            'vigente_desde' => $timestamp,
        ]);
    }

    private function insertUsuario(
        string $publicId,
        string $nombreUsuario,
        string $dispositivo,
        string $rolCodigo,
        string $credencial,
        CarbonImmutable $timestamp,
    ): void {
        DB::table('usuarios')->insert([
            'id_publico' => $publicId,
            'nombre' => $nombreUsuario,
            'nombre_usuario' => $nombreUsuario,
            'credencial_hash' => $credencial,
            'estado' => 'ACTIVO',
            'rol_id' => (int) DB::table('roles')->where('codigo', $rolCodigo)->value('id'),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('dispositivos')->insert([
            'usuario_id' => (int) DB::table('usuarios')->where('nombre_usuario', $nombreUsuario)->value('id'),
            'identificador_dispositivo' => $dispositivo,
            'estado' => 'ACTIVO',
        ]);
    }

    private function insertCliente(string $idPublico, string $estado, CarbonImmutable $timestamp): int
    {
        $fila = DB::selectOne(
            'insert into clientes (
                id_publico, nombre, telefono_principal_cifrado, telefono_principal_hmac,
                telefono_principal_ultimos4, telefono_principal_version_clave, estado, created_at, updated_at
             ) values (?, ?, decode(?, \'hex\'), decode(?, \'hex\'), \'0000\', 1, ?, ?, ?)
             returning id',
            [
                $idPublico,
                'Cliente credito',
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(32)),
                $estado,
                $timestamp,
                $timestamp,
            ],
        );

        return (int) $fila->id;
    }

    private function insertarActivo(int $clienteId, int $usuarioId, int $versionPlanId, int $indice): void
    {
        $solicitud = DB::selectOne(
            'insert into solicitudes_credito (
                id_publico, cliente_id, version_plan_id, monto_centavos, estado, solicitante_id
             ) values (?, ?, ?, 100000, \'APROBADA\', ?)
             returning id',
            [sprintf('85000000-0000-4000-8000-%012d', $indice), $clienteId, $versionPlanId, $usuarioId],
        );

        $credito = DB::selectOne(
            'insert into creditos (
                id_publico, cliente_id, solicitud_credito_id, estado,
                saldo_inicial_centavos, saldo_actual_centavos,
                dias_atraso_actual, semaforo_actual, cuotas_vencidas_pendientes
             ) values (?, ?, ?, \'ACTIVO\', 120000, 100, 0, \'VERDE\', 0)
             returning id',
            [sprintf('86000000-0000-4000-8000-%012d', $indice), $clienteId, (int) $solicitud->id],
        );

        DB::table('solicitudes_credito')->where('id', (int) $solicitud->id)->update([
            'credito_id' => (int) $credito->id,
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
}
