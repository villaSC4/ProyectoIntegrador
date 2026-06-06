<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReconocimientoFacial extends Model
{
    protected $table = 'reconocimientos_faciales';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['vector_facial', 'biometria_id', 'biometria_type'];

    protected $casts = [
        'vector_facial' => 'array', 
    ];

    public function biometria(): MorphTo
    {
        return $this->morphTo();
    }
}