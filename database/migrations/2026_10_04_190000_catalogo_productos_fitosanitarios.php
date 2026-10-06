<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            // null = producto del Registro oficial del MAPA, compartido; con valor = producto propio del usuario
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mapa_id')->nullable()->unique()->after('user_id');
            $table->string('titular', 255)->nullable()->after('ingrediente_activo');
            $table->boolean('vigente')->default(true)->after('titular');
            $table->date('fecha_caducidad')->nullable()->after('vigente');
            // Cultivos de la app (vid, olivo, pistacho) para los que el registro lo autoriza; null = sin dato
            $table->jsonb('cultivos')->nullable()->after('fecha_caducidad');

            // Un usuario puede dar de alta como propio un producto que también esté en el registro
            $table->dropUnique(['numero_registro']);
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            // El plazo de seguridad depende del cultivo y la plaga: se anota en cada tratamiento según la etiqueta
            $table->unsignedSmallInteger('plazo_seguridad_dias')->nullable()->after('dosis_l_ha');
        });
    }

    public function down(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn('plazo_seguridad_dias');
        });

        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['mapa_id', 'titular', 'vigente', 'fecha_caducidad', 'cultivos']);
            $table->unique('numero_registro');
        });
    }
};
