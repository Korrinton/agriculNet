<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcelas', function (Blueprint $table) {
            $table->string('uso', 100)->nullable()->after('nombre');
        });

        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['uso', 'hectareas']);
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->string('uso', 100)->nullable()->after('paraje');
            $table->decimal('hectareas', 8, 4)->default(0)->after('uso');
        });

        Schema::table('parcelas', function (Blueprint $table) {
            $table->dropColumn('uso');
        });
    }
};
