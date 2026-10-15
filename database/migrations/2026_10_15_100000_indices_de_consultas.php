<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PostgreSQL no crea índices para las claves foráneas: se añaden los de las columnas
     * que se consultan, se quitan dos duplicados y se amplía el del listado de alertas.
     */
    public function up(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->index('producto_id');
            $table->index('user_id');
        });

        Schema::table('costes', function (Blueprint $table) {
            $table->index('tratamiento_id');
            $table->index('categoria_id');
            $table->index('user_id');
        });

        Schema::table('fincas', function (Blueprint $table) {
            $table->index('estacion_meteorologica_id');
        });

        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->index('user_id');
            // Duplicado: numero_registro ya tiene índice único
            $table->dropIndex(['numero_registro']);
        });

        Schema::table('registro_fenologico', function (Blueprint $table) {
            $table->index('estado_fenologico_id');
            $table->index('user_id');
        });

        Schema::table('fertilizaciones', fn (Blueprint $table) => $table->index('user_id'));
        Schema::table('cosechas', fn (Blueprint $table) => $table->index('user_id'));
        Schema::table('riegos', fn (Blueprint $table) => $table->index('user_id'));

        // El único (user_id, producto_id) no sirve cuando se busca solo por producto
        Schema::table('precios_productos_fitosanitarios', fn (Blueprint $table) => $table->index('producto_id'));

        Schema::table('alertas', function (Blueprint $table) {
            // El listado filtra por leída y ordena por fecha: el índice cubre la consulta entera
            $table->dropIndex(['user_id', 'leida']);
            $table->index(['user_id', 'leida', 'created_at']);
        });

        Schema::table('datos_meteorologicos', function (Blueprint $table) {
            // Duplicado: existe el índice único sobre las mismas columnas
            $table->dropIndex(['estacion_id', 'fecha']);
        });

        // whereJsonContains('cultivos', …) usa el operador @>, que indexa un GIN
        DB::statement('CREATE INDEX productos_fitosanitarios_cultivos_gin ON productos_fitosanitarios USING GIN (cultivos jsonb_path_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS productos_fitosanitarios_cultivos_gin');

        Schema::table('datos_meteorologicos', fn (Blueprint $table) => $table->index(['estacion_id', 'fecha']));

        Schema::table('alertas', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'leida', 'created_at']);
            $table->index(['user_id', 'leida']);
        });

        Schema::table('precios_productos_fitosanitarios', fn (Blueprint $table) => $table->dropIndex(['producto_id']));
        Schema::table('riegos', fn (Blueprint $table) => $table->dropIndex(['user_id']));
        Schema::table('cosechas', fn (Blueprint $table) => $table->dropIndex(['user_id']));
        Schema::table('fertilizaciones', fn (Blueprint $table) => $table->dropIndex(['user_id']));

        Schema::table('registro_fenologico', function (Blueprint $table) {
            $table->dropIndex(['estado_fenologico_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->index('numero_registro');
            $table->dropIndex(['user_id']);
        });

        Schema::table('fincas', fn (Blueprint $table) => $table->dropIndex(['estacion_meteorologica_id']));

        Schema::table('costes', function (Blueprint $table) {
            $table->dropIndex(['tratamiento_id']);
            $table->dropIndex(['categoria_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['user_id']);
        });
    }
};
