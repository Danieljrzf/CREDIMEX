<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const SECUENCIAS = [
        'clientes_id_seq',
        'contactos_alternativos_id_seq',
        'referencias_id_seq',
        'domicilios_ubicaciones_id_seq',
        'documentos_cliente_id_seq',
        'confirmaciones_no_duplicado_id_seq',
        'asignaciones_cliente_ruta_id_seq',
    ];

    /** @var list<string> */
    private const ELIMINAR = [
        'asignaciones_cliente_ruta',
        'confirmaciones_no_duplicado',
        'documentos_cliente',
        'domicilios_ubicaciones',
        'referencias',
        'contactos_alternativos',
        'clientes',
        'rutas',
    ];

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        $schema = Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION);

        $schema->create('clientes', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->uuid('id_publico');
            $table->text('nombre');
            $table->binary('telefono_principal_cifrado');
            $table->binary('telefono_principal_hmac');
            $table->string('telefono_principal_ultimos4', 4);
            $table->smallInteger('telefono_principal_version_clave');
            $table->string('estado', 64)->default('ACTIVO');
            $table->text('observaciones')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->unique('id_publico', 'uq_clientes_id_publico');
            $table->index('telefono_principal_hmac', 'idx_clientes_telefono_principal_hmac');
        });

        $schema->create('contactos_alternativos', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('cliente_id');
            $table->text('nombre');
            $table->binary('telefono_cifrado');
            $table->binary('telefono_hmac');
            $table->string('telefono_ultimos4', 4);
            $table->smallInteger('telefono_version_clave');
            $table->text('relacion');
            $table->boolean('vigente')->default(true);
            $table->timestampTz('vigente_desde');
            $table->timestampTz('vigente_hasta')->nullable();
            $table->foreign('cliente_id', 'fk_contactos_alternativos_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_contactos_alternativos_cliente_id');
            $table->index('telefono_hmac', 'idx_contactos_alternativos_telefono_hmac');
        });

        $schema->create('referencias', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('cliente_id');
            $table->text('nombre');
            $table->binary('telefono_cifrado');
            $table->binary('telefono_hmac');
            $table->string('telefono_ultimos4', 4);
            $table->smallInteger('telefono_version_clave');
            $table->text('relacion');
            $table->text('direccion')->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('vigente')->default(true);
            $table->foreign('cliente_id', 'fk_referencias_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_referencias_cliente_id');
            $table->index('telefono_hmac', 'idx_referencias_telefono_hmac');
        });

        $schema->create('domicilios_ubicaciones', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('cliente_id');
            $table->text('direccion');
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 10, 6);
            $table->timestampTz('capturado_en');
            $table->text('motivo_cambio')->nullable();
            $table->boolean('vigente')->default(true);
            $table->foreign('cliente_id', 'fk_domicilios_ubicaciones_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_domicilios_ubicaciones_cliente_id');
        });

        $schema->create('documentos_cliente', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->uuid('id_publico');
            $table->unsignedBigInteger('cliente_id');
            $table->string('tipo', 64);
            $table->text('archivo_privado');
            $table->unsignedBigInteger('capturista_id');
            $table->timestampTz('capturado_en');
            $table->unique('id_publico', 'uq_documentos_cliente_id_publico');
            $table->foreign('cliente_id', 'fk_documentos_cliente_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('capturista_id', 'fk_documentos_cliente_capturista_id')
                ->references('id')->on('usuarios')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_documentos_cliente_cliente_id');
        });

        $schema->create('confirmaciones_no_duplicado', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('cliente_id');
            $table->text('candidatos_json');
            $table->unsignedBigInteger('usuario_id');
            $table->timestampTz('confirmado_en');
            $table->foreign('cliente_id', 'fk_confirmaciones_no_duplicado_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('usuario_id', 'fk_confirmaciones_no_duplicado_usuario_id')
                ->references('id')->on('usuarios')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_confirmaciones_no_duplicado_cliente_id');
        });

        $schema->create('rutas', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->uuid('id_publico');
            $table->text('nombre');
            $table->string('estado', 64)->default('ACTIVA');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->unique('id_publico', 'uq_rutas_id_publico');
        });

        $schema->create('asignaciones_cliente_ruta', function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('ruta_id');
            $table->string('estado', 64)->default('VIGENTE');
            $table->text('motivo')->nullable();
            $table->timestampTz('vigente_desde');
            $table->timestampTz('vigente_hasta')->nullable();
            $table->foreign('cliente_id', 'fk_asignaciones_cliente_ruta_cliente_id')
                ->references('id')->on('clientes')->noActionOnUpdate()->noActionOnDelete();
            $table->foreign('ruta_id', 'fk_asignaciones_cliente_ruta_ruta_id')
                ->references('id')->on('rutas')->noActionOnUpdate()->noActionOnDelete();
            $table->index('cliente_id', 'idx_asignaciones_cliente_ruta_cliente_id');
            $table->index('ruta_id', 'idx_asignaciones_cliente_ruta_ruta_id');
        });

        $this->restricciones();
        $this->conceder();
    }

    public function down(): void
    {
        $this->revocar();

        $schema = Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION);

        foreach (self::ELIMINAR as $tabla) {
            $schema->drop($tabla);
        }
    }

    private function restricciones(): void
    {
        $this->sql('ALTER TABLE "credimex"."clientes" ADD CONSTRAINT "chk_clientes_estado" CHECK ("estado" IN (\'ACTIVO\', \'INACTIVO\'))');
        $this->sql('ALTER TABLE "credimex"."clientes" ADD CONSTRAINT "chk_clientes_tel_hmac_len" CHECK (octet_length("telefono_principal_hmac") = 32)');
        $this->sql('ALTER TABLE "credimex"."clientes" ADD CONSTRAINT "chk_clientes_tel_ultimos4" CHECK (char_length("telefono_principal_ultimos4") = 4 AND "telefono_principal_ultimos4" ~ \'^[0-9]{4}$\')');
        $this->sql('ALTER TABLE "credimex"."clientes" ADD CONSTRAINT "chk_clientes_tel_version" CHECK ("telefono_principal_version_clave" > 0)');

        $this->sql('ALTER TABLE "credimex"."contactos_alternativos" ADD CONSTRAINT "chk_contactos_tel_hmac_len" CHECK (octet_length("telefono_hmac") = 32)');
        $this->sql('ALTER TABLE "credimex"."contactos_alternativos" ADD CONSTRAINT "chk_contactos_tel_ultimos4" CHECK (char_length("telefono_ultimos4") = 4 AND "telefono_ultimos4" ~ \'^[0-9]{4}$\')');
        $this->sql('ALTER TABLE "credimex"."contactos_alternativos" ADD CONSTRAINT "chk_contactos_tel_version" CHECK ("telefono_version_clave" > 0)');

        $this->sql('ALTER TABLE "credimex"."referencias" ADD CONSTRAINT "chk_referencias_tel_hmac_len" CHECK (octet_length("telefono_hmac") = 32)');
        $this->sql('ALTER TABLE "credimex"."referencias" ADD CONSTRAINT "chk_referencias_tel_ultimos4" CHECK (char_length("telefono_ultimos4") = 4 AND "telefono_ultimos4" ~ \'^[0-9]{4}$\')');
        $this->sql('ALTER TABLE "credimex"."referencias" ADD CONSTRAINT "chk_referencias_tel_version" CHECK ("telefono_version_clave" > 0)');

        $this->sql('ALTER TABLE "credimex"."domicilios_ubicaciones" ADD CONSTRAINT "chk_domicilios_latitud" CHECK ("latitud" >= -90 AND "latitud" <= 90)');
        $this->sql('ALTER TABLE "credimex"."domicilios_ubicaciones" ADD CONSTRAINT "chk_domicilios_longitud" CHECK ("longitud" >= -180 AND "longitud" <= 180)');

        $this->sql('ALTER TABLE "credimex"."documentos_cliente" ADD CONSTRAINT "chk_documentos_cliente_tipo" CHECK ("tipo" IN (\'INE\', \'COMPROBANTE\'))');

        $this->sql('ALTER TABLE "credimex"."rutas" ADD CONSTRAINT "chk_rutas_estado" CHECK ("estado" IN (\'ACTIVA\', \'INACTIVA\'))');
        $this->sql('ALTER TABLE "credimex"."asignaciones_cliente_ruta" ADD CONSTRAINT "chk_asignaciones_cliente_ruta_estado" CHECK ("estado" IN (\'VIGENTE\', \'FINALIZADA\', \'ANULADA\'))');
    }

    private function conceder(): void
    {
        $grants = PostgreSqlGrantManager::fromOwnerMigration(app());
        $grants->grantTable('clientes', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->grantTable('contactos_alternativos', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->grantTable('referencias', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->grantTable('domicilios_ubicaciones', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->grantTable('documentos_cliente', ['SELECT', 'INSERT']);
        $grants->grantTable('confirmaciones_no_duplicado', ['SELECT', 'INSERT']);
        $grants->grantTable('rutas', ['SELECT']);
        $grants->grantTable('asignaciones_cliente_ruta', ['SELECT', 'INSERT', 'UPDATE']);

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

        $grants->revokeTable('asignaciones_cliente_ruta', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->revokeTable('rutas', ['SELECT']);
        $grants->revokeTable('confirmaciones_no_duplicado', ['SELECT', 'INSERT']);
        $grants->revokeTable('documentos_cliente', ['SELECT', 'INSERT']);
        $grants->revokeTable('domicilios_ubicaciones', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->revokeTable('referencias', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->revokeTable('contactos_alternativos', ['SELECT', 'INSERT', 'UPDATE']);
        $grants->revokeTable('clientes', ['SELECT', 'INSERT', 'UPDATE']);
    }

    private function sql(string $sentencia): void
    {
        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->statement($sentencia);
    }
};
