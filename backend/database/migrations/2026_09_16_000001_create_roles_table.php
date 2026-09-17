<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'roles';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->string('codigo', 64);
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->unique('codigo', 'uq_roles_codigo');
        });

        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->statement(
            <<<'SQL'
                ALTER TABLE "credimex"."roles"
                ADD CONSTRAINT "chk_roles_codigo"
                CHECK ("codigo" ~ '^[a-z][a-z0-9_]*$')
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
