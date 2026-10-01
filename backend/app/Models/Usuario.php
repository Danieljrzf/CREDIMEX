<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'usuarios';

    protected $hidden = [
        'credencial_hash',
    ];

    public function getAuthPasswordName(): string
    {
        return 'credencial_hash';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
