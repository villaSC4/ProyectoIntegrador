<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Administrador extends Authenticatable
{
    protected $table = 'administradores';
    protected $keyType = 'integer';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
}