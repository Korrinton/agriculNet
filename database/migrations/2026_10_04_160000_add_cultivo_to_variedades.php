<?php

use Database\Seeders\VariedadSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cultivo al que pertenece la variedad: vid, olivo, pistacho o herbaceo (secano).
        // Las existentes son todas de vid.
        Schema::table('variedades', function (Blueprint $table) {
            $table->string('cultivo', 20)->default('vid')->after('nombre');
            $table->index('cultivo');
        });

        // El color (tinta/blanca/rosada) solo tiene sentido en la vid
        DB::statement('ALTER TABLE variedades ALTER COLUMN tipo DROP NOT NULL');
        DB::statement('ALTER TABLE variedades ALTER COLUMN tipo DROP DEFAULT');

        // Añade olivo, pistacho y herbáceos (el seeder es idempotente)
        (new VariedadSeeder)->run();
    }

    public function down(): void
    {
        DB::table('variedades')->where('cultivo', '!=', 'vid')->delete();
        DB::statement("ALTER TABLE variedades ALTER COLUMN tipo SET DEFAULT 'tinta'");
        DB::statement('ALTER TABLE variedades ALTER COLUMN tipo SET NOT NULL');

        Schema::table('variedades', function (Blueprint $table) {
            $table->dropIndex(['cultivo']);
            $table->dropColumn('cultivo');
        });
    }
};
