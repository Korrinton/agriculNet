<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['provincia', 'municipio', 'municipio_ine']);
        });

        Schema::table('fincas', function (Blueprint $table) {
            $table->unsignedSmallInteger('provincia_cod')->nullable()->after('user_id')
                ->comment('Código INE de provincia (2 dígitos, ej: 45)');
            $table->unsignedSmallInteger('municipio_cod')->nullable()->after('provincia_cod')
                ->comment('Código INE de municipio dentro de la provincia (ej: 165)');
            $table->unsignedTinyInteger('agregado')->default(0)->after('municipio_cod')
                ->comment('Código de agregado/zona SIGPAC (0 si no hay concentración parcelaria)');
            $table->unsignedSmallInteger('recinto')->nullable()->after('parcela_sigpac')
                ->comment('Número de recinto SIGPAC dentro de la parcela (opcional)');
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['provincia_cod', 'municipio_cod', 'agregado', 'recinto']);
        });

        Schema::table('fincas', function (Blueprint $table) {
            $table->string('provincia', 100)->nullable()->after('user_id');
            $table->string('municipio', 255)->nullable()->after('provincia');
            $table->unsignedSmallInteger('municipio_ine')->nullable();
        });
    }
};
