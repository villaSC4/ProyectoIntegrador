<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'administradores';


    protected $fillable = [
        'nombre',
        'dni',
        'celular',
        'face_vector',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'face_vector' => 'array', 
    ];


    public function setNameAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }


    public function getNameAttribute(): string
    {
        return $this->nombre ?? 'Usuario';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }
}