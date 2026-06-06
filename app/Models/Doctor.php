<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Filament\Forms\Components\CheckboxList;

class Doctor extends Model
{
    protected $table = 'doctores';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'especialidad_id',
        'cupos_disponibles',
        'ruta_imagen',
        'esta_activo'
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
    ];

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function horarios(): BelongsToMany
    {
        return $this->belongsToMany(Horario::class, 'doctor_horario', 'doctor_id', 'horario_id')
                    ->withTimestamps()
                    ->wherePivot('creado_en', now()) // Si manejas marcas de tiempo personalizadas en el pivote
                    ->withPivot('id');
    }

    public function reconocimientosFaciales(): MorphMany
    {
        return $this->morphMany(ReconocimientoFacial::class, 'biometria', 'biometria_type', 'biometria_id');
    }
}