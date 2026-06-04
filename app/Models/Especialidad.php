<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends Model
{
    protected $table = 'especialidades';
    protected $keyType = 'integer';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function doctores(): HasMany
    {
        return $this->hasMany(Doctor::class, 'especialidad_id');
    }
}