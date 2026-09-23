<?php

use App\Infrastructure\Database\PostgreSqlBooleanConverter;
use App\Infrastructure\Database\PostgreSqlGrantManager;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONNECTION = PostgreSqlGrantManager::OWNER_CONNECTION;

    private const PERMISSION_CODIGO_PATTERN = '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/';

    private const COBRADOR_EXCLUDED = [
        'asignaciones.asignar',
        'asignaciones.reasignar',
        'reportes.consultar',
        'auditoria.consultar',
    ];

    /** @var list<array{codigo: string, nombre: string, activo: bool}> */
    private const ROLES = [
        ['codigo' => 'cobrador', 'nombre' => 'Cobrador', 'activo' => true],
        ['codigo' => 'supervisor', 'nombre' => 'Supervisor', 'activo' => true],
        ['codigo' => 'administrador', 'nombre' => 'Administrador', 'activo' => true],
    ];

    /** @var list<array{codigo: string, modulo: string, descripcion: string, activo: bool}> */
    private const PERMISOS = [
        ['codigo' => 'clientes.consultar', 'modulo' => 'clientes', 'descripcion' => 'Consultar clientes', 'activo' => true],
        ['codigo' => 'clientes.registrar', 'modulo' => 'clientes', 'descripcion' => 'Registrar clientes', 'activo' => true],
        ['codigo' => 'clientes.editar', 'modulo' => 'clientes', 'descripcion' => 'Editar clientes', 'activo' => true],
        ['codigo' => 'creditos.consultar', 'modulo' => 'creditos', 'descripcion' => 'Consultar créditos', 'activo' => true],
        ['codigo' => 'creditos.autorizar', 'modulo' => 'creditos', 'descripcion' => 'Autorizar créditos', 'activo' => true],
        ['codigo' => 'creditos.renovar', 'modulo' => 'creditos', 'descripcion' => 'Renovar créditos', 'activo' => true],
        ['codigo' => 'pagos.registrar', 'modulo' => 'pagos', 'descripcion' => 'Registrar pagos', 'activo' => true],
        ['codigo' => 'caja.consultar', 'modulo' => 'caja', 'descripcion' => 'Consultar caja', 'activo' => true],
        ['codigo' => 'rutas.consultar', 'modulo' => 'rutas', 'descripcion' => 'Consultar rutas', 'activo' => true],
        ['codigo' => 'asignaciones.asignar', 'modulo' => 'asignaciones', 'descripcion' => 'Asignar cobradores', 'activo' => true],
        ['codigo' => 'asignaciones.reasignar', 'modulo' => 'asignaciones', 'descripcion' => 'Reasignar cobradores', 'activo' => true],
        ['codigo' => 'reportes.consultar', 'modulo' => 'reportes', 'descripcion' => 'Consultar reportes', 'activo' => true],
        ['codigo' => 'auditoria.consultar', 'modulo' => 'auditoria', 'descripcion' => 'Consultar auditoría', 'activo' => true],
    ];

    /** @var list<array{rol: string, permiso: string}> */
    private const MATRIZ = [
        ['rol' => 'cobrador', 'permiso' => 'clientes.consultar'],
        ['rol' => 'cobrador', 'permiso' => 'clientes.registrar'],
        ['rol' => 'cobrador', 'permiso' => 'clientes.editar'],
        ['rol' => 'cobrador', 'permiso' => 'creditos.consultar'],
        ['rol' => 'cobrador', 'permiso' => 'creditos.autorizar'],
        ['rol' => 'cobrador', 'permiso' => 'creditos.renovar'],
        ['rol' => 'cobrador', 'permiso' => 'pagos.registrar'],
        ['rol' => 'cobrador', 'permiso' => 'caja.consultar'],
        ['rol' => 'cobrador', 'permiso' => 'rutas.consultar'],
        ['rol' => 'supervisor', 'permiso' => 'clientes.consultar'],
        ['rol' => 'supervisor', 'permiso' => 'clientes.registrar'],
        ['rol' => 'supervisor', 'permiso' => 'clientes.editar'],
        ['rol' => 'supervisor', 'permiso' => 'creditos.consultar'],
        ['rol' => 'supervisor', 'permiso' => 'creditos.autorizar'],
        ['rol' => 'supervisor', 'permiso' => 'creditos.renovar'],
        ['rol' => 'supervisor', 'permiso' => 'pagos.registrar'],
        ['rol' => 'supervisor', 'permiso' => 'caja.consultar'],
        ['rol' => 'supervisor', 'permiso' => 'rutas.consultar'],
        ['rol' => 'supervisor', 'permiso' => 'asignaciones.asignar'],
        ['rol' => 'supervisor', 'permiso' => 'asignaciones.reasignar'],
        ['rol' => 'supervisor', 'permiso' => 'reportes.consultar'],
        ['rol' => 'supervisor', 'permiso' => 'auditoria.consultar'],
        ['rol' => 'administrador', 'permiso' => 'clientes.consultar'],
        ['rol' => 'administrador', 'permiso' => 'clientes.registrar'],
        ['rol' => 'administrador', 'permiso' => 'clientes.editar'],
        ['rol' => 'administrador', 'permiso' => 'creditos.consultar'],
        ['rol' => 'administrador', 'permiso' => 'creditos.autorizar'],
        ['rol' => 'administrador', 'permiso' => 'creditos.renovar'],
        ['rol' => 'administrador', 'permiso' => 'pagos.registrar'],
        ['rol' => 'administrador', 'permiso' => 'caja.consultar'],
        ['rol' => 'administrador', 'permiso' => 'rutas.consultar'],
        ['rol' => 'administrador', 'permiso' => 'asignaciones.asignar'],
        ['rol' => 'administrador', 'permiso' => 'asignaciones.reasignar'],
        ['rol' => 'administrador', 'permiso' => 'reportes.consultar'],
        ['rol' => 'administrador', 'permiso' => 'auditoria.consultar'],
    ];

    protected $connection = self::CONNECTION;

    public function up(): void
    {
        $this->assertManifest();

        $owner = $this->owner();
        $this->assertCanonicalRolesAbsent($owner);
        $this->assertCanonicalPermisosAbsent($owner);

        $timestamp = now()->utc();

        foreach ($this->roleInsertRows($timestamp) as $row) {
            $owner->table('roles')->insert($row);
        }

        foreach ($this->permisoInsertRows($timestamp) as $row) {
            $owner->table('permisos')->insert($row);
        }

        $roleIds = $this->resolveIds($owner, 'roles', $this->roleCodes());
        $permisoIds = $this->resolveIds($owner, 'permisos', $this->permisoCodes());

        foreach ($this->relationInsertRows($roleIds, $permisoIds, $timestamp) as $row) {
            $owner->table('rol_permisos')->insert($row);
        }

        $this->assertCanonicalSubset($owner, 'postcondicion');
    }

    public function down(): void
    {
        $this->assertManifest();

        $owner = $this->owner();
        $this->assertCanonicalSubset($owner, 'down');
        $this->assertNoAlienRelationsToCanonicalPermisos($owner);

        $roleIds = $this->resolveIds($owner, 'roles', $this->roleCodes());
        $permisoIds = $this->resolveIds($owner, 'permisos', $this->permisoCodes());
        $relationIds = $this->canonicalRelationIds($owner, $roleIds, $permisoIds);

        $deletedRelations = $owner->table('rol_permisos')->whereIn('id', $relationIds)->delete();
        $deletedPermisos = $owner->table('permisos')->whereIn('id', array_values($permisoIds))->delete();
        $deletedRoles = $owner->table('roles')->whereIn('id', array_values($roleIds))->delete();

        if ($deletedRelations !== 35 || $deletedPermisos !== 13 || $deletedRoles !== 3) {
            $this->failClosed('down', 'El borrado del manifiesto no coincidió con 35/13/3.');
        }
    }

    private function assertManifest(): void
    {
        if (count(self::ROLES) !== 3) {
            $this->failClosed('manifiesto', 'El manifiesto no contiene exactamente 3 roles.');
        }

        if (count(self::PERMISOS) !== 13) {
            $this->failClosed('manifiesto', 'El manifiesto no contiene exactamente 13 permisos.');
        }

        if (count(self::MATRIZ) !== 35) {
            $this->failClosed('manifiesto', 'El manifiesto no contiene exactamente 35 relaciones.');
        }

        $roleCodes = $this->roleCodes();
        $permisoCodes = $this->permisoCodes();

        if (count(array_unique($roleCodes)) !== 3) {
            $this->failClosed('manifiesto', 'Hay códigos de rol duplicados.');
        }

        if (count(array_unique($permisoCodes)) !== 13) {
            $this->failClosed('manifiesto', 'Hay códigos de permiso duplicados.');
        }

        $pairs = [];

        foreach (self::MATRIZ as $pair) {
            $key = $pair['rol'].'|'.$pair['permiso'];

            if (isset($pairs[$key])) {
                $this->failClosed('manifiesto', 'Hay pares rol-permiso duplicados.');
            }

            if (! in_array($pair['rol'], $roleCodes, true)) {
                $this->failClosed('manifiesto', 'La matriz referencia un rol inexistente.');
            }

            if (! in_array($pair['permiso'], $permisoCodes, true)) {
                $this->failClosed('manifiesto', 'La matriz referencia un permiso inexistente.');
            }

            $pairs[$key] = true;
        }

        foreach (self::PERMISOS as $permiso) {
            if (preg_match(self::PERMISSION_CODIGO_PATTERN, $permiso['codigo']) !== 1) {
                $this->failClosed('manifiesto', 'Un código de permiso no cumple la forma modulo.accion.');
            }

            $prefix = explode('.', $permiso['codigo'], 2)[0];

            if ($prefix !== $permiso['modulo']) {
                $this->failClosed('manifiesto', 'El prefijo de un permiso no coincide con su módulo.');
            }
        }

        $cobradorPermisos = $this->permisosForRole('cobrador');

        if (count($cobradorPermisos) !== 9) {
            $this->failClosed('manifiesto', 'El cobrador no tiene exactamente 9 permisos.');
        }

        if (array_intersect($cobradorPermisos, self::COBRADOR_EXCLUDED) !== []) {
            $this->failClosed('manifiesto', 'El cobrador contiene un permiso excluido.');
        }

        if (count($this->permisosForRole('supervisor')) !== 13) {
            $this->failClosed('manifiesto', 'El supervisor no tiene exactamente 13 permisos.');
        }

        if (count($this->permisosForRole('administrador')) !== 13) {
            $this->failClosed('manifiesto', 'El administrador no tiene exactamente 13 permisos.');
        }
    }

    private function assertCanonicalRolesAbsent(Connection $owner): void
    {
        $existing = $owner->table('roles')
            ->whereIn('codigo', $this->roleCodes())
            ->pluck('codigo');

        if ($existing->isNotEmpty()) {
            $this->failClosed('precondicion_roles', 'Existe un rol canónico previo.');
        }
    }

    private function assertCanonicalPermisosAbsent(Connection $owner): void
    {
        $existing = $owner->table('permisos')
            ->whereIn('codigo', $this->permisoCodes())
            ->pluck('codigo');

        if ($existing->isNotEmpty()) {
            $this->failClosed('precondicion_permisos', 'Existe un permiso canónico previo.');
        }
    }

    /**
     * @return list<array{codigo: string, nombre: string, activo: bool, created_at: Carbon, updated_at: Carbon}>
     */
    private function roleInsertRows(mixed $timestamp): array
    {
        return array_map(static fn (array $role): array => [
            'codigo' => $role['codigo'],
            'nombre' => $role['nombre'],
            'activo' => $role['activo'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], self::ROLES);
    }

    /**
     * @return list<array{codigo: string, modulo: string, descripcion: string, activo: bool, created_at: Carbon, updated_at: Carbon}>
     */
    private function permisoInsertRows(mixed $timestamp): array
    {
        return array_map(static fn (array $permiso): array => [
            'codigo' => $permiso['codigo'],
            'modulo' => $permiso['modulo'],
            'descripcion' => $permiso['descripcion'],
            'activo' => $permiso['activo'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], self::PERMISOS);
    }

    /**
     * @param  array<string, int>  $roleIds
     * @param  array<string, int>  $permisoIds
     * @return list<array{rol_id: int, permiso_id: int, created_at: mixed}>
     */
    private function relationInsertRows(array $roleIds, array $permisoIds, mixed $timestamp): array
    {
        return array_map(static fn (array $pair): array => [
            'rol_id' => $roleIds[$pair['rol']],
            'permiso_id' => $permisoIds[$pair['permiso']],
            'created_at' => $timestamp,
        ], self::MATRIZ);
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, int>
     */
    private function resolveIds(Connection $owner, string $table, array $codes): array
    {
        $rows = $owner->table($table)
            ->whereIn('codigo', $codes)
            ->get(['id', 'codigo']);

        if ($rows->count() !== count($codes)) {
            $this->failClosed('resolucion_ids', "La resolución de {$table} no devolvió el conjunto exacto.");
        }

        $ids = [];

        foreach ($rows as $row) {
            $codigo = (string) $row->codigo;

            if (isset($ids[$codigo])) {
                $this->failClosed('resolucion_ids', "Hay un código duplicado al resolver {$table}.");
            }

            $ids[$codigo] = (int) $row->id;
        }

        foreach ($codes as $codigo) {
            if (! isset($ids[$codigo]) || $ids[$codigo] < 1) {
                $this->failClosed('resolucion_ids', "Falta un id válido para un código de {$table}.");
            }
        }

        return $ids;
    }

    private function assertCanonicalSubset(Connection $owner, string $stage): void
    {
        $rolesByCodigo = [];

        foreach ($owner->table('roles')->whereIn('codigo', $this->roleCodes())->get() as $row) {
            $rolesByCodigo[(string) $row->codigo] = $row;
        }

        if (count($rolesByCodigo) !== 3) {
            $this->failClosed($stage, 'No existen exactamente los 3 roles canónicos.');
        }

        foreach (self::ROLES as $expected) {
            $actual = $rolesByCodigo[$expected['codigo']];

            if ((string) $actual->nombre !== $expected['nombre']
                || PostgreSqlBooleanConverter::toBool($actual->activo) !== $expected['activo']) {
                $this->failClosed($stage, 'Un rol canónico no coincide con el manifiesto.');
            }
        }

        $permisosByCodigo = [];

        foreach ($owner->table('permisos')->whereIn('codigo', $this->permisoCodes())->get() as $row) {
            $permisosByCodigo[(string) $row->codigo] = $row;
        }

        if (count($permisosByCodigo) !== 13) {
            $this->failClosed($stage, 'No existen exactamente los 13 permisos canónicos.');
        }

        foreach (self::PERMISOS as $expected) {
            $actual = $permisosByCodigo[$expected['codigo']];

            if ((string) $actual->modulo !== $expected['modulo']
                || (string) $actual->descripcion !== $expected['descripcion']
                || PostgreSqlBooleanConverter::toBool($actual->activo) !== $expected['activo']) {
                $this->failClosed($stage, 'Un permiso canónico no coincide con el manifiesto.');
            }
        }

        $actualPairs = [];

        foreach ($this->canonicalRelationRows($owner) as $row) {
            $key = $row->rol.'|'.$row->permiso;

            if (isset($actualPairs[$key])) {
                $this->failClosed($stage, 'Hay relaciones canónicas duplicadas.');
            }

            $actualPairs[$key] = true;
        }

        $expectedPairs = [];

        foreach (self::MATRIZ as $pair) {
            $expectedPairs[$pair['rol'].'|'.$pair['permiso']] = true;
        }

        $actualKeys = array_keys($actualPairs);
        $expectedKeys = array_keys($expectedPairs);
        sort($actualKeys);
        sort($expectedKeys);

        if ($actualKeys !== $expectedKeys || count($actualKeys) !== 35) {
            $this->failClosed(
                $stage === 'down' ? 'down' : 'relaciones',
                'Las relaciones canónicas no coinciden exactamente con las 35 aprobadas.',
            );
        }
    }

    private function assertNoAlienRelationsToCanonicalPermisos(Connection $owner): void
    {
        $exists = $owner->table('rol_permisos')
            ->join('roles', 'roles.id', '=', 'rol_permisos.rol_id')
            ->join('permisos', 'permisos.id', '=', 'rol_permisos.permiso_id')
            ->whereIn('permisos.codigo', $this->permisoCodes())
            ->whereNotIn('roles.codigo', $this->roleCodes())
            ->exists();

        if ($exists) {
            $this->failClosed('down', 'Un permiso canónico tiene relaciones ajenas.');
        }
    }

    /**
     * @return list<object{rol: string, permiso: string}>
     */
    private function canonicalRelationRows(Connection $owner): array
    {
        return $owner->table('rol_permisos')
            ->join('roles', 'roles.id', '=', 'rol_permisos.rol_id')
            ->join('permisos', 'permisos.id', '=', 'rol_permisos.permiso_id')
            ->whereIn('roles.codigo', $this->roleCodes())
            ->orderBy('roles.codigo')
            ->orderBy('permisos.codigo')
            ->select(['roles.codigo as rol', 'permisos.codigo as permiso'])
            ->get()
            ->all();
    }

    /**
     * @param  array<string, int>  $roleIds
     * @param  array<string, int>  $permisoIds
     * @return list<int>
     */
    private function canonicalRelationIds(Connection $owner, array $roleIds, array $permisoIds): array
    {
        $ids = [];

        foreach (self::MATRIZ as $pair) {
            $id = $owner->table('rol_permisos')
                ->where('rol_id', $roleIds[$pair['rol']])
                ->where('permiso_id', $permisoIds[$pair['permiso']])
                ->value('id');

            if ($id === null) {
                $this->failClosed('down', 'Falta una relación canónica antes del borrado.');
            }

            $ids[] = (int) $id;
        }

        if (count(array_unique($ids)) !== 35) {
            $this->failClosed('down', 'No se resolvieron 35 IDs distintos de relaciones.');
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function roleCodes(): array
    {
        return array_column(self::ROLES, 'codigo');
    }

    /**
     * @return list<string>
     */
    private function permisoCodes(): array
    {
        return array_column(self::PERMISOS, 'codigo');
    }

    /**
     * @return list<string>
     */
    private function permisosForRole(string $rol): array
    {
        $permisos = [];

        foreach (self::MATRIZ as $pair) {
            if ($pair['rol'] === $rol) {
                $permisos[] = $pair['permiso'];
            }
        }

        return $permisos;
    }

    private function owner(): Connection
    {
        return DB::connection(self::CONNECTION);
    }

    private function failClosed(string $stage, string $detail): never
    {
        throw new RuntimeException("RBAC inicial [{$stage}]: {$detail}");
    }
};
