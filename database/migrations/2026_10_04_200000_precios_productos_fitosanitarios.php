<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada agricultor paga su precio, también por los productos compartidos del registro del MAPA
        Schema::create('precios_productos_fitosanitarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos_fitosanitarios')->cascadeOnDelete();
            // € por litro (o kg): la misma unidad que la dosis
            $table->decimal('precio', 10, 2);
            $table->timestamps();

            $table->unique(['user_id', 'producto_id']);
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            // Precio con el que se calculó el coste, aunque después cambie el del producto
            $table->decimal('precio_unitario', 10, 2)->nullable()->after('plazo_seguridad_dias');
        });

        Schema::table('costes', function (Blueprint $table) {
            // Coste generado al registrar un tratamiento: se borra con él
            $table->foreignId('tratamiento_id')->nullable()->after('categoria_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('costes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tratamiento_id');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn('precio_unitario');
        });

        Schema::dropIfExists('precios_productos_fitosanitarios');
    }
};
