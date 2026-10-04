<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->foreignId('estacion_meteorologica_id')
                ->nullable()
                ->after('paraje')
                ->constrained('estaciones_meteorologicas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Modules\Meteorologia\Models\EstacionMeteorologica::class, 'estacion_meteorologica_id');
            $table->dropColumn('estacion_meteorologica_id');
        });
    }
};
