<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * indices_de_consultas quitó este índice creyéndolo duplicado del único, pero el único ya se
     * había quitado en catalogo_productos_fitosanitarios: era el único índice de la columna, que
     * el importador del registro usa para casar productos por número.
     */
    public function up(): void
    {
        Schema::table('productos_fitosanitarios', fn (Blueprint $table) => $table->index('numero_registro'));
    }

    public function down(): void
    {
        Schema::table('productos_fitosanitarios', fn (Blueprint $table) => $table->dropIndex(['numero_registro']));
    }
};
