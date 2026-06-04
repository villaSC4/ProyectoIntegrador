<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Horario extends Model
{
    protected $keyType = 'integer';
    protected $fillable = ['dia_semana', 'hora_inicio', 'hora_fin', 'turno'];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function doctores(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_horario', 'horario_id', 'doctor_id')
                    ->withTimestamps();
    }
}