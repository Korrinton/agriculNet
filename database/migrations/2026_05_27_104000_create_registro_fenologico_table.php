<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_fenologico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('estado_fenologico_id')->constrained('estados_fenologicos');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha_observacion');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha_observacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_fenologico');
    }
};
