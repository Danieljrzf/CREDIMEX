<?php

namespace App\Http\Requests;

use App\Infrastructure\Pii\ProtectorTelefono;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegistrarClienteRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'filled'],
            'telefono_principal' => ['required', 'string'],
            'observaciones' => ['nullable', 'string'],
            'ruta_id_publico' => ['required', 'uuid'],
            'contacto_alternativo' => ['required', 'array'],
            'contacto_alternativo.nombre' => ['required', 'string', 'filled'],
            'contacto_alternativo.telefono' => ['required', 'string'],
            'contacto_alternativo.relacion' => ['required', 'string', 'filled'],
            'referencia' => ['required', 'array'],
            'referencia.nombre' => ['required', 'string', 'filled'],
            'referencia.telefono' => ['required', 'string'],
            'referencia.relacion' => ['required', 'string', 'filled'],
            'referencia.direccion' => ['nullable', 'string'],
            'referencia.observaciones' => ['nullable', 'string'],
            'domicilio' => ['required', 'array'],
            'domicilio.direccion' => ['required', 'string', 'filled'],
            'domicilio.latitud' => ['required', 'numeric', 'between:-90,90'],
            'domicilio.longitud' => ['required', 'numeric', 'between:-180,180'],
            'documentos' => ['required', 'array'],
            'documentos.ine' => ['required', 'string', 'filled'],
            'documentos.comprobante_domicilio' => ['required', 'string', 'filled'],
            'confirmar_no_duplicado' => ['sometimes', 'boolean'],
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
        $valor = $this->input($campo);

        if (! is_string($valor)) {
            return;
        }

        if (strlen(app(ProtectorTelefono::class)->canonico($valor)) < 4) {
            $validator->errors()->add($campo, 'El teléfono no tiene suficientes dígitos.');
        }
    }
}
