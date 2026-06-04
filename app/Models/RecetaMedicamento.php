<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaMedicamento extends Model
{
    protected $table = 'receta_medicamentos';
    protected $keyType = 'integer';
    public $timestamps = false; 
    
    protected $fillable = [
        'receta_medica_id', 'nombre_medicamento', 'presentacion', 'via_administracion',
        'dosis', 'frecuencia', 'duracion', 'cantidad', 'indicaciones_especificas'
    ];

    public function receta(): BelongsTo { return $this->belongsTo(RecetaMedica::class, 'receta_medica_id'); }
}