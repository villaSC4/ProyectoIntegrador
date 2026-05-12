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

    /**
     * Mutator para el campo 'turno'.
     * Esto asegura que siempre se guarde en minúsculas para coincidir 
     * con el ENUM de tu base de datos ('mañana', 'tarde', 'noche').
     */
    public function setTurnoAttribute($value)
    {
        $this->attributes['turno'] = strtolower($value);
    }

    /**
     * Casting de atributos.
     * Esto ayuda a que Laravel trate 'esta_activo' como booleano 
     * y 'cupos_disponibles' como entero automáticamente.
     */
    protected $casts = [
        'esta_activo' => 'boolean',
        'cupos_disponibles' => 'integer',
    ];
}