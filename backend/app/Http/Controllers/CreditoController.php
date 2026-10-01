<?php

namespace App\Http\Controllers;

use App\Creditos\CreditoOperacionRechazada;
use App\Creditos\CreditoServicio;
use App\Http\Requests\RegistrarCreditoRequest;
use Illuminate\Http\JsonResponse;

class CreditoController extends Controller
{
    public function __construct(
        private readonly CreditoServicio $creditos,
    ) {}

    public function index(): JsonResponse
    {
        return $this->responder(fn (): array => $this->creditos->listar());
    }

    public function store(RegistrarCreditoRequest $request): JsonResponse
    {
        return $this->responder(
            fn (): array => $this->creditos->registrar($request->validated(), (int) $request->user()->getAuthIdentifier()),
            201,
        );
    }

    public function show(string $idPublico): JsonResponse
    {
        return $this->responder(fn (): array => $this->creditos->consultar($idPublico));
    }

    private function responder(callable $accion, int $estado = 200): JsonResponse
    {
        try {
            /** @var array<string, mixed>|list<array<string, mixed>> $cuerpo */
            $cuerpo = $accion();
        } catch (CreditoOperacionRechazada $rechazo) {
            return response()->json($rechazo->cuerpo(), $rechazo->estadoHttp());
        }

        return response()->json($cuerpo, $estado);
    }
}
