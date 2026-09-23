<?php

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROLE_CODIGO = 'prueba_ajeno';

    private const PERMISO_CODIGO = 'prueba.consultar';

    protected $connection = PostgreSqlGrantManager::OWNER_CONNECTION;

    public function up(): void
    {
        $owner = DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION);
        $timestamp = now()->utc();

        $owner->table('roles')->insert([
            'codigo' => self::ROLE_CODIGO,
            'nombre' => 'Prueba ajeno',
            'activo' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $owner->table('permisos')->insert([
            'codigo' => self::PERMISO_CODIGO,
            'modulo' => 'prueba',
            'descripcion' => 'Consultar prueba',
            'activo' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    public function down(): void
    {
        $owner = DB::connection(PostgreSqlGrantManager::OWNER_CONNECTION);

        if (! $owner->table('roles')->where('codigo', self::ROLE_CODIGO)->exists()
            || ! $owner->table('permisos')->where('codigo', self::PERMISO_CODIGO)->exists()) {
            throw new RuntimeException('RBAC inicial [down]: el dato ajeno no sobrevivió al rollback canónico.');
        }

        $owner->table('permisos')->where('codigo', self::PERMISO_CODIGO)->delete();
        $owner->table('roles')->where('codigo', self::ROLE_CODIGO)->delete();
    }
};
