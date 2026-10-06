<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resumen diario de alertas por correo: qué alertas quiere recibir cada usuario y cuáles se
 * le han enviado ya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // todas, avisos (avisos y críticas), criticas o ninguna
            $table->string('alertas_por_correo', 10)->default('todas');
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->timestamp('notificada_at')->nullable();
            $table->index(['user_id', 'notificada_at']);
        });

        // Las alertas que ya existían se dan por vistas: el primer resumen no debe traer las antiguas
        DB::table('alertas')->update(['notificada_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'notificada_at']);
            $table->dropColumn('notificada_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('alertas_por_correo');
        });
    }
};
