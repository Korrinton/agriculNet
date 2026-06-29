<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_costes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->enum('tipo', ['mano_obra', 'maquinaria', 'insumos', 'otros'])->default('otros');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_costes');
    }
};
