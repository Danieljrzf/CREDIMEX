<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'rol_permisos';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('permiso_id');
            $table->timestampTz('created_at');

            $table->unique(['rol_id', 'permiso_id'], 'uq_rol_permisos_rol_id_permiso_id');
            $table->foreign('rol_id', 'fk_rol_permisos_rol_id')
                ->references('id')
                ->on('roles')
                ->noActionOnUpdate()
                ->noActionOnDelete();
            $table->foreign('permiso_id', 'fk_rol_permisos_permiso_id')
                ->references('id')
                ->on('permisos')
                ->noActionOnUpdate()
                ->noActionOnDelete();
            $table->index('permiso_id', 'idx_rol_permisos_permiso_id');
        });

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
