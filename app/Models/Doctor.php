<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctores'; 

    protected $fillable = [
        'nombre', 
        'especialidad', 
        'horario', 
        'turno', 
        'cupos_disponibles', 
        'ruta_imagen', 
        'esta_activo'
    ];

    public function setTurnoAttribute($value)
    {
        $this->attributes['turno'] = strtolower($value);
    }


    protected $casts = [
        'esta_activo' => 'boolean',
        'cupos_disponibles' => 'integer',
    ];
}