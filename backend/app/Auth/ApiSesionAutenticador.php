<?php

namespace App\Auth;

use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class ApiSesionAutenticador
{
    private const ESTADO_USUARIO_ACTIVO = 'ACTIVO';

    private const ESTADO_DISPOSITIVO_ACTIVO = 'ACTIVO';

    private const ESTADO_SESION_VIGENTE = 'VIGENTE';

    private const ESTADO_SESION_REVOCADA = 'REVOCADA';

    private static ?string $credencialInexistente = null;

    public function authenticate(Request $request): ?Usuario
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return null;
        }

        $sesion = DB::selectOne(
            'select usuario_id, dispositivo_id
             from sesiones_token
             where token_hash = decode(?, \'hex\')
               and estado = ?
               and expira_en > ?::timestamptz',
            [
                bin2hex($this->hashToken($token)),
                self::ESTADO_SESION_VIGENTE,
                $this->ahora()->format('Y-m-d H:i:sP'),
            ],
        );

        if ($sesion === null) {
            return null;
        }

        $usuario = Usuario::query()->find($sesion->usuario_id);

        if (! $usuario instanceof Usuario || $usuario->estado !== self::ESTADO_USUARIO_ACTIVO) {
            return null;
        }

        if ($sesion->dispositivo_id === null) {
            return $usuario;
        }

        $dispositivo = DB::table('dispositivos')->where('id', $sesion->dispositivo_id)->first();

        if ($dispositivo === null
            || (int) $dispositivo->usuario_id !== (int) $usuario->getAuthIdentifier()
            || $dispositivo->estado !== self::ESTADO_DISPOSITIVO_ACTIVO) {
            return null;
        }

        return $usuario;
    }

    /**
     * @return array{
     *     token: string,
     *     expira_en: string,
     *     usuario: array{id_publico: string, nombre: string, nombre_usuario: string},
     *     rol: array{codigo: string, nombre: string}
     * }|null
     */
    public function login(string $nombreUsuario, string $password, string $identificadorDispositivo): ?array
    {
        $usuario = Usuario::query()->where('nombre_usuario', $nombreUsuario)->first();
        $claveValida = Hash::check($password, $this->credencialParaComparar($usuario));
        $dispositivo = DB::table('dispositivos')
            ->where('identificador_dispositivo', $identificadorDispositivo)
            ->first();

        $usuarioValido = $usuario instanceof Usuario
            && $claveValida
            && $usuario->estado === self::ESTADO_USUARIO_ACTIVO;
        $dispositivoValido = $usuarioValido
            && $dispositivo !== null
            && (int) $dispositivo->usuario_id === (int) $usuario->getAuthIdentifier()
            && $dispositivo->estado === self::ESTADO_DISPOSITIVO_ACTIVO;

        if (! $usuarioValido || ! $dispositivoValido) {
            return null;
        }

        $token = $this->tokenEnClaro();
        $expiraEn = $this->ahora()->addMinutes($this->ttlMinutos());

        $insertada = DB::selectOne(
            'insert into sesiones_token (usuario_id, dispositivo_id, token_hash, expira_en, estado)
             values (?, ?, decode(?, \'hex\'), ?::timestamptz, ?)
             returning expira_en',
            [
                $usuario->getAuthIdentifier(),
                $dispositivo->id,
                bin2hex($this->hashToken($token)),
                $expiraEn->format('Y-m-d H:i:sP'),
                self::ESTADO_SESION_VIGENTE,
            ],
        );

        if ($insertada === null) {
            throw new RuntimeException('No fue posible crear la sesión.');
        }

        $rol = DB::table('roles')->where('id', $usuario->rol_id)->first(['codigo', 'nombre']);

        if ($rol === null) {
            throw new RuntimeException('No fue posible resolver el rol del usuario.');
        }

        return [
            'token' => $token,
            'expira_en' => $expiraEn->toIso8601String(),
            'usuario' => [
                'id_publico' => (string) $usuario->id_publico,
                'nombre' => (string) $usuario->nombre,
                'nombre_usuario' => (string) $usuario->nombre_usuario,
            ],
            'rol' => [
                'codigo' => (string) $rol->codigo,
                'nombre' => (string) $rol->nombre,
            ],
        ];
    }

    public function logout(Request $request): bool
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return false;
        }

        $revocadas = DB::update(
            'update sesiones_token
             set estado = ?
             where token_hash = decode(?, \'hex\')
               and estado = ?',
            [
                self::ESTADO_SESION_REVOCADA,
                bin2hex($this->hashToken($token)),
                self::ESTADO_SESION_VIGENTE,
            ],
        );

        return $revocadas === 1;
    }

    private function credencialParaComparar(?Usuario $usuario): string
    {
        if ($usuario instanceof Usuario) {
            return (string) $usuario->credencial_hash;
        }

        return self::$credencialInexistente ??= Hash::make('credencial-inexistente');
    }

    private function tokenEnClaro(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function hashToken(string $tokenEnClaro): string
    {
        return hash('sha256', $tokenEnClaro, true);
    }

    private function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    private function ttlMinutos(): int
    {
        $ttl = config('credimex.auth.sesion_ttl_minutos');

        if (! is_int($ttl) || $ttl < 1) {
            throw new RuntimeException('La duración de la sesión no está configurada.');
        }

        return $ttl;
    }
}
