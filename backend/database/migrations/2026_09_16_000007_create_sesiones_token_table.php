<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'sesiones_token';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('dispositivo_id')->nullable();
            $table->binary('token_hash');
            $table->timestampTz('expira_en');
            $table->string('estado', 16)->default('VIGENTE');

            $table->unique('token_hash', 'uq_sesiones_token_token_hash');
            $table->foreign('usuario_id', 'fk_sesiones_token_usuario_id')
                ->references('id')
                ->on('usuarios')
                ->noActionOnUpdate()
                ->noActionOnDelete();
            $table->foreign('dispositivo_id', 'fk_sesiones_token_dispositivo_id')
                ->references('id')
                ->on('dispositivos')
                ->noActionOnUpdate()
                ->noActionOnDelete();
            $table->index('usuario_id', 'idx_sesiones_token_usuario_id');
            $table->index('dispositivo_id', 'idx_sesiones_token_dispositivo_id');
        });

        $owner = DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION);
        $owner->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."sesiones_token"
                ADD CONSTRAINT "chk_sesiones_token_token_hash_length"
                CHECK (octet_length("token_hash") = 32)
                SQL
        );
        $owner->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."sesiones_token"
                ADD CONSTRAINT "chk_sesiones_token_estado"
                CHECK ("estado" IN ('VIGENTE', 'REVOCADA'))
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
