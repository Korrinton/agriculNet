<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backoffice: administradores, cuentas bloqueadas, último acceso (usuarios activos) y el
 * historial de ejecuciones de las tareas programadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->timestamp('bloqueado_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();
        });

        Schema::create('ejecuciones_tareas', function (Blueprint $table) {
            $table->id();
            $table->string('comando', 100);
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
            $table->smallInteger('codigo_salida')->nullable();
            $table->text('salida')->nullable();
            // Quién la lanzó desde el backoffice; null = el programador de tareas o la consola
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['comando', 'inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecuciones_tareas');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'bloqueado_at', 'ultimo_acceso_at']);
        });
    }
};
