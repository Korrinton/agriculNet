<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_meteorologicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estacion_id')->constrained('estaciones_meteorologicas')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('temp_max', 5, 2)->nullable();
            $table->decimal('temp_min', 5, 2)->nullable();
            $table->decimal('precipitacion_mm', 6, 2)->nullable();
            $table->tinyInteger('humedad_pct')->unsigned()->nullable();
            $table->decimal('viento_kmh', 5, 1)->nullable();
            $table->timestamps();

            $table->unique(['estacion_id', 'fecha']);
            $table->index(['estacion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_meteorologicos');
    }
};
