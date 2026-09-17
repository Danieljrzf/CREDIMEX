<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'zz_test_owner_multi_child';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        Schema::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->create(self::TABLE, function (Blueprint $table): void {
            $table->id()->generatedAs()->always();
            $table->unsignedBigInteger('parent_a_id');
            $table->unsignedBigInteger('parent_b_id');
            $table->foreign('parent_a_id', 'fk_zz_test_owner_multi_child_parent_a')
                ->references('id')
                ->on('zz_test_owner_multi_parent_a')
                ->noActionOnDelete();
            $table->foreign('parent_b_id', 'fk_zz_test_owner_multi_child_parent_b')
                ->references('id')
                ->on('zz_test_owner_multi_parent_b')
                ->noActionOnDelete();
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
