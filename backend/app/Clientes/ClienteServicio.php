<?php

namespace App\Clientes;

use App\Infrastructure\Identidad\GeneradorIdPublico;
use App\Infrastructure\Pii\ProtectorTelefono;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ClienteServicio
{
    public const CONTEXTO_PRINCIPAL = 'clientes.telefono_principal';

    public const CONTEXTO_CONTACTO = 'contactos_alternativos.telefono';

    public const CONTEXTO_REFERENCIA = 'referencias.telefono';

    public function __construct(
        private readonly ProtectorTelefono $pii,
        private readonly GeneradorIdPublico $ids,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public function registrar(array $datos, int $usuarioId): array
    {
        $rutaId = $this->rutaActivaId((string) $datos['ruta_id_publico']);
        $hmacPrincipal = bin2hex($this->pii->hmacTelefono((string) $datos['telefono_principal'], $this->versionActual()));
        $hmacContacto = bin2hex($this->pii->hmacTelefono((string) $datos['contacto_alternativo']['telefono'], $this->versionActual()));
        $hmacReferencia = bin2hex($this->pii->hmacTelefono((string) $datos['referencia']['telefono'], $this->versionActual()));

        return DB::transaction(function () use ($datos, $usuarioId, $rutaId, $hmacPrincipal, $hmacContacto, $hmacReferencia): array {
            $candidatos = $this->buscarDuplicados(
                (string) $datos['nombre'],
                (string) $datos['domicilio']['direccion'],
                $hmacPrincipal,
                $hmacContacto,
                $hmacReferencia,
            );

            if ($candidatos !== [] && ! $this->confirmado($datos)) {
                throw new ClienteOperacionRechazada([
                    'message' => 'Se requiere confirmación de no duplicado.',
                    'candidatos' => $candidatos,
                ], 422);
            }

            $ahora = $this->ahora();
            $principal = $this->pii->protegerTelefono((string) $datos['telefono_principal'], self::CONTEXTO_PRINCIPAL);
            $clienteId = $this->insertarCliente($datos, $principal, $ahora);
            $contacto = $this->pii->protegerTelefono((string) $datos['contacto_alternativo']['telefono'], self::CONTEXTO_CONTACTO);
            $this->insertarContacto($clienteId, $datos['contacto_alternativo'], $contacto, $ahora);
            $referencia = $this->pii->protegerTelefono((string) $datos['referencia']['telefono'], self::CONTEXTO_REFERENCIA);
            $this->insertarReferencia($clienteId, $datos['referencia'], $referencia);
            $this->insertarDomicilio($clienteId, $datos['domicilio'], null, $ahora);
            $this->insertarDocumento($clienteId, 'INE', (string) $datos['documentos']['ine'], $usuarioId, $ahora);
            $this->insertarDocumento($clienteId, 'COMPROBANTE', (string) $datos['documentos']['comprobante_domicilio'], $usuarioId, $ahora);
            $this->insertarAsignacion($clienteId, $rutaId, $ahora);

            if ($candidatos !== []) {
                $this->insertarConfirmacion($clienteId, $usuarioId, $candidatos, $ahora);
            }

            return $this->presentar($clienteId);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(): array
    {
        $ids = DB::table('clientes')->orderBy('id')->pluck('id');
        $clientes = [];

        foreach ($ids as $id) {
            $clientes[] = $this->presentar((int) $id);
        }

        return $clientes;
    }

    /**
     * @return array<string, mixed>
     */
    public function consultar(string $idPublico): array
    {
        $id = DB::table('clientes')->where('id_publico', $idPublico)->value('id');

        if ($id === null) {
            throw new ClienteOperacionRechazada(['message' => 'No encontrado.'], 404);
        }

        return $this->presentar((int) $id);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public function actualizar(string $idPublico, array $datos, int $usuarioId): array
    {
        $clienteId = DB::table('clientes')->where('id_publico', $idPublico)->value('id');

        if ($clienteId === null) {
            throw new ClienteOperacionRechazada(['message' => 'No encontrado.'], 404);
        }

        $clienteId = (int) $clienteId;

        return DB::transaction(function () use ($datos, $usuarioId, $clienteId): array {
            $ahora = $this->ahora();
            $cambios = ['updated_at' => $ahora];

            if (array_key_exists('nombre', $datos)) {
                $cambios['nombre'] = $datos['nombre'];
            }

            if (array_key_exists('observaciones', $datos)) {
                $cambios['observaciones'] = $datos['observaciones'];
            }

            if (array_key_exists('estado', $datos)) {
                $cambios['estado'] = $datos['estado'];
            }

            if (array_key_exists('telefono_principal', $datos)) {
                $telefono = $this->pii->protegerTelefono((string) $datos['telefono_principal'], self::CONTEXTO_PRINCIPAL);
                DB::update(
                    'update clientes
                     set telefono_principal_cifrado = decode(?, \'hex\'),
                         telefono_principal_hmac = decode(?, \'hex\'),
                         telefono_principal_ultimos4 = ?,
                         telefono_principal_version_clave = ?,
                         updated_at = ?::timestamptz
                     where id = ?',
                    [
                        bin2hex($telefono['cifrado']),
                        bin2hex($telefono['hmac']),
                        $telefono['ultimos4'],
                        $telefono['version_clave'],
                        $ahora,
                        $clienteId,
                    ],
                );
                unset($cambios['updated_at']);
            }

            if ($cambios !== []) {
                DB::table('clientes')->where('id', $clienteId)->update($cambios);
            }

            if (isset($datos['contacto_alternativo']) && is_array($datos['contacto_alternativo'])) {
                DB::table('contactos_alternativos')
                    ->where('cliente_id', $clienteId)
                    ->where('vigente', true)
                    ->update(['vigente' => false, 'vigente_hasta' => $ahora]);
                $telefono = $this->pii->protegerTelefono((string) $datos['contacto_alternativo']['telefono'], self::CONTEXTO_CONTACTO);
                $this->insertarContacto($clienteId, $datos['contacto_alternativo'], $telefono, $ahora);
            }

            if (isset($datos['referencia']) && is_array($datos['referencia'])) {
                DB::table('referencias')
                    ->where('cliente_id', $clienteId)
                    ->where('vigente', true)
                    ->update(['vigente' => false]);
                $telefono = $this->pii->protegerTelefono((string) $datos['referencia']['telefono'], self::CONTEXTO_REFERENCIA);
                $this->insertarReferencia($clienteId, $datos['referencia'], $telefono);
            }

            if (isset($datos['domicilio']) && is_array($datos['domicilio'])) {
                DB::table('domicilios_ubicaciones')
                    ->where('cliente_id', $clienteId)
                    ->where('vigente', true)
                    ->update(['vigente' => false]);
                $this->insertarDomicilio($clienteId, $datos['domicilio'], (string) $datos['domicilio']['motivo_cambio'], $ahora);
            }

            if (isset($datos['documentos']) && is_array($datos['documentos'])) {
                if (isset($datos['documentos']['ine'])) {
                    $this->insertarDocumento($clienteId, 'INE', (string) $datos['documentos']['ine'], $usuarioId, $ahora);
                }

                if (isset($datos['documentos']['comprobante_domicilio'])) {
                    $this->insertarDocumento($clienteId, 'COMPROBANTE', (string) $datos['documentos']['comprobante_domicilio'], $usuarioId, $ahora);
                }
            }

            if (isset($datos['ruta_id_publico'])) {
                $rutaId = $this->rutaActivaId((string) $datos['ruta_id_publico']);
                $vigente = DB::table('asignaciones_cliente_ruta')
                    ->where('cliente_id', $clienteId)
                    ->where('estado', 'VIGENTE')
                    ->value('ruta_id');

                if ((int) $vigente !== $rutaId) {
                    DB::table('asignaciones_cliente_ruta')
                        ->where('cliente_id', $clienteId)
                        ->where('estado', 'VIGENTE')
                        ->update(['estado' => 'FINALIZADA', 'vigente_hasta' => $ahora]);
                    $this->insertarAsignacion($clienteId, $rutaId, $ahora);
                }
            }

            return $this->presentar($clienteId);
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array{cifrado: string, hmac: string, ultimos4: string, version_clave: int}  $telefono
     */
    private function insertarCliente(array $datos, array $telefono, string $ahora): int
    {
        $fila = DB::selectOne(
            'insert into clientes (
                id_publico, nombre,
                telefono_principal_cifrado, telefono_principal_hmac,
                telefono_principal_ultimos4, telefono_principal_version_clave,
                estado, observaciones, created_at, updated_at
             ) values (
                ?, ?, decode(?, \'hex\'), decode(?, \'hex\'), ?, ?, \'ACTIVO\', ?, ?::timestamptz, ?::timestamptz
             ) returning id',
            [
                $this->ids->nuevo(),
                $datos['nombre'],
                bin2hex($telefono['cifrado']),
                bin2hex($telefono['hmac']),
                $telefono['ultimos4'],
                $telefono['version_clave'],
                $datos['observaciones'] ?? null,
                $ahora,
                $ahora,
            ],
        );

        return (int) $fila->id;
    }

    /**
     * @param  array<string, mixed>  $contacto
     * @param  array{cifrado: string, hmac: string, ultimos4: string, version_clave: int}  $telefono
     */
    private function insertarContacto(int $clienteId, array $contacto, array $telefono, string $ahora): void
    {
        DB::selectOne(
            'insert into contactos_alternativos (
                cliente_id, nombre, telefono_cifrado, telefono_hmac, telefono_ultimos4, telefono_version_clave,
                relacion, vigente, vigente_desde
             ) values (
                ?, ?, decode(?, \'hex\'), decode(?, \'hex\'), ?, ?, ?, true, ?::timestamptz
             ) returning id',
            [
                $clienteId,
                $contacto['nombre'],
                bin2hex($telefono['cifrado']),
                bin2hex($telefono['hmac']),
                $telefono['ultimos4'],
                $telefono['version_clave'],
                $contacto['relacion'],
                $ahora,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $referencia
     * @param  array{cifrado: string, hmac: string, ultimos4: string, version_clave: int}  $telefono
     */
    private function insertarReferencia(int $clienteId, array $referencia, array $telefono): void
    {
        DB::selectOne(
            'insert into referencias (
                cliente_id, nombre, telefono_cifrado, telefono_hmac, telefono_ultimos4, telefono_version_clave,
                relacion, direccion, observaciones, vigente
             ) values (
                ?, ?, decode(?, \'hex\'), decode(?, \'hex\'), ?, ?, ?, ?, ?, true
             ) returning id',
            [
                $clienteId,
                $referencia['nombre'],
                bin2hex($telefono['cifrado']),
                bin2hex($telefono['hmac']),
                $telefono['ultimos4'],
                $telefono['version_clave'],
                $referencia['relacion'],
                $referencia['direccion'] ?? null,
                $referencia['observaciones'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $domicilio
     */
    private function insertarDomicilio(int $clienteId, array $domicilio, ?string $motivo, string $ahora): void
    {
        DB::selectOne(
            'insert into domicilios_ubicaciones (
                cliente_id, direccion, latitud, longitud, capturado_en, motivo_cambio, vigente
             ) values (
                ?, ?, ?::numeric, ?::numeric, ?::timestamptz, ?, true
             ) returning id',
            [
                $clienteId,
                $domicilio['direccion'],
                $this->coordenada($domicilio['latitud'], 6),
                $this->coordenada($domicilio['longitud'], 6),
                $ahora,
                $motivo,
            ],
        );
    }

    private function insertarDocumento(int $clienteId, string $tipo, string $archivo, int $usuarioId, string $ahora): void
    {
        DB::selectOne(
            'insert into documentos_cliente (
                id_publico, cliente_id, tipo, archivo_privado, capturista_id, capturado_en
             ) values (?, ?, ?, ?, ?, ?::timestamptz) returning id',
            [$this->ids->nuevo(), $clienteId, $tipo, $archivo, $usuarioId, $ahora],
        );
    }

    private function insertarAsignacion(int $clienteId, int $rutaId, string $ahora): void
    {
        DB::table('asignaciones_cliente_ruta')->insert([
            'cliente_id' => $clienteId,
            'ruta_id' => $rutaId,
            'estado' => 'VIGENTE',
            'vigente_desde' => $ahora,
        ]);
    }

    /**
     * @param  list<array{id_publico: string, nombre: string}>  $candidatos
     */
    private function insertarConfirmacion(int $clienteId, int $usuarioId, array $candidatos, string $ahora): void
    {
        DB::table('confirmaciones_no_duplicado')->insert([
            'cliente_id' => $clienteId,
            'candidatos_json' => json_encode($candidatos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'usuario_id' => $usuarioId,
            'confirmado_en' => $ahora,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentar(int $clienteId): array
    {
        $cliente = DB::selectOne(
            'select c.id_publico, c.nombre, c.estado, c.observaciones,
                    encode(c.telefono_principal_cifrado, \'hex\') as telefono_hex,
                    c.telefono_principal_version_clave as telefono_version,
                    r.id_publico as ruta_id_publico
             from clientes c
             join asignaciones_cliente_ruta a on a.cliente_id = c.id and a.estado = \'VIGENTE\'
             join rutas r on r.id = a.ruta_id
             where c.id = ?',
            [$clienteId],
        );

        $contacto = DB::selectOne(
            'select nombre, relacion, encode(telefono_cifrado, \'hex\') as telefono_hex, telefono_version_clave as telefono_version
             from contactos_alternativos
             where cliente_id = ? and vigente is true
             order by id desc
             limit 1',
            [$clienteId],
        );

        $referencia = DB::selectOne(
            'select nombre, relacion, direccion, observaciones,
                    encode(telefono_cifrado, \'hex\') as telefono_hex, telefono_version_clave as telefono_version
             from referencias
             where cliente_id = ? and vigente is true
             order by id desc
             limit 1',
            [$clienteId],
        );

        $domicilio = DB::selectOne(
            'select direccion, latitud, longitud
             from domicilios_ubicaciones
             where cliente_id = ? and vigente is true
             order by id desc
             limit 1',
            [$clienteId],
        );

        $documentos = DB::select(
            'select tipo, archivo_privado
             from documentos_cliente
             where cliente_id = ?
             order by id',
            [$clienteId],
        );

        return [
            'id_publico' => (string) $cliente->id_publico,
            'nombre' => (string) $cliente->nombre,
            'telefono_principal' => $this->revelar((string) $cliente->telefono_hex, (int) $cliente->telefono_version, self::CONTEXTO_PRINCIPAL),
            'estado' => (string) $cliente->estado,
            'observaciones' => $cliente->observaciones,
            'ruta_id_publico' => (string) $cliente->ruta_id_publico,
            'contacto_alternativo' => [
                'nombre' => (string) $contacto->nombre,
                'telefono' => $this->revelar((string) $contacto->telefono_hex, (int) $contacto->telefono_version, self::CONTEXTO_CONTACTO),
                'relacion' => (string) $contacto->relacion,
            ],
            'referencia' => [
                'nombre' => (string) $referencia->nombre,
                'telefono' => $this->revelar((string) $referencia->telefono_hex, (int) $referencia->telefono_version, self::CONTEXTO_REFERENCIA),
                'relacion' => (string) $referencia->relacion,
                'direccion' => $referencia->direccion,
                'observaciones' => $referencia->observaciones,
            ],
            'domicilio' => [
                'direccion' => (string) $domicilio->direccion,
                'latitud' => (string) $domicilio->latitud,
                'longitud' => (string) $domicilio->longitud,
            ],
            'documentos' => array_map(static fn (object $documento): array => [
                'tipo' => (string) $documento->tipo,
                'archivo_privado' => (string) $documento->archivo_privado,
            ], $documentos),
        ];
    }

    /**
     * @return list<array{id_publico: string, nombre: string}>
     */
    private function buscarDuplicados(
        string $nombre,
        string $direccion,
        string $hmacPrincipal,
        string $hmacContacto,
        string $hmacReferencia,
    ): array {
        $filas = DB::select(
            'select distinct id_publico, nombre from (
                select c.id_publico, c.nombre
                from clientes c
                where c.telefono_principal_hmac = decode(?, \'hex\')
                   or lower(c.nombre) = lower(?)
                union
                select c.id_publico, c.nombre
                from contactos_alternativos ca
                join clientes c on c.id = ca.cliente_id
                where ca.vigente is true and ca.telefono_hmac = decode(?, \'hex\')
                union
                select c.id_publico, c.nombre
                from referencias r
                join clientes c on c.id = r.cliente_id
                where r.vigente is true and r.telefono_hmac = decode(?, \'hex\')
                union
                select c.id_publico, c.nombre
                from domicilios_ubicaciones d
                join clientes c on c.id = d.cliente_id
                where d.vigente is true and lower(d.direccion) = lower(?)
            ) coincidencias',
            [$hmacPrincipal, $nombre, $hmacContacto, $hmacReferencia, $direccion],
        );

        return array_map(static fn (object $fila): array => [
            'id_publico' => (string) $fila->id_publico,
            'nombre' => (string) $fila->nombre,
        ], $filas);
    }

    private function rutaActivaId(string $idPublico): int
    {
        $ruta = DB::table('rutas')->where('id_publico', $idPublico)->first(['id', 'estado']);

        if ($ruta === null || $ruta->estado !== 'ACTIVA') {
            throw new ClienteOperacionRechazada(['message' => 'La ruta no está disponible.'], 422);
        }

        return (int) $ruta->id;
    }

    private function revelar(string $hex, int $version, string $contexto): string
    {
        $binario = hex2bin($hex);

        if ($binario === false) {
            throw new ClienteOperacionRechazada(['message' => 'No fue posible consultar el cliente.'], 500);
        }

        return $this->pii->revelarTelefono($binario, $version, $contexto);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function confirmado(array $datos): bool
    {
        return filter_var($datos['confirmar_no_duplicado'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function versionActual(): int
    {
        $version = config('credimex.pii.key_version');

        if (is_string($version) && ctype_digit($version)) {
            $version = (int) $version;
        }

        if (! is_int($version) || $version < 1) {
            throw new ClienteOperacionRechazada(['message' => 'No fue posible proteger el dato.'], 500);
        }

        return $version;
    }

    private function coordenada(mixed $valor, int $escala): string
    {
        if (is_string($valor) && preg_match('/^-?\d+(\.\d+)?$/', $valor) === 1) {
            return $valor;
        }

        if (is_int($valor) || is_float($valor)) {
            return number_format($valor, $escala, '.', '');
        }

        throw new ClienteOperacionRechazada(['message' => 'La ubicación no es válida.'], 422);
    }

    private function ahora(): string
    {
        return CarbonImmutable::now('UTC')->format('Y-m-d H:i:sP');
    }
}
