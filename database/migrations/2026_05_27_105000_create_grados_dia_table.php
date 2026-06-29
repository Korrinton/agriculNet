<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grados_dia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('acumulado', 8, 2);
            $table->decimal('temperatura_base', 4, 1)->default(10.0);
            $table->timestamps();

            $table->unique(['parcela_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grados_dia');
    }
};
