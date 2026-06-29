<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'latitud', 'longitud']);

            $table->string('paraje', 255)->nullable()->after('municipio');
            $table->string('poligono', 20)->nullable()->after('paraje');
            $table->string('uso', 100)->nullable()->after('poligono');
            $table->renameColumn('superficie_ha', 'hectareas');
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['paraje', 'poligono', 'uso']);

            $table->string('nombre', 255)->after('user_id');
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->renameColumn('hectareas', 'superficie_ha');
        });
    }
};
