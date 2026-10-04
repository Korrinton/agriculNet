<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubicación aproximada de la finca, obtenida de la geometría SIGPAC de sus parcelas.
        // coordenadas_origen: 'parcela', 'poligono' o 'no_disponible' (ya se intentó y no hubo datos).
        Schema::table('fincas', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('coordenadas_origen', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud', 'coordenadas_origen']);
        });
    }
};
