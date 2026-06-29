<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_fitosanitarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('numero_registro', 50)->nullable()->unique();
            $table->string('ingrediente_activo', 255)->nullable();
            $table->tinyInteger('plazo_seguridad_dias')->unsigned()->nullable();
            $table->decimal('dosis_max_l_ha', 6, 3)->nullable();
            $table->timestamps();

            $table->index('numero_registro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_fitosanitarios');
    }
};
