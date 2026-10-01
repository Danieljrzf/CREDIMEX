<?php

namespace Tests\Feature\Clientes;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Migrations\OwnerAwareMigrationInspection;
use Tests\Support\Migrations\OwnerAwareMigrationTestHarness;
use Tests\TestCase;

/**
 * SERIAL ONLY: comparte credimex_test, el schema credimex y la tabla migrations.
 */
#[Group('owner-migrations-serial')]
final class ClienteNucleoTest extends TestCase
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
    ];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000002_create_permisos_table',
        '2026_09_16_000003_create_rol_permisos_table',
        '2026_09_16_000004_insert_initial_rbac_catalog',
        '2026_09_16_000005_create_usuarios_table',
        '2026_09_16_000006_create_dispositivos_table',
        '2026_09_16_000007_create_sesiones_token_table',
        '2026_09_16_000009_create_clientes_nucleo_tables',
    ];

    private const PASSWORD = 'clave-ficticia-clientes';

    private const RUTA_ID = '71717171-7171-4717-8717-717171717171';

    public function test_nucleo_de_clientes_registra_consulta_y_edita(): void
    {
        $paths = array_map(
            static fn (string $migration): string => database_path("migrations/{$migration}.php"),
            self::MIGRATIONS,
        );

        OwnerAwareMigrationTestHarness::fromApplication($this->app)
            ->runFiles(
                absolutePaths: $paths,
                expectedAbsentTables: self::TABLES,
                assertions: function (OwnerAwareMigrationInspection $inspection): void {
                    config([
                        'credimex.pii.key_version' => 1,
                        'credimex.pii.encryption_keys' => [1 => base64_encode(random_bytes(32))],
                        'credimex.pii.hmac_keys' => [1 => base64_encode(random_bytes(32))],
                    ]);

                    $this->assertTrue($inspection->appHasTablePrivilege('clientes', 'SELECT'));
                    $this->assertTrue($inspection->appHasTablePrivilege('clientes', 'INSERT'));
                    $this->assertTrue($inspection->appHasTablePrivilege('clientes', 'UPDATE'));
                    $this->assertFalse($inspection->appHasTablePrivilege('clientes', 'DELETE'));
                    $this->assertTrue($inspection->appHasTablePrivilege('rutas', 'SELECT'));
                    $this->assertFalse($inspection->appHasTablePrivilege('rutas', 'INSERT'));
                    $this->assertTrue($inspection->appHasSequencePrivilege('clientes_id_seq', 'USAGE'));

                    $this->seedFixtures();

                    $this->jsonConToken('GET', '/api/clientes')->assertUnauthorized();

                    $sinPermiso = $this->tokenDe('usuario_sin_clientes', 'dispositivo-sin-clientes');
                    $this->jsonConToken('GET', '/api/clientes', $sinPermiso)->assertForbidden();

                    $cobrador = $this->tokenDe('usuario_cliente_cobrador', 'dispositivo-cliente-cobrador');
                    $this->jsonConToken('POST', '/api/clientes', $cobrador, [])->assertUnprocessable();
                    $incompleto = $this->alta();
                    $incompleto['telefono_principal'] = '12-3';
                    $this->jsonConToken('POST', '/api/clientes', $cobrador, $incompleto)->assertUnprocessable();

                    $creado = $this->jsonConToken('POST', '/api/clientes', $cobrador, $this->alta());
                    $creado->assertCreated();
                    $idPublico = (string) $creado->json('id_publico');
                    $this->assertSame('7', $idPublico[14]);
                    $this->assertSame('7221234567', $creado->json('telefono_principal'));
                    $this->assertSame(self::RUTA_ID, $creado->json('ruta_id_publico'));
                    $this->assertSame('Calle 1', $creado->json('domicilio.direccion'));
                    $this->assertSame('Marta Díaz', $creado->json('referencia.nombre'));
                    $this->assertSame('7221112233', $creado->json('contacto_alternativo.telefono'));
                    $this->assertSinSecretos($creado);

                    $clienteId = (int) DB::table('clientes')->where('id_publico', $idPublico)->value('id');
                    $fila = DB::selectOne(
                        'select encode(telefono_principal_cifrado, \'escape\') as cifrado,
                                telefono_principal_ultimos4 as ultimos,
                                octet_length(telefono_principal_hmac) as hmac_len
                         from clientes where id = ?',
                        [$clienteId],
                    );
                    $this->assertStringNotContainsString('7221234567', (string) $fila->cifrado);
                    $this->assertSame('4567', $fila->ultimos);
                    $this->assertSame(32, (int) $fila->hmac_len);
                    $this->assertSame(1, DB::table('referencias')->where('cliente_id', $clienteId)->count());
                    $this->assertSame(1, DB::table('domicilios_ubicaciones')->where('cliente_id', $clienteId)->where('vigente', true)->count());
                    $this->assertSame(
                        1,
                        DB::table('asignaciones_cliente_ruta')->where('cliente_id', $clienteId)->where('estado', 'VIGENTE')->count(),
                    );

                    $listado = $this->jsonConToken('GET', '/api/clientes', $cobrador);
                    $listado->assertOk();
                    $listado->assertJsonPath('0.id_publico', $idPublico);
                    $listado->assertJsonPath('0.telefono_principal', '7221234567');
                    $this->assertSinSecretos($listado);

                    $detalle = $this->jsonConToken('GET', '/api/clientes/'.$idPublico, $cobrador);
                    $detalle->assertOk();
                    $detalle->assertJsonPath('id_publico', $idPublico);
                    $detalle->assertJsonPath('telefono_principal', '7221234567');
                    $this->assertSinSecretos($detalle);

                    $editado = $this->jsonConToken('PATCH', '/api/clientes/'.$idPublico, $cobrador, [
                        'nombre' => 'Ana López Editada',
                        'telefono_principal' => '722 000 11 22',
                    ]);
                    $editado->assertOk();
                    $editado->assertJsonPath('nombre', 'Ana López Editada');
                    $editado->assertJsonPath('telefono_principal', '7220001122');
                    $this->assertSinSecretos($editado);
                    $ultimo = DB::table('clientes')->where('id', $clienteId)->value('telefono_principal_ultimos4');
                    $this->assertSame('1122', $ultimo);

                    $antes = DB::table('clientes')->count();
                    $contactos = DB::table('contactos_alternativos')->count();
                    DB::unprepared(<<<'SQL'
                        CREATE FUNCTION credimex.fallar_insercion_referencia()
                        RETURNS trigger
                        LANGUAGE plpgsql
                        AS $$
                        BEGIN
                            RAISE EXCEPTION 'fallo controlado';
                        END;
                        $$;

                        CREATE TRIGGER trg_fallar_insercion_referencia
                        BEFORE INSERT ON credimex.referencias
                        FOR EACH ROW
                        EXECUTE FUNCTION credimex.fallar_insercion_referencia();
                        SQL);

                    try {
                        $fallo = $this->alta('7225556677', 'Otra Persona Distinta');
                        $fallo['domicilio']['direccion'] = 'Calle sin persistir';
                        $fallo['contacto_alternativo']['nombre'] = 'Contacto Nuevo';
                        $fallo['contacto_alternativo']['telefono'] = '7225550001';
                        $fallo['referencia']['nombre'] = 'Referencia Nueva';
                        $fallo['referencia']['telefono'] = '7225550002';
                        $this->jsonConToken('POST', '/api/clientes', $cobrador, $fallo)->assertStatus(500);
                        $this->assertSame($antes, DB::table('clientes')->count());
                        $this->assertSame($contactos, DB::table('contactos_alternativos')->count());
                    } finally {
                        DB::unprepared(<<<'SQL'
                            DROP TRIGGER IF EXISTS trg_fallar_insercion_referencia ON credimex.referencias;
                            DROP FUNCTION IF EXISTS credimex.fallar_insercion_referencia();
                            SQL);
                    }
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

    private function assertSinSecretos(TestResponse $response): void
    {
        $contenido = (string) $response->getContent();
        $this->assertStringNotContainsString('telefono_principal_hmac', $contenido);
        $this->assertStringNotContainsString('telefono_principal_cifrado', $contenido);
        $this->assertStringNotContainsString('telefono_cifrado', $contenido);
        $this->assertStringNotContainsString('telefono_hmac', $contenido);
        $this->assertStringNotContainsString('version_clave', $contenido);

        $json = $response->json();
        $cliente = array_is_list($json) ? $json[0] : $json;
        $this->assertArrayNotHasKey('id', $cliente);
    }

    /**
     * @return array<string, mixed>
     */
    private function alta(string $telefono = '(722) 123-45-67', string $nombre = 'Ana López'): array
    {
        return [
            'nombre' => $nombre,
            'telefono_principal' => $telefono,
            'observaciones' => 'Nota',
            'ruta_id_publico' => self::RUTA_ID,
            'contacto_alternativo' => [
                'nombre' => 'Luis López',
                'telefono' => '7221112233',
                'relacion' => 'hermano',
            ],
            'referencia' => [
                'nombre' => 'Marta Díaz',
                'telefono' => '7229998877',
                'relacion' => 'vecina',
            ],
            'domicilio' => [
                'direccion' => 'Calle 1',
                'latitud' => '19.432608',
                'longitud' => '-99.133209',
            ],
            'documentos' => [
                'ine' => 'privado/ine/ana',
                'comprobante_domicilio' => 'privado/comprobante/ana',
            ],
        ];
    }

    private function seedFixtures(): void
    {
        $timestamp = CarbonImmutable::now('UTC');
        $credencial = Hash::make(self::PASSWORD);

        DB::table('roles')->insert([
            'codigo' => 'sin_clientes',
            'nombre' => 'Sin clientes',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $this->insertUsuario(
            '61616161-6161-4616-8616-616161616161',
            'Cobrador clientes',
            'usuario_cliente_cobrador',
            'dispositivo-cliente-cobrador',
            'cobrador',
            $credencial,
            $timestamp,
        );
        $this->insertUsuario(
            '62626262-6262-4626-8626-626262626262',
            'Sin permiso clientes',
            'usuario_sin_clientes',
            'dispositivo-sin-clientes',
            'sin_clientes',
            $credencial,
            $timestamp,
        );

        DB::table('rutas')->insert([
            'id_publico' => self::RUTA_ID,
            'nombre' => 'Ruta centro',
            'estado' => 'ACTIVA',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
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

        DB::table('dispositivos')->insert([
            'usuario_id' => (int) DB::table('usuarios')->where('nombre_usuario', $nombreUsuario)->value('id'),
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
}
