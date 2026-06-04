<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $keyType = 'integer';

    protected $fillable = ['dni', 'celular', 'email', 'nombre'];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function reconocimientoFacial(): MorphOne
    {
        return $this->morphOne(ReconocimientoFacial::class, 'biometria');
    }

    public function historialClinico(): HasOne
    {
        return $this->hasOne(HistorialClinico::class, 'user_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaMedica::class, 'user_id');
    }
}