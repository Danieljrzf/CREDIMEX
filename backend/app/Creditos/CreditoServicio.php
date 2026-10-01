<?php

namespace App\Creditos;

use App\Infrastructure\Identidad\GeneradorIdPublico;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CreditoServicio
{
    private const SEMAFORO_INICIAL = 'VERDE';

    public function __construct(
        private readonly GeneradorIdPublico $ids,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public function registrar(array $datos, int $usuarioId): array
    {
        $monto = (int) $datos['monto_centavos'];
        $plazo = (int) $datos['plazo_cuotas'];

        return DB::transaction(function () use ($datos, $usuarioId, $monto, $plazo): array {
            $cliente = $this->bloquearCliente((string) $datos['cliente_id_publico']);
            $rutaIdPublico = $this->rutaVigente((int) $cliente->id);
            $plan = $this->planVigente($plazo);
            $condiciones = $this->calcular($monto, $plazo, (string) $plan->tasa);
            $this->exigirMontoMinimo($monto);
            $this->exigirCupo((int) $cliente->id);

            $ahora = $this->ahora();
            $solicitudIdPublico = $this->ids->nuevo();
            $solicitudId = $this->insertarSolicitud(
                $solicitudIdPublico,
                (int) $cliente->id,
                (int) $plan->id,
                $monto,
                $usuarioId,
                'PENDIENTE_AUTORIZACION',
            );

            if ($this->superaLimiteDeCobrador($usuarioId, $monto)) {
                return [
                    'resultado' => 'PENDIENTE_AUTORIZACION',
                    'solicitud_id_publico' => $solicitudIdPublico,
                    'credito' => null,
                    'principal_centavos' => $condiciones['principal_centavos'],
                    'interes_centavos' => $condiciones['interes_centavos'],
                    'comision_centavos' => $condiciones['comision_centavos'],
                    'total_a_pagar_centavos' => $condiciones['total_a_pagar_centavos'],
                    'cuota_base_centavos' => $condiciones['cuota_base_centavos'],
                    'plazo_cuotas' => $plazo,
                    'tasa' => $condiciones['tasa'],
                    'ruta_id_publico' => $rutaIdPublico,
                ];
            }

            DB::table('autorizaciones_credito')->insert([
                'solicitud_credito_id' => $solicitudId,
                'autorizador_id' => $usuarioId,
                'resultado' => 'APROBADA',
                'monto_centavos' => $monto,
                'decidido_en' => $ahora,
            ]);

            $creditoIdPublico = $this->ids->nuevo();
            $creditoId = $this->insertarCredito($creditoIdPublico, (int) $cliente->id, $solicitudId, $condiciones['total_a_pagar_centavos']);
            $versionId = $this->insertarCondiciones($creditoId, $condiciones, $ahora);

            DB::table('creditos')->where('id', $creditoId)->update([
                'version_condiciones_vigente_id' => $versionId,
            ]);
            DB::table('solicitudes_credito')->where('id', $solicitudId)->update([
                'estado' => 'APROBADA',
                'credito_id' => $creditoId,
            ]);

            return [
                'resultado' => 'APROBADA',
                'solicitud_id_publico' => $solicitudIdPublico,
                'credito' => $this->presentar($creditoId),
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(): array
    {
        $ids = DB::table('creditos')->orderBy('id')->pluck('id');
        $creditos = [];

        foreach ($ids as $id) {
            $creditos[] = $this->presentar((int) $id);
        }

        return $creditos;
    }

    /**
     * @return array<string, mixed>
     */
    public function consultar(string $idPublico): array
    {
        $id = DB::table('creditos')->where('id_publico', $idPublico)->value('id');

        if ($id === null) {
            throw new CreditoOperacionRechazada(['message' => 'No encontrado.'], 404);
        }

        return $this->presentar((int) $id);
    }

    private function bloquearCliente(string $idPublico): object
    {
        $cliente = DB::selectOne(
            'select id, estado from clientes where id_publico = ? for update',
            [$idPublico],
        );

        if ($cliente === null) {
            throw new CreditoOperacionRechazada(['message' => 'El cliente no está disponible.'], 422);
        }

        return $cliente;
    }

    private function rutaVigente(int $clienteId): string
    {
        $ruta = DB::selectOne(
            'select r.id_publico
             from asignaciones_cliente_ruta a
             join rutas r on r.id = a.ruta_id
             where a.cliente_id = ?
               and a.estado = \'VIGENTE\'
               and r.estado = \'ACTIVA\'
             limit 1',
            [$clienteId],
        );

        if ($ruta === null) {
            throw new CreditoOperacionRechazada(['message' => 'El cliente no tiene una ruta activa.'], 422);
        }

        return (string) $ruta->id_publico;
    }

    private function planVigente(int $plazo): object
    {
        $plan = DB::selectOne(
            'select vp.id, vp.tasa::text as tasa
             from versiones_plan vp
             join planes_credito p on p.id = vp.plan_credito_id
             where p.estado = \'ACTIVO\'
               and vp.plazo_cuotas = ?
             order by vp.vigente_desde desc
             limit 1',
            [$plazo],
        );

        if ($plan === null) {
            throw new CreditoOperacionRechazada(['message' => 'El plazo no está disponible.'], 422);
        }

        return $plan;
    }

    /**
     * @return array{
     *     principal_centavos: int,
     *     interes_centavos: int,
     *     comision_centavos: int,
     *     total_a_pagar_centavos: int,
     *     cuota_base_centavos: int,
     *     plazo_cuotas: int,
     *     tasa: string
     * }
     */
    private function calcular(int $monto, int $plazo, string $tasa): array
    {
        $interes = $this->aplicarTasa($monto, $tasa);
        $total = $monto + $interes;
        $comision = $this->comision($monto);

        return [
            'principal_centavos' => $monto,
            'interes_centavos' => $interes,
            'comision_centavos' => $comision,
            'total_a_pagar_centavos' => $total,
            'cuota_base_centavos' => intdiv($total, $plazo),
            'plazo_cuotas' => $plazo,
            'tasa' => $this->tasaNormalizada($tasa),
        ];
    }

    private function exigirMontoMinimo(int $monto): void
    {
        if ($monto < $this->parametro('credito_monto_minimo_centavos')) {
            throw new CreditoOperacionRechazada(['message' => 'El monto no está permitido.'], 422);
        }
    }

    private function exigirCupo(int $clienteId): void
    {
        $activos = (int) DB::table('creditos')
            ->where('cliente_id', $clienteId)
            ->where('estado', 'ACTIVO')
            ->where('saldo_actual_centavos', '>', 0)
            ->count();

        if ($activos >= $this->parametro('credito_maximo_activos')) {
            throw new CreditoOperacionRechazada(['message' => 'El cliente alcanzó el máximo de créditos activos.'], 422);
        }
    }

    private function superaLimiteDeCobrador(int $usuarioId, int $monto): bool
    {
        $codigo = DB::table('usuarios')
            ->join('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->where('usuarios.id', $usuarioId)
            ->value('roles.codigo');

        if ($codigo !== 'cobrador') {
            return false;
        }

        return $monto > $this->parametro('credito_limite_cobrador_centavos');
    }

    private function insertarSolicitud(
        string $idPublico,
        int $clienteId,
        int $versionPlanId,
        int $monto,
        int $usuarioId,
        string $estado,
    ): int {
        $fila = DB::selectOne(
            'insert into solicitudes_credito (
                id_publico, cliente_id, version_plan_id, monto_centavos, estado, solicitante_id
             ) values (?, ?, ?, ?, ?, ?)
             returning id',
            [$idPublico, $clienteId, $versionPlanId, $monto, $estado, $usuarioId],
        );

        return (int) $fila->id;
    }

    private function insertarCredito(string $idPublico, int $clienteId, int $solicitudId, int $saldoInicial): int
    {
        $fila = DB::selectOne(
            'insert into creditos (
                id_publico, cliente_id, solicitud_credito_id, estado,
                saldo_inicial_centavos, saldo_actual_centavos,
                dias_atraso_actual, semaforo_actual, cuotas_vencidas_pendientes
             ) values (?, ?, ?, \'PENDIENTE_DESEMBOLSO\', ?, 0, 0, ?, 0)
             returning id',
            [$idPublico, $clienteId, $solicitudId, $saldoInicial, self::SEMAFORO_INICIAL],
        );

        return (int) $fila->id;
    }

    /**
     * @param  array{
     *     principal_centavos: int,
     *     interes_centavos: int,
     *     comision_centavos: int,
     *     total_a_pagar_centavos: int,
     *     cuota_base_centavos: int,
     *     plazo_cuotas: int,
     *     tasa: string
     * }  $condiciones
     */
    private function insertarCondiciones(int $creditoId, array $condiciones, string $ahora): int
    {
        $fila = DB::selectOne(
            'insert into versiones_condiciones_credito (
                credito_id, monto_centavos, plazo_cuotas, tasa, interes_centavos,
                total_a_pagar_centavos, cuota_base_centavos, comision_centavos, origen, creado_en
             ) values (?, ?, ?, ?::numeric, ?, ?, ?, ?, \'AUTORIZACION\', ?::timestamptz)
             returning id',
            [
                $creditoId,
                $condiciones['principal_centavos'],
                $condiciones['plazo_cuotas'],
                $condiciones['tasa'],
                $condiciones['interes_centavos'],
                $condiciones['total_a_pagar_centavos'],
                $condiciones['cuota_base_centavos'],
                $condiciones['comision_centavos'],
                $ahora,
            ],
        );

        return (int) $fila->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentar(int $creditoId): array
    {
        $fila = DB::selectOne(
            'select cr.id_publico, cr.estado, cr.saldo_inicial_centavos, cr.saldo_actual_centavos,
                    cr.semaforo_actual, c.id_publico as cliente_id_publico,
                    v.monto_centavos, v.interes_centavos, v.comision_centavos, v.total_a_pagar_centavos,
                    v.cuota_base_centavos, v.plazo_cuotas, v.tasa::text as tasa,
                    r.id_publico as ruta_id_publico
             from creditos cr
             join clientes c on c.id = cr.cliente_id
             join versiones_condiciones_credito v on v.id = cr.version_condiciones_vigente_id
             join asignaciones_cliente_ruta a on a.cliente_id = c.id and a.estado = \'VIGENTE\'
             join rutas r on r.id = a.ruta_id
             where cr.id = ?',
            [$creditoId],
        );

        return [
            'id_publico' => (string) $fila->id_publico,
            'cliente_id_publico' => (string) $fila->cliente_id_publico,
            'ruta_id_publico' => (string) $fila->ruta_id_publico,
            'estado' => (string) $fila->estado,
            'principal_centavos' => (int) $fila->monto_centavos,
            'interes_centavos' => (int) $fila->interes_centavos,
            'comision_centavos' => (int) $fila->comision_centavos,
            'total_a_pagar_centavos' => (int) $fila->total_a_pagar_centavos,
            'saldo_inicial_centavos' => (int) $fila->saldo_inicial_centavos,
            'saldo_actual_centavos' => (int) $fila->saldo_actual_centavos,
            'cuota_base_centavos' => (int) $fila->cuota_base_centavos,
            'plazo_cuotas' => (int) $fila->plazo_cuotas,
            'tasa' => $this->tasaNormalizada((string) $fila->tasa),
            'semaforo_actual' => (string) $fila->semaforo_actual,
        ];
    }

    private function aplicarTasa(int $monto, string $tasa): int
    {
        $factor = $this->factorMicros($tasa);

        return intdiv($monto * $factor + 500000, 1000000);
    }

    private function comision(int $monto): int
    {
        $porCienPesos = $this->parametro('credito_comision_centavos_por_100_pesos');

        return intdiv($monto * $porCienPesos + 5000, 10000);
    }

    private function factorMicros(string $tasa): int
    {
        $normalizada = $this->tasaNormalizada($tasa);
        $partes = explode('.', $normalizada);

        return ((int) $partes[0] * 1000000) + (int) $partes[1];
    }

    private function tasaNormalizada(string $tasa): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $tasa, $coincidencias) !== 1) {
            throw new CreditoOperacionRechazada(['message' => 'No fue posible calcular el crédito.'], 500);
        }

        return $coincidencias[1].'.'.str_pad(substr($coincidencias[2], 0, 6), 6, '0');
    }

    private function parametro(string $clave): int
    {
        $filas = DB::table('parametros_sistema')
            ->where('clave', $clave)
            ->whereNull('vigente_hasta')
            ->pluck('valor');

        $valor = $filas->count() === 1 ? (string) $filas->first() : '';

        if ($valor === '' || preg_match('/^\d+$/', $valor) !== 1) {
            throw new CreditoOperacionRechazada(['message' => 'No fue posible calcular el crédito.'], 500);
        }

        return (int) $valor;
    }

    private function ahora(): string
    {
        return CarbonImmutable::now('UTC')->format('Y-m-d H:i:sP');
    }
}
