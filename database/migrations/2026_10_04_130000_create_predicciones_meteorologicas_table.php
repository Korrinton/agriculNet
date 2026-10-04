<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Predicción diaria AEMET por municipio (se comparte entre fincas del mismo municipio)
        Schema::create('predicciones_meteorologicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('provincia_cod');
            $table->unsignedSmallInteger('municipio_cod');
            $table->date('fecha');
            $table->decimal('temp_max', 5, 2)->nullable();
            $table->decimal('temp_min', 5, 2)->nullable();
            $table->unsignedTinyInteger('prob_precipitacion')->nullable();
            $table->unsignedTinyInteger('humedad_max')->nullable();
            $table->unsignedTinyInteger('humedad_min')->nullable();
            $table->string('estado_cielo', 100)->nullable();
            $table->timestamp('elaborado_at')->nullable();
            $table->timestamps();

            $table->unique(['provincia_cod', 'municipio_cod', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predicciones_meteorologicas');
    }
};
