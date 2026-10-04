<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos que exige el cuaderno de explotación (Orden APA/204/2023) y que faltaban:
 * titular de la explotación, detalle de los tratamientos, fertilización y cosecha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->string('titular_nombre', 255)->nullable();
            $table->string('titular_nif', 20)->nullable();
            $table->string('rea_numero', 50)->nullable()->comment('Nº de inscripción en el Registro de Explotaciones Agrícolas');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->decimal('superficie_tratada_ha', 8, 4)->nullable()->after('dosis_l_ha');
            $table->string('aplicador_nombre', 255)->nullable();
            $table->string('aplicador_ropo', 50)->nullable()->comment('Nº de carné/inscripción ROPO del aplicador');
            $table->string('equipo_roma', 50)->nullable()->comment('Nº de inscripción ROMA del equipo de aplicación');
            $table->string('eficacia', 20)->nullable()->comment('buena, regular o mala');
        });

        Schema::create('fertilizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->string('tipo', 30);                       // mineral, organico, organo_mineral, enmienda
            $table->string('producto', 255);
            $table->decimal('riqueza_n', 5, 2)->nullable();   // % N
            $table->decimal('riqueza_p', 5, 2)->nullable();   // % P2O5
            $table->decimal('riqueza_k', 5, 2)->nullable();   // % K2O
            $table->decimal('dosis', 10, 3);
            $table->string('unidad', 10);                     // kg/ha, t/ha, l/ha, m3/ha
            $table->decimal('superficie_ha', 8, 4);
            $table->string('metodo', 30)->nullable();         // voleo, localizado, fertirrigacion, foliar, enterrado
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha']);
        });

        Schema::create('cosechas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->string('producto', 100);
            $table->decimal('cantidad_kg', 12, 2);
            $table->decimal('superficie_ha', 8, 4)->nullable();
            $table->string('destino', 255)->nullable();       // bodega, cooperativa, almazara…
            $table->string('destinatario_nif', 20)->nullable();
            $table->string('albaran', 50)->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->index(['parcela_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosechas');
        Schema::dropIfExists('fertilizaciones');

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn(['superficie_tratada_ha', 'aplicador_nombre', 'aplicador_ropo', 'equipo_roma', 'eficacia']);
        });

        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['titular_nombre', 'titular_nif', 'rea_numero']);
        });
    }
};
