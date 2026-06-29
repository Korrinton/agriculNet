<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuaderno_entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->string('tipo', 100);
            $table->text('descripcion');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuaderno_entradas');
    }
};
