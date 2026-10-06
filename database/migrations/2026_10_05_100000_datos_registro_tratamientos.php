<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos del registro de tratamientos que exigen el Reglamento de Ejecución (UE) 2023/564
 * (hora de inicio, cultivo con código EPPO, estadio BBCH) y la Orden APA/204/2023
 * (justificación, NIF del aplicador, asesor y equipo con su inspección).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->time('hora_inicio')->nullable()->after('fecha');
            $table->string('cultivo_eppo', 10)->nullable()->after('superficie_tratada_ha');
            $table->string('bbch', 2)->nullable()->after('cultivo_eppo');
            $table->string('justificacion', 500)->nullable()->after('motivo');
            $table->string('aplicador_nif', 20)->nullable()->after('aplicador_nombre');
            $table->date('equipo_inspeccion_fecha')->nullable()->after('equipo_roma');
            $table->string('asesor_nombre')->nullable();
            $table->string('asesor_nif', 20)->nullable();
            $table->string('asesor_ropo', 50)->nullable();
            $table->date('asesor_fecha_validacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn([
                'hora_inicio', 'cultivo_eppo', 'bbch', 'justificacion', 'aplicador_nif', 'equipo_inspeccion_fecha',
                'asesor_nombre', 'asesor_nif', 'asesor_ropo', 'asesor_fecha_validacion',
            ]);
        });
    }
};
