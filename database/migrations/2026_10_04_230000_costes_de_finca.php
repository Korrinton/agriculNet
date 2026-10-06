<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Todo coste es de una finca; sin parcela es un gasto general de la finca (seguro, gestoría…),
        // que no se reparte entre las parcelas
        Schema::table('costes', function (Blueprint $table) {
            $table->foreignId('finca_id')->nullable()->after('id')->constrained('fincas')->cascadeOnDelete();
        });

        DB::statement('UPDATE costes SET finca_id = parcelas.finca_id FROM parcelas WHERE parcelas.id = costes.parcela_id');

        Schema::table('costes', function (Blueprint $table) {
            $table->foreignId('finca_id')->nullable(false)->change();
            $table->foreignId('parcela_id')->nullable()->change();
            $table->index(['finca_id', 'fecha']);
        });
    }

    public function down(): void
    {
        // Los gastos generales no tienen parcela a la que volver
        DB::table('costes')->whereNull('parcela_id')->delete();

        Schema::table('costes', function (Blueprint $table) {
            $table->dropIndex(['finca_id', 'fecha']);
            $table->foreignId('parcela_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('finca_id');
        });
    }
};
