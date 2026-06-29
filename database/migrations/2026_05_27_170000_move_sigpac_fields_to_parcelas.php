<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir campos SIGPAC a parcelas
        Schema::table('parcelas', function (Blueprint $table) {
            $table->smallInteger('agregado')->default(0)->after('nombre');
            $table->smallInteger('poligono')->nullable()->after('agregado');
            $table->smallInteger('parcela_sigpac')->nullable()->after('poligono');
            $table->smallInteger('recinto')->nullable()->after('parcela_sigpac');
        });

        // Eliminar campos SIGPAC de fincas
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['agregado', 'poligono', 'parcela_sigpac', 'recinto']);
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->smallInteger('agregado')->default(0)->after('municipio_cod');
            $table->smallInteger('poligono')->nullable()->after('agregado');
            $table->smallInteger('parcela_sigpac')->nullable()->after('poligono');
            $table->smallInteger('recinto')->nullable()->after('parcela_sigpac');
        });

        Schema::table('parcelas', function (Blueprint $table) {
            $table->dropColumn(['agregado', 'poligono', 'parcela_sigpac', 'recinto']);
        });
    }
};
