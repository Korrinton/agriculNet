<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estaciones_meteorologicas', function (Blueprint $table) {
            $table->unsignedTinyInteger('provincia_cod')->nullable()->after('codigo_externo');
            $table->index('provincia_cod');
        });
    }

    public function down(): void
    {
        Schema::table('estaciones_meteorologicas', function (Blueprint $table) {
            $table->dropIndex(['provincia_cod']);
            $table->dropColumn('provincia_cod');
        });
    }
};
