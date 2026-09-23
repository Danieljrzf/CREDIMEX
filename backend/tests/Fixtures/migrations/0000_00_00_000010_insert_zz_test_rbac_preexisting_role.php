<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        $timestamp = now()->utc();

        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->table('roles')->insert([
            'codigo' => 'cobrador',
            'nombre' => 'Cobrador',
            'activo' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    public function down(): void
    {
        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)
            ->table('roles')
            ->where('codigo', 'cobrador')
            ->delete();
    }
};
