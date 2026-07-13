<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sena_conversacion_muestras MODIFY codigo VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE sena_conversacion_muestras MODIFY texto VARCHAR(180) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sena_conversacion_muestras MODIFY codigo VARCHAR(40) NOT NULL');
        DB::statement('ALTER TABLE sena_conversacion_muestras MODIFY texto VARCHAR(120) NOT NULL');
    }
};
