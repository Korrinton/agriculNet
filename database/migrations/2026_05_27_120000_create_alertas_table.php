<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 100);
            $table->enum('nivel', ['info', 'warning', 'critical'])->default('info');
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'leida']);
            $table->index(['parcela_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
