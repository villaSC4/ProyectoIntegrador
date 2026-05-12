<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sunat extends Model
{
    protected $table = 'sunat';

    protected $primaryKey = 'dni';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['dni', 'nombre_completo', 'fecha_nacimiento'];
}