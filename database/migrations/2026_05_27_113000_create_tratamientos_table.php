<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos_fitosanitarios')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('dosis_l_ha', 8, 4);
            $table->string('motivo', 500)->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
