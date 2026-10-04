<?php

namespace App\Modules\CuadernoDigital\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CuadernoDigital\Services\CuadernoCampana;
use App\Modules\CuadernoDigital\Services\ExportadorCUE;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CuadernoDigitalController extends Controller
{
    public function __construct(private readonly CuadernoCampana $cuaderno) {}

    /** Cuaderno de una finca en una campaña: parcelas, tratamientos, fertilización, cosecha y riego. */
    public function show(Finca $finca, int $anio): JsonResponse
    {
        $this->authorize('view', $finca);

        $datos = $this->cuaderno->datos($finca, $anio);

        return response()->json([
            'anio'            => $anio,
            'finca'           => $finca->only(['id', 'provincia_cod', 'municipio_cod', 'paraje', 'titular_nombre', 'titular_nif', 'rea_numero']),
            'parcelas'        => $datos['parcelas']->map->only(['id', 'nombre', 'uso', 'poligono', 'parcela_sigpac', 'recinto', 'superficie_ha', 'variedad_id'])->values(),
            'tratamientos'    => $datos['tratamientos']->map(fn ($t) => $t->only([
                'id', 'parcela_id', 'fecha', 'dosis_l_ha', 'superficie_tratada_ha', 'motivo',
                'aplicador_nombre', 'aplicador_ropo', 'equipo_roma', 'eficacia',
            ]) + ['producto' => $t->producto?->only(['id', 'nombre', 'numero_registro'])])->values(),
            'fertilizaciones' => $datos['fertilizaciones']->map->makeHidden(['parcela', 'user'])->values(),
            'cosechas'        => $datos['cosechas']->map->makeHidden(['parcela', 'user'])->values(),
            'riegos'          => $datos['riegos']->map->makeHidden(['parcela', 'user'])->values(),
            'avisos'          => $datos['avisos'],
        ]);
    }

    public function excel(Finca $finca, int $anio, ExportadorCUE $exportador): Response
    {
        $this->authorize('view', $finca);

        return response($exportador->generar($this->cuaderno->datos($finca, $anio)), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . self::nombreFichero($finca, $anio) . '"',
        ]);
    }

    public static function nombreFichero(Finca $finca, int $anio): string
    {
        $nombre = \Illuminate\Support\Str::slug($finca->paraje ?: $finca->provincia_nombre);

        return "cuaderno-explotacion-{$nombre}-{$anio}.xlsx";
    }
}
