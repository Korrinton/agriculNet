<?php

use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los líquidos se dosifican en l/ha y se pagan en €/l; los sólidos, en kg/ha y €/kg
        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->string('unidad', 2)->default('l')->after('dosis_max_l_ha');
        });

        // La del producto en el momento del tratamiento (dosis_l_ha va en esta unidad por hectárea)
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->string('unidad', 2)->default('l')->after('dosis_l_ha');
        });

        DB::table('productos_fitosanitarios')->whereNotNull('mapa_id')->orderBy('id')
            ->each(function ($p) {
                $unidad = ImportadorFitosanitarios::unidadDeFormulado($p->ingrediente_activo);
                DB::table('productos_fitosanitarios')->where('id', $p->id)->update(['unidad' => $unidad]);
                DB::table('tratamientos')->where('producto_id', $p->id)->update(['unidad' => $unidad]);
            });
    }

    public function down(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn('unidad');
        });

        Schema::table('productos_fitosanitarios', function (Blueprint $table) {
            $table->dropColumn('unidad');
        });
    }
};
