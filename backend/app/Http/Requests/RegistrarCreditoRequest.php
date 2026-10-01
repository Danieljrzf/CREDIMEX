<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'cliente_id_publico' => ['required', 'uuid'],
            'monto_centavos' => ['required', 'integer', 'min:1'],
            'plazo_cuotas' => ['required', 'integer', 'min:1'],
        ];
    }
}
