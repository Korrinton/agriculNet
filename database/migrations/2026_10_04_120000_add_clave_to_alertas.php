<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            // Identifica el hecho que originó la alerta (p. ej. "helada:12:2026-04-03")
            // para que los generadores automáticos no la dupliquen.
            $table->string('clave', 150)->nullable()->after('tipo');
            $table->unique(['user_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'clave']);
            $table->dropColumn('clave');
        });
    }
};
