<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'zz_test_owner_migration_harness';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->string('marker', 64);
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
