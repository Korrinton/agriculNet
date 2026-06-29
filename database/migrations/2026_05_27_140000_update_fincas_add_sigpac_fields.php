<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->char('municipio_ine', 3)->nullable()->after('municipio')
                ->comment('Código INE del municipio (3 dígitos dentro de la provincia)');
            $table->unsignedSmallInteger('parcela_sigpac')->nullable()->after('poligono')
                ->comment('Número de parcela SIGPAC dentro del polígono');
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['municipio_ine', 'parcela_sigpac']);
        });
    }
};
