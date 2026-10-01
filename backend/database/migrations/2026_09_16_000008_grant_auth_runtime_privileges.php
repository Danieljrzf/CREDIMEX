<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const TABLE = 'sesiones_token';

    private const SEQUENCE = 'sesiones_token_id_seq';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        $grants = PostgreSqlGrantManager::fromOwnerMigration(app());

        $grants->grantTable(self::TABLE, ['INSERT', 'UPDATE']);
        $grants->grantSequence(self::SEQUENCE, ['USAGE']);
    }

    public function down(): void
    {
        $grants = PostgreSqlGrantManager::fromOwnerMigration(app());

        $grants->revokeSequence(self::SEQUENCE, ['USAGE']);
        $grants->revokeTable(self::TABLE, ['INSERT', 'UPDATE']);
    }
};
