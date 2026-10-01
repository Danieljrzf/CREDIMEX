<?php

namespace Tests\Feature\Auth;

use App\Infrastructure\Database\PostgreSqlBooleanConverter;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
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
final class ApiAuthenticationTest extends TestCase
{
    private const TABLES = ['roles', 'usuarios', 'dispositivos', 'sesiones_token'];

    private const MIGRATIONS = [
        '2026_09_16_000001_create_roles_table',
        '2026_09_16_000005_create_usuarios_table',
        '2026_09_16_000006_create_dispositivos_table',
        '2026_09_16_000007_create_sesiones_token_table',
        '2026_09_16_000008_grant_auth_runtime_privileges',
    ];

    private const ROLE_CODE = 'prueba_auth';

    private const PASSWORD = 'clave-ficticia-auth';

    private const ACTIVE_USER = 'usuario_auth_activo';

    private const BLOCKED_USER = 'usuario_auth_bloqueado';

    private const OTHER_USER = 'usuario_auth_ajeno';

    private const ACTIVE_DEVICE = 'dispositivo-auth-activo';

    private const REVOKED_DEVICE = 'dispositivo-auth-revocado';

    private const BLOCKED_DEVICE = 'dispositivo-auth-bloqueado';

    private const OTHER_DEVICE = 'dispositivo-auth-ajeno';

    private const ACTIVE_PUBLIC_ID = '41414141-4141-4414-8414-414141414141';

    public function test_api_authentication_login_bearer_logout_and_runtime_grants(): void
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
                    $ttlOriginal = config('credimex.auth.sesion_ttl_minutos');
                    $frozen = CarbonImmutable::parse('2026-09-30 15:00:00', 'UTC');
                    Carbon::setTestNow($frozen);
                    CarbonImmutable::setTestNow($frozen);

