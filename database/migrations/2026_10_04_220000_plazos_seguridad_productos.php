<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plazos de seguridad oficiales por cultivo, leídos de la ficha del producto en el registro del MAPA
        Schema::create('plazos_seguridad_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos_fitosanitarios')->cascadeOnDelete();
            $table->string('cultivo', 20);           // clave de ImportadorFitosanitarios::CULTIVOS_REGISTRO
            $table->unsignedSmallInteger('dias')->nullable(); // null = «NP», no procede
            $table->timestamps();

            $table->unique(['producto_id', 'cultivo']);
        });

        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->timestamp('ficha_leida_at')->nullable()->after('cultivos');
        });
    }

    public function down(): void
    {
        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->dropColumn('ficha_leida_at');
        });

        Schema::dropIfExists('plazos_seguridad_productos');
    }
};
