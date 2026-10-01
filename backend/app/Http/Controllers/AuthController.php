<?php

namespace App\Http\Controllers;

use App\Auth\ApiSesionAutenticador;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    private const MENSAJE_NO_AUTORIZADO = 'No autorizado.';

    public function __construct(
        private readonly ApiSesionAutenticador $autenticador,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $sesion = $this->autenticador->login(
            nombreUsuario: $request->string('nombre_usuario')->toString(),
            password: $request->string('password')->toString(),
            identificadorDispositivo: $request->string('identificador_dispositivo')->toString(),
        );

        if ($sesion === null) {
            return response()->json([
                'message' => self::MENSAJE_NO_AUTORIZADO,
            ], 401);
        }

        return response()->json($sesion);
    }

    public function logout(Request $request): JsonResponse|Response
    {
        if (! $this->autenticador->logout($request)) {
            return response()->json([
                'message' => self::MENSAJE_NO_AUTORIZADO,
            ], 401);
        }

        return response()->noContent();
    }
}
