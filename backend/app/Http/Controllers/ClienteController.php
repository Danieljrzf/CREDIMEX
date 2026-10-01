<?php

namespace App\Http\Controllers;

use App\Clientes\ClienteOperacionRechazada;
use App\Clientes\ClienteServicio;
use App\Http\Requests\ActualizarClienteRequest;
use App\Http\Requests\RegistrarClienteRequest;
use App\Infrastructure\Pii\DatoPiiException;
use App\Infrastructure\Pii\TelefonoInsuficienteException;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    public function __construct(
        private readonly ClienteServicio $clientes,
    ) {}

    public function index(): JsonResponse
    {
        return $this->responder(fn (): array => $this->clientes->listar());
    }

    public function store(RegistrarClienteRequest $request): JsonResponse
    {
        return $this->responder(
            fn (): array => $this->clientes->registrar($request->validated(), $this->usuarioId($request)),
            201,
        );
    }

    public function show(string $idPublico): JsonResponse
    {
        return $this->responder(fn (): array => $this->clientes->consultar($idPublico));
    }

    public function update(ActualizarClienteRequest $request, string $idPublico): JsonResponse
    {
        return $this->responder(
            fn (): array => $this->clientes->actualizar($idPublico, $request->validated(), $this->usuarioId($request)),
        );
    }

    private function responder(callable $accion, int $estado = 200): JsonResponse
    {
        try {
            /** @var array<string, mixed>|list<array<string, mixed>> $cuerpo */
            $cuerpo = $accion();
        } catch (ClienteOperacionRechazada $rechazo) {
            return response()->json($rechazo->cuerpo(), $rechazo->estadoHttp());
        } catch (TelefonoInsuficienteException $insuficiente) {
            return response()->json(['message' => $insuficiente->getMessage()], 422);
        } catch (DatoPiiException) {
            return response()->json(['message' => 'No fue posible proteger el dato.'], 500);
        }

        return response()->json($cuerpo, $estado);
    }

    private function usuarioId(RegistrarClienteRequest|ActualizarClienteRequest $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }
}
