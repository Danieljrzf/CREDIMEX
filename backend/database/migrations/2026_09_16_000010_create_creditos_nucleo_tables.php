<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const SECUENCIAS = [
        'solicitudes_credito_id_seq',
        'autorizaciones_credito_id_seq',
        'creditos_id_seq',
        'versiones_condiciones_credito_id_seq',
    ];

    /** @var list<array{nombre: string, plazo: int, tasa: string}> */
    private const PLANES = [
        ['nombre' => 'Plazo 20', 'plazo' => 20, 'tasa' => '0.200000'],
        ['nombre' => 'Plazo 27', 'plazo' => 27, 'tasa' => '0.215000'],
        ['nombre' => 'Plazo 40', 'plazo' => 40, 'tasa' => '0.320000'],
        ['nombre' => 'Plazo 54', 'plazo' => 54, 'tasa' => '0.404000'],
        ['nombre' => 'Plazo 68', 'plazo' => 68, 'tasa' => '0.496000'],
    ];

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        $schema = Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION);
        $ahora = CarbonImmutable::now('UTC');

        $schema->create('parametros_sistema', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->text('clave');
            $table->text('valor');
            $table->timestampTz('vigente_desde');
            $table->timestampTz('vigente_hasta')->nullable();
            $table->text('zona_horaria')->nullable();
            $table->index('clave', 'idx_parametros_sistema_clave');
        });

        $schema->create('planes_credito', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->text('nombre');
            $table->string('estado', 64)->default('ACTIVO');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
        });

        $schema->create('versiones_plan', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('plan_credito_id');
            $table->integer('plazo_cuotas');
            $table->decimal('tasa', 9, 6);
            $table->timestampTz('vigente_desde');
            $table->foreign('plan_credito_id', 'fk_versiones_plan_plan_credito_id')
                ->references('id')->on('planes_credito')->noActionOnUpdate()->noActionOnDelete();
            $table->index('plan_credito_id', 'idx_versiones_plan_plan_credito_id');
        });

        $schema->create('solicitudes_credito', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->uuid('id_publico');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('version_plan_id');
            $table->bigInteger('monto_centavos');
            $table->string('estado', 64)->default('BORRADOR');
            $table->unsignedBigInteger('credito_id')->nullable();
            $table->unsignedBigInteger('solicitante_id');
            $table->unique('id_publico', 'uq_solicitudes_credito_id_publico');
            $table->foreign('cliente_id', 'fk_solicitudes_credito_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('version_plan_id', 'fk_solicitudes_credito_version_plan_id')
                ->references('id')->on('versiones_plan')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('solicitante_id', 'fk_solicitudes_credito_solicitante_id')
                ->references('id')->on('usuarios')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_solicitudes_credito_cliente_id');
        });

        $schema->create('creditos', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->uuid('id_publico');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('solicitud_credito_id');
            $table->string('estado', 64)->default('PENDIENTE_DESEMBOLSO');
            $table->bigInteger('saldo_inicial_centavos');
            $table->bigInteger('saldo_actual_centavos')->default(0);
            $table->integer('dias_atraso_actual')->default(0);
            $table->string('semaforo_actual', 64);
            $table->integer('cuotas_vencidas_pendientes')->default(0);
            $table->date('fecha_calculo_atraso')->nullable();
            $table->unsignedBigInteger('version_condiciones_vigente_id')->nullable();
            $table->unique('id_publico', 'uq_creditos_id_publico');
            $table->foreign('cliente_id', 'fk_creditos_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('solicitud_credito_id', 'fk_creditos_solicitud_credito_id')
                ->references('id')->on('solicitudes_credito')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_creditos_cliente_id');
            $table->index('solicitud_credito_id', 'idx_creditos_solicitud_credito_id');
        });

        $schema->create('autorizaciones_credito', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('solicitud_credito_id');
            $table->unsignedBigInteger('autorizador_id');
            $table->string('resultado', 64);
            $table->bigInteger('monto_centavos')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestampTz('decidido_en');
            $table->foreign('solicitud_credito_id', 'fk_autorizaciones_credito_solicitud_credito_id')
                ->references('id')->on('solicitudes_credito')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('autorizador_id', 'fk_autorizaciones_credito_autorizador_id')
                ->references('id')->on('usuarios')->noActionOnUpdate()->noActionOnDelete();
            $table->index('solicitud_credito_id', 'idx_autorizaciones_credito_solicitud_credito_id');
        });

        $schema->create('versiones_condiciones_credito', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('credito_id');
            $table->bigInteger('monto_centavos');
            $table->integer('plazo_cuotas');
            $table->decimal('tasa', 9, 6);
            $table->bigInteger('interes_centavos');
            $table->bigInteger('total_a_pagar_centavos');
            $table->bigInteger('cuota_base_centavos');
            $table->bigInteger('comision_centavos');
            $table->string('origen', 64);
            $table->timestampTz('creado_en');
            $table->foreign('credito_id', 'fk_versiones_condiciones_credito_credito_id')
                ->references('id')->on('creditos')->noActionOnUpdate()->noActionOnDelete();
            $table->index('credito_id', 'idx_versiones_condiciones_credito_credito_id');
        });

        $schema->table('solicitudes_credito', function (Blueprint $table): void {
            $table->foreign('credito_id', 'fk_solicitudes_credito_credito_id')
                ->references('id')->on('creditos')->noActionOnUpdate()->noActionOnDelete();
        });

        $schema->table('creditos', function (Blueprint $table): void {
            $table->foreign('version_condiciones_vigente_id', 'fk_creditos_version_condiciones_vigente_id')
                ->references('id')->on('versiones_condiciones_credito')->noActionOnUpdate()->noActionOnDelete();
        });

        $this->restricciones();
        $this->datosIniciales($ahora);
        $this->conceder();
    }

    public function down(): void
    {
        $this->revocar();
        $owner = DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION);
        $owner->statement('ALTER TABLE "credimex"."solicitudes_credito" DROP CONSTRAINT "fk_solicitudes_credito_credito_id"');
        $owner->statement('ALTER TABLE "credimex"."creditos" DROP CONSTRAINT "fk_creditos_version_condiciones_vigente_id"');

        $schema = Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION);

        foreach ([
            'versiones_condiciones_credito',
            'autorizaciones_credito',
            'creditos',
            'solicitudes_credito',
            'versiones_plan',
            'planes_credito',
            'parametros_sistema',
        ] as $tabla) {
            $schema->drop($tabla);
        }
    }

    private function restricciones(): void
    {
        $this->sql('ALTER TABLE "credimex"."planes_credito" ADD CONSTRAINT "chk_planes_credito_estado" CHECK ("estado" IN (\'ACTIVO\', \'INACTIVO\'))');
        $this->sql('ALTER TABLE "credimex"."versiones_plan" ADD CONSTRAINT "chk_versiones_plan_plazo" CHECK ("plazo_cuotas" > 0)');
        $this->sql('ALTER TABLE "credimex"."versiones_plan" ADD CONSTRAINT "chk_versiones_plan_tasa" CHECK ("tasa" > 0)');
        $this->sql('ALTER TABLE "credimex"."solicitudes_credito" ADD CONSTRAINT "chk_solicitudes_credito_monto" CHECK ("monto_centavos" > 0)');
        $this->sql('ALTER TABLE "credimex"."solicitudes_credito" ADD CONSTRAINT "chk_solicitudes_credito_estado" CHECK ("estado" IN (\'BORRADOR\', \'PENDIENTE_AUTORIZACION\', \'DEVUELTA\', \'APROBADA\', \'RECHAZADA\', \'CANCELADA\'))');
        $this->sql('ALTER TABLE "credimex"."autorizaciones_credito" ADD CONSTRAINT "chk_autorizaciones_credito_resultado" CHECK ("resultado" IN (\'APROBADA\', \'RECHAZADA\', \'DEVUELTA\'))');
        $this->sql('ALTER TABLE "credimex"."autorizaciones_credito" ADD CONSTRAINT "chk_autorizaciones_credito_monto" CHECK ("monto_centavos" IS NULL OR "monto_centavos" > 0)');
        $this->sql('ALTER TABLE "credimex"."creditos" ADD CONSTRAINT "chk_creditos_estado" CHECK ("estado" IN (\'PENDIENTE_DESEMBOLSO\', \'ACTIVO\', \'LIQUIDADO\', \'CANCELADO\', \'CASTIGADO\'))');
        $this->sql('ALTER TABLE "credimex"."creditos" ADD CONSTRAINT "chk_creditos_saldo_inicial" CHECK ("saldo_inicial_centavos" > 0)');
        $this->sql('ALTER TABLE "credimex"."creditos" ADD CONSTRAINT "chk_creditos_saldo_actual" CHECK ("saldo_actual_centavos" >= 0)');
        $this->sql('ALTER TABLE "credimex"."creditos" ADD CONSTRAINT "chk_creditos_dias_atraso" CHECK ("dias_atraso_actual" >= 0)');
        $this->sql('ALTER TABLE "credimex"."creditos" ADD CONSTRAINT "chk_creditos_cuotas_vencidas" CHECK ("cuotas_vencidas_pendientes" >= 0)');
        $this->sql('ALTER TABLE "credimex"."creditos" ADD CONSTRAINT "chk_creditos_semaforo" CHECK ("semaforo_actual" IN (\'VERDE\', \'AMARILLO\', \'NARANJA\', \'ROJO\', \'VENCIDO\'))');
        $this->sql('ALTER TABLE "credimex"."versiones_condiciones_credito" ADD CONSTRAINT "chk_versiones_condiciones_montos" CHECK ("monto_centavos" > 0 AND "interes_centavos" >= 0 AND "comision_centavos" >= 0 AND "total_a_pagar_centavos" = "monto_centavos" + "interes_centavos" AND "cuota_base_centavos" >= 0 AND "plazo_cuotas" > 0 AND "tasa" > 0)');
        $this->sql('ALTER TABLE "credimex"."versiones_condiciones_credito" ADD CONSTRAINT "chk_versiones_condiciones_origen" CHECK ("origen" IN (\'AUTORIZACION\', \'REESTRUCTURA\'))');
    }

    private function datosIniciales(CarbonImmutable $ahora): void
    {
        $filas = [
            'credito_monto_minimo_centavos' => '100000',
            'credito_limite_cobrador_centavos' => '400000',
            'credito_comision_centavos_por_100_pesos' => '2000',
            'credito_maximo_activos' => '5',
        ];

        foreach ($filas as $clave => $valor) {
            DB::table('parametros_sistema')->insert([
                'clave' => $clave,
                'valor' => $valor,
                'vigente_desde' => $ahora,
            ]);
        }

        foreach (self::PLANES as $plan) {
            $planId = DB::table('planes_credito')->insertGetId([
                'nombre' => $plan['nombre'],
                'estado' => 'ACTIVO',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('versiones_plan')->insert([
                'plan_credito_id' => $planId,
                'plazo_cuotas' => $plan['plazo'],
                'tasa' => $plan['tasa'],
                'vigente_desde' => $ahora,
            ]);
        }
    }

    private function conceder(): void
    {
        $grants = PostgreSqlGrantManager::fromOwnerMigration(app());
        $grants->grantTable('parametros_sistema', ['SELECT']);
        $grants->grantTable('planes_credito', ['SELECT']);
        $grants->grantTable('versiones_plan', ['SELECT']);
        $grants->grantTable('solicitudes_credito', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->grantTable('autorizaciones_credito', ['SELECT', 'INSERT']);
        $grants->grantTable('creditos', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->grantTable('versiones_condiciones_credito', ['SELECT', 'INSERT']);

        foreach (self::SECUENCIAS as $secuencia) {
            $grants->grantSequence($secuencia, ['USAGE']);
        }
    }

    private function revocar(): void
    {
        $grants = PostgreSqlGrantManager::fromOwnerMigration(app());

        foreach (array_reverse(self::SECUENCIAS) as $secuencia) {
            $grants->revokeSequence($secuencia, ['USAGE']);
        }

        $grants->revokeTable('versiones_condiciones_credito', ['SELECT', 'INSERT']);
        $grants->revokeTable('creditos', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->revokeTable('autorizaciones_credito', ['SELECT', 'INSERT']);
        $grants->revokeTable('solicitudes_credito', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->revokeTable('versiones_plan', ['SELECT']);
        $grants->revokeTable('planes_credito', ['SELECT']);
        $grants->revokeTable('parametros_sistema', ['SELECT']);
    }

    private function sql(string $sentencia): void
    {
        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->statement($sentencia);
    }
};
