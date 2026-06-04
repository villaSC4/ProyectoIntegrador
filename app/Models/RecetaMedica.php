<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecetaMedica extends Model
{
    protected $table = 'recetas_medicas';
    protected $keyType = 'integer';
    protected $fillable = ['cita_medica_id', 'fecha_emision', 'fecha_vigencia', 'indicaciones_generales'];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function cita(): BelongsTo { return $this->belongsTo(CitaMedica::class, 'cita_medica_id'); }
    
    public function medicamentos(): HasMany { return $this->hasMany(RecetaMedicamento::class, 'receta_medica_id'); }
}