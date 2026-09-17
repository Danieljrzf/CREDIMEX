<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'permisos';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->string('codigo', 96);
            $table->string('modulo', 64);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->unique('codigo', 'uq_permisos_codigo');
        });

        $owner = DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION);

        $owner->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."permisos"
                ADD CONSTRAINT "chk_permisos_modulo"
                CHECK ("modulo" ~ '^[a-z][a-z0-9_]*$')
                SQL
        );
        $owner->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."permisos"
                ADD CONSTRAINT "chk_permisos_codigo"
                CHECK ("codigo" ~ '^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$')
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
