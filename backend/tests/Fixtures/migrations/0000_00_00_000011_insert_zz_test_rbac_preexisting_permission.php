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

        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)->table('permisos')->insert([
            'codigo' => 'clientes.consultar',
            'modulo' => 'clientes',
            'descripcion' => 'Descripción ajena al manifiesto',
            'activo' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    public function down(): void
    {
        DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION)
            ->table('permisos')
            ->where('codigo', 'clientes.consultar')
            ->delete();
    }
};
