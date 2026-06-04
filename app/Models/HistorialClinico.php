<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialClinico extends Model
{
    protected $table = 'historiales_clinicos';
    protected $keyType = 'integer';

    protected $fillable = [
        'user_id',
        'tipo_paciente',
        'bmi',
        'grupo_sanguineo',
        'alergias',
        'antecedentes_medicos',
    ];

    protected $casts = [
        'bmi' => 'float',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}