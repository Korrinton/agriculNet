<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riegos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('volumen_m3', 10, 2);
            $table->decimal('superficie_ha', 8, 4);
            $table->decimal('duracion_horas', 6, 2)->nullable();
            $table->string('sistema', 20);              // goteo, microaspersion, aspersion, pivot, gravedad
            $table->string('origen', 30)->nullable();   // pozo, comunidad_regantes, red, balsa, otro
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riegos');
    }
};
