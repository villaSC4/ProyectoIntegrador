<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $table = 'doctores';
    protected $keyType = 'integer';

    protected $fillable = ['nombre', 'especialidad_id', 'cupos_disponibles', 'ruta_imagen', 'esta_activo'];

    protected $casts = [
        'esta_activo' => 'boolean',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function horarios(): BelongsToMany
    {
        return $this->belongsToMany(Horario::class, 'doctor_horario', 'doctor_id', 'horario_id')
                    ->withTimestamps();
    }

    public function reconocimientoFacial(): MorphOne
    {
        return $this->morphOne(ReconocimientoFacial::class, 'biometria');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaMedica::class, 'doctor_id');
    }
}