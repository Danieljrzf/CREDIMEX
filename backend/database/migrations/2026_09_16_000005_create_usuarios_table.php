<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'usuarios';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->uuid('id_publico');
            $table->string('nombre', 160);
            $table->string('nombre_usuario', 64);
            $table->text('credencial_hash');
            $table->string('estado', 16)->default('ACTIVO');
            $table->unsignedBigInteger('rol_id');
            $table->boolean('es_cobrador')->default(false);
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->unique('id_publico', 'uq_usuarios_id_publico');
            $table->unique('nombre_usuario', 'uq_usuarios_nombre_usuario');
            $table->foreign('rol_id', 'fk_usuarios_rol_id')
                ->references('id')
                ->on('roles')
                ->noActionOnUpdate()
                ->noActionOnDelete();
            $table->index('rol_id', 'idx_usuarios_rol_id');
        });

        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."usuarios"
                ADD CONSTRAINT "chk_usuarios_estado"
                CHECK ("estado" IN ('ACTIVO', 'BLOQUEADO'))
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
