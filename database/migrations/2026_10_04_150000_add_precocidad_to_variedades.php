<?php

use Database\Seeders\VariedadSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Época de maduración (temprana, media, tardia, muy_tardia): desplaza el
        // calendario fenológico de referencia de las parcelas de esa variedad.
        Schema::table('variedades', function (Blueprint $table) {
            $table->string('precocidad', 20)->nullable()->after('tipo');
        });

        // Rellena las variedades ya sembradas (el seeder es idempotente)
        (new VariedadSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('variedades', function (Blueprint $table) {
            $table->dropColumn('precocidad');
        });
    }
};