                    try {
                        $this->assertRuntimeGrants($inspection);
                        $this->assertSame(1440, $ttlOriginal);
                        config(['credimex.auth.sesion_ttl_minutos' => 90]);

                        $this->seedAuthFixtures();
                        $this->assertLoginRejectionsDoNotCreateSessions();
                        $this->assertSuccessfulLoginStoresHashedSession($frozen->addMinutes(90));
                    } finally {
                        Carbon::setTestNow();
                        CarbonImmutable::setTestNow();
                        config(['credimex.auth.sesion_ttl_minutos' => $ttlOriginal]);
                    }
                },
            );
    }

    private function assertRuntimeGrants(OwnerAwareMigrationInspection $inspection): void
    {
        $this->assertTrue($inspection->appHasTablePrivilege('sesiones_token', 'SELECT'));
        $this->assertTrue($inspection->appHasTablePrivilege('sesiones_token', 'INSERT'));
        $this->assertTrue($inspection->appHasTablePrivilege('sesiones_token', 'UPDATE'));
        $this->assertFalse($inspection->appHasTablePrivilege('sesiones_token', 'DELETE'));

        $sequence = $inspection->identitySequence('sesiones_token');
        $this->assertNotNull($sequence);
        $this->assertSame('sesiones_token_id_seq', $sequence);
        $this->assertTrue($inspection->appHasSequencePrivilege($sequence, 'USAGE'));

        foreach (['usuarios', 'dispositivos', 'roles'] as $table) {
            $this->assertTrue($inspection->appHasTablePrivilege($table, 'SELECT'));
            $this->assertFalse($inspection->appHasTablePrivilege($table, 'INSERT'));
            $this->assertFalse($inspection->appHasTablePrivilege($table, 'UPDATE'));
            $this->assertFalse($inspection->appHasTablePrivilege($table, 'DELETE'));
        }
    }

    private function seedAuthFixtures(): void
    {
        $timestamp = CarbonImmutable::now('UTC');
        $credencial = Hash::make(self::PASSWORD);

        DB::table('roles')->insert([
            'codigo' => self::ROLE_CODE,
            'nombre' => 'Rol de prueba auth',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $roleId = (int) DB::table('roles')->where('codigo', self::ROLE_CODE)->value('id');

        $this->insertUsuario(self::ACTIVE_PUBLIC_ID, 'Usuario auth activo', self::ACTIVE_USER, $credencial, $roleId, 'ACTIVO', $timestamp);
        $this->insertUsuario('42424242-4242-4424-8424-424242424242', 'Usuario auth bloqueado', self::BLOCKED_USER, $credencial, $roleId, 'BLOQUEADO', $timestamp);
        $this->insertUsuario('43434343-4343-4434-8434-434343434343', 'Usuario auth ajeno', self::OTHER_USER, $credencial, $roleId, 'ACTIVO', $timestamp);

        $activeUserId = $this->userId(self::ACTIVE_USER);
        $blockedUserId = $this->userId(self::BLOCKED_USER);
        $otherUserId = $this->userId(self::OTHER_USER);

        $this->insertDispositivo($activeUserId, self::ACTIVE_DEVICE, 'ACTIVO');
        $this->insertDispositivo($activeUserId, self::REVOKED_DEVICE, 'REVOCADO');
        $this->insertDispositivo($blockedUserId, self::BLOCKED_DEVICE, 'ACTIVO');
        $this->insertDispositivo($otherUserId, self::OTHER_DEVICE, 'ACTIVO');
    }

    private function assertLoginRejectionsDoNotCreateSessions(): void
    {
        $this->postAuth('/api/auth/login', [
            'nombre_usuario' => self::ACTIVE_USER,
            'identificador_dispositivo' => self::ACTIVE_DEVICE,
        ])->assertUnprocessable();

        $expected = null;

        foreach ([
            [self::ACTIVE_USER, 'clave-incorrecta', self::ACTIVE_DEVICE],
            [self::BLOCKED_USER, self::PASSWORD, self::BLOCKED_DEVICE],
            ['usuario_auth_inexistente', self::PASSWORD, self::ACTIVE_DEVICE],
            [self::ACTIVE_USER, self::PASSWORD, 'dispositivo-auth-inexistente'],
            [self::ACTIVE_USER, self::PASSWORD, self::REVOKED_DEVICE],
            [self::ACTIVE_USER, self::PASSWORD, self::OTHER_DEVICE],
        ] as [$nombreUsuario, $password, $dispositivo]) {
            $response = $this->postAuth('/api/auth/login', [
                'nombre_usuario' => $nombreUsuario,
                'password' => $password,
                'identificador_dispositivo' => $dispositivo,
            ]);

            $response->assertUnauthorized();
            $expected ??= $response->json();
            $this->assertSame($expected, $response->json());
        }

        $this->assertSame(['message' => 'No autorizado.'], $expected);
        $this->assertSame(0, DB::table('sesiones_token')->count());
    }

    private function assertSuccessfulLoginStoresHashedSession(CarbonImmutable $expiraEn): void
    {
        $first = $this->postAuth('/api/auth/login', [
            'nombre_usuario' => self::ACTIVE_USER,
            'password' => self::PASSWORD,
            'identificador_dispositivo' => self::ACTIVE_DEVICE,
        ]);

        $first->assertOk();
        $payload = $first->json();
        $token = $payload['token'];

        $this->assertSame(['token', 'expira_en', 'usuario', 'rol'], array_keys($payload));
        $this->assertSame(['id_publico', 'nombre', 'nombre_usuario'], array_keys($payload['usuario']));
        $this->assertSame(['codigo', 'nombre'], array_keys($payload['rol']));
        $this->assertSame(self::ACTIVE_PUBLIC_ID, $payload['usuario']['id_publico']);
        $this->assertSame('Usuario auth activo', $payload['usuario']['nombre']);
        $this->assertSame(self::ACTIVE_USER, $payload['usuario']['nombre_usuario']);
        $this->assertSame(self::ROLE_CODE, $payload['rol']['codigo']);
        $this->assertSame('Rol de prueba auth', $payload['rol']['nombre']);
        $this->assertSame($expiraEn->toIso8601String(), $payload['expira_en']);
        $this->assertSame(1, preg_match('/^[A-Za-z0-9_-]+$/', $token));
        $this->assertStringNotContainsString('=', $token);
        $this->assertStringNotContainsString(self::PASSWORD, $first->getContent());

        $stored = $this->sessionByToken($token);
        $this->assertSame('VIGENTE', $stored->estado);
        $this->assertSame(32, (int) $stored->longitud);
        $this->assertSame(bin2hex(hash('sha256', $token, true)), $stored->hash_hex);
        $this->assertNotSame($token, $stored->hash_hex);
        $this->assertTrue(PostgreSqlBooleanConverter::toBool($stored->coincide));

        $second = $this->postAuth('/api/auth/login', [
            'nombre_usuario' => self::ACTIVE_USER,
            'password' => self::PASSWORD,
            'identificador_dispositivo' => self::ACTIVE_DEVICE,
        ])->assertOk();
        $secondToken = $second->json('token');
        $this->assertNotSame($token, $secondToken);

        $this->assertBearerRejections($expiraEn, CarbonImmutable::parse('2026-09-30 15:00:00', 'UTC'));
        $this->assertAppConnectionCannotDeleteSessions();

        $this->postAuth('/api/auth/logout', token: $token)->assertNoContent();
        $this->assertSame('REVOCADA', $this->sessionByToken($token)->estado);
        $this->assertSame('VIGENTE', $this->sessionByToken($secondToken)->estado);

        DB::table('dispositivos')
            ->where('identificador_dispositivo', self::ACTIVE_DEVICE)
            ->update(['estado' => 'REVOCADO']);

        $this->postAuth('/api/auth/logout', token: $secondToken)->assertUnauthorized();
        $this->assertSame('VIGENTE', $this->sessionByToken($secondToken)->estado);
    }

    private function assertBearerRejections(CarbonImmutable $expiraEn, CarbonImmutable $ahora): void
    {
        $userId = $this->userId(self::ACTIVE_USER);
        $deviceId = (int) DB::table('dispositivos')
            ->where('identificador_dispositivo', self::ACTIVE_DEVICE)
            ->value('id');
        $expected = null;

        foreach ([
            ['token-expirado-ficticio', $ahora->subMinute(), 'VIGENTE', $deviceId],
            ['token-revocado-ficticio', $expiraEn, 'REVOCADA', $deviceId],
        ] as [$token, $vence, $estado, $dispositivoId]) {
            $this->insertSession($userId, $dispositivoId, $token, $vence, $estado);
            $response = $this->postAuth('/api/auth/logout', token: $token);
            $response->assertUnauthorized();
            $expected ??= $response->json();
            $this->assertSame($expected, $response->json());
            $this->assertSame($estado, $this->sessionByToken($token)->estado);
        }
    }

    private function assertAppConnectionCannotDeleteSessions(): void
    {
        $before = DB::table('sesiones_token')->count();

        try {
            DB::connection('pgsql')->delete('delete from sesiones_token');
            $this->fail('La conexión app no debía borrar sesiones_token.');
        } catch (QueryException) {
            $this->assertSame($before, DB::table('sesiones_token')->count());
        }
    }

    private function insertUsuario(
        string $publicId,
        string $nombre,
        string $nombreUsuario,
        string $credencial,
        int $roleId,
        string $estado,
        CarbonImmutable $timestamp,
    ): void {
        DB::table('usuarios')->insert([
            'id_publico' => $publicId,
            'nombre' => $nombre,
            'nombre_usuario' => $nombreUsuario,
            'credencial_hash' => $credencial,
            'estado' => $estado,
            'rol_id' => $roleId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function insertDispositivo(int $userId, string $identificador, string $estado): void
    {
        DB::table('dispositivos')->insert([
            'usuario_id' => $userId,
            'identificador_dispositivo' => $identificador,
            'estado' => $estado,
        ]);
    }

    private function insertSession(
        int $userId,
        int $deviceId,
        string $token,
        CarbonImmutable $expiraEn,
        string $estado,
    ): void {
        DB::selectOne(
            'insert into sesiones_token (usuario_id, dispositivo_id, token_hash, expira_en, estado)
             values (?, ?, decode(?, \'hex\'), ?::timestamptz, ?)
             returning id',
            [
                $userId,
                $deviceId,
                bin2hex(hash('sha256', $token, true)),
                $expiraEn->format('Y-m-d H:i:sP'),
                $estado,
            ],
        );
    }

    private function sessionByToken(string $token): object
    {
        $row = DB::selectOne(
            'select estado,
                    octet_length(token_hash) as longitud,
                    encode(token_hash, \'hex\') as hash_hex,
                    expira_en = ?::timestamptz as coincide
             from sesiones_token
             where token_hash = decode(?, \'hex\')',
            [
                CarbonImmutable::parse('2026-09-30 15:00:00', 'UTC')->addMinutes(90)->format('Y-m-d H:i:sP'),
                bin2hex(hash('sha256', $token, true)),
            ],
        );

        $this->assertNotNull($row);

        return $row;
    }

    private function userId(string $nombreUsuario): int
    {
        return (int) DB::table('usuarios')->where('nombre_usuario', $nombreUsuario)->value('id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function postAuth(string $uri, array $data = [], ?string $token = null): TestResponse
    {
        Auth::forgetGuards();

        if ($token === null) {
            $this->flushHeaders();
        } else {
            $this->withToken($token);
        }

        return $this->postJson($uri, $data);
    }
}
