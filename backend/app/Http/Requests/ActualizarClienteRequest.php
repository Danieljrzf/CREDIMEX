<?php

namespace App\Http\Requests;

use App\Infrastructure\Pii\ProtectorTelefono;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ActualizarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'filled'],
            'telefono_principal' => ['sometimes', 'string'],
            'observaciones' => ['sometimes', 'nullable', 'string'],
            'estado' => ['sometimes', 'in:ACTIVO,INACTIVO'],
            'ruta_id_publico' => ['sometimes', 'uuid'],
            'contacto_alternativo' => ['sometimes', 'array'],
            'contacto_alternativo.nombre' => ['required_with:contacto_alternativo', 'string', 'filled'],
            'contacto_alternativo.telefono' => ['required_with:contacto_alternativo', 'string'],
            'contacto_alternativo.relacion' => ['required_with:contacto_alternativo', 'string', 'filled'],
            'referencia' => ['sometimes', 'array'],
            'referencia.nombre' => ['required_with:referencia', 'string', 'filled'],
            'referencia.telefono' => ['required_with:referencia', 'string'],
            'referencia.relacion' => ['required_with:referencia', 'string', 'filled'],
            'referencia.direccion' => ['nullable', 'string'],
            'referencia.observaciones' => ['nullable', 'string'],
            'domicilio' => ['sometimes', 'array'],
            'domicilio.direccion' => ['required_with:domicilio', 'string', 'filled'],
            'domicilio.latitud' => ['required_with:domicilio', 'numeric', 'between:-90,90'],
            'domicilio.longitud' => ['required_with:domicilio', 'numeric', 'between:-180,180'],
            'domicilio.motivo_cambio' => ['required_with:domicilio', 'string', 'filled'],
            'documentos' => ['sometimes', 'array'],
            'documentos.ine' => ['sometimes', 'string', 'filled'],
            'documentos.comprobante_domicilio' => ['sometimes', 'string', 'filled'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarTelefono($validator, 'telefono_principal');
            $this->validarTelefono($validator, 'contacto_alternativo.telefono');
            $this->validarTelefono($validator, 'referencia.telefono');
        });
    }

    private function validarTelefono(Validator $validator, string $campo): void
    {
        if (! $this->exists($campo)) {
            return;
        }

        $valor = $this->input($campo);

        if (! is_string($valor)) {
            return;
        }

        if (strlen(app(ProtectorTelefono::class)->canonico($valor)) < 4) {
            $validator->errors()->add($campo, 'El teléfono no tiene suficientes dígitos.');
        }
    }
}
