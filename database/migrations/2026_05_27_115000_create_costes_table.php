<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categoria_costes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('importe', 10, 2);
            $table->string('descripcion', 500)->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costes');
    }
};
