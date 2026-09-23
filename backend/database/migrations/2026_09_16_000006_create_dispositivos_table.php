<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'dispositivos';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('usuario_id');
            $table->string('identificador_dispositivo', 255);
            $table->string('estado', 16)->default('ACTIVO');
            $table->timestampTz('vinculado_en')->useCurrent();

            $table->unique('identificador_dispositivo', 'uq_dispositivos_identificador_dispositivo');
            $table->foreign('usuario_id', 'fk_dispositivos_usuario_id')
                ->references('id')
                ->on('usuarios')
                ->noActionOnUpdate()
                ->noActionOnDelete();
            $table->index('usuario_id', 'idx_dispositivos_usuario_id');
        });

        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."dispositivos"
                ADD CONSTRAINT "chk_dispositivos_estado"
                CHECK ("estado" IN ('ACTIVO', 'REVOCADO'))
                SQL
        );

        PostgreSqlGrantManager::fromOwnerMigration(app())
            ->grantTable(self::TABLE, ['SELECT']);
    }

    public function down(): void
    {
        PostgreSqlGrantManager::fromOwnerMigration(app())
            ->revokeTable(self::TABLE, ['SELECT']);

        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->drop(self::TABLE);
    }
};
