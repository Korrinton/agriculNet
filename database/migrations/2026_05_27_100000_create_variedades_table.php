<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variedades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->enum('tipo', ['tinta', 'blanca', 'rosada'])->default('tinta');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variedades');
    }
};
