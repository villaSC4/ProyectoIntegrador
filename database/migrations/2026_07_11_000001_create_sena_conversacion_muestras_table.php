<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sena_conversacion_muestras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40);
            $table->string('texto', 120);
            $table->json('puntos');
            $table->json('features');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sena_conversacion_muestras');
    }
};
