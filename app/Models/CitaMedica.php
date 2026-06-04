<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CitaMedica extends Model
{
    protected $table = 'citas_medicas';
    protected $keyType = 'integer';

    protected $fillable = [
        'user_id', 'doctor_id', 'titulo', 'fecha_cita', 'hora_cita', 'estado',
        'motivo_consulta', 'sintomas', 'diagnostico', 'tratamiento', 'observaciones_adicionales'
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function receta(): HasOne
    {
        return $this->hasOne(RecetaMedica::class, 'cita_medica_id');
    }
}