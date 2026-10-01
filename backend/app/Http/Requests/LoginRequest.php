<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'nombre_usuario' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string'],
            'identificador_dispositivo' => ['required', 'string', 'max:255'],
        ];
    }
}
