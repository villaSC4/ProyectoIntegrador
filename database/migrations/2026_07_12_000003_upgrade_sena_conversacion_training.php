<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sena_conversacion_etiquetas')) {
            Schema::create('sena_conversacion_etiquetas', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 100)->unique();
                $table->string('texto', 180);
                $table->string('texto_normalizado', 180)->unique();
                $table->boolean('activa')->default(true)->index();
                $table->unsignedInteger('muestras_validas')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('sena_conversacion_muestras', function (Blueprint $table) {
            $table->foreignId('etiqueta_id')->nullable()->after('id')->index();
            $table->string('version_modelo', 20)->default('v4.0')->after('features')->index();
            $table->decimal('calidad', 5, 4)->default(0)->after('version_modelo')->index();
            $table->unsignedInteger('duracion_ms')->default(0)->after('calidad');
            $table->unsignedSmallInteger('cantidad_frames')->default(0)->after('duracion_ms');
            $table->unsignedTinyInteger('manos_max')->default(0)->after('cantidad_frames');
            $table->string('tipo_movimiento', 20)->default('estatica')->after('manos_max')->index();
            $table->json('metricas')->nullable()->after('tipo_movimiento');
            $table->boolean('activa')->default(true)->after('metricas')->index();
        });
    }

    public function down(): void
    {
        Schema::table('sena_conversacion_muestras', function (Blueprint $table) {
            $table->dropColumn([
                'etiqueta_id',
                'version_modelo',
                'calidad',
                'duracion_ms',
                'cantidad_frames',
                'manos_max',
                'tipo_movimiento',
                'metricas',
                'activa',
            ]);
        });

        Schema::dropIfExists('sena_conversacion_etiquetas');
    }
};
