<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $codigo = ''): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            throw new AuthenticationException;
        }

        $autorizado = DB::selectOne(
            'select 1 as ok
             from usuarios
             join roles on roles.id = usuarios.rol_id
             join rol_permisos on rol_permisos.rol_id = roles.id
             join permisos on permisos.id = rol_permisos.permiso_id
             where usuarios.id = ?
               and roles.activo is true
               and permisos.activo is true
               and permisos.codigo = ?',
            [$usuario->getAuthIdentifier(), $codigo],
        );

        if ($autorizado === null) {
            return response()->json([
                'message' => 'Prohibido.',
            ], 403);
        }

        return $next($request);
    }
}
