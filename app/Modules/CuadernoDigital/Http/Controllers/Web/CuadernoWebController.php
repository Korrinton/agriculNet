<?php

namespace App\Modules\CuadernoDigital\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\CuadernoDigital\Http\Controllers\CuadernoDigitalController;
use App\Modules\CuadernoDigital\Services\CuadernoCampana;
use App\Modules\CuadernoDigital\Services\ExportadorCUE;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CuadernoWebController extends Controller
{
    public function __construct(private readonly CuadernoCampana $cuaderno) {}

    public function index(Request $request)
    {
        $fincas = Finca::where('user_id', auth()->id())->orderBy('provincia_cod')->orderBy('municipio_cod')->get();

        if ($fincas->isEmpty()) {
            return view('cuaderno.index', ['fincas' => $fincas, 'datos' => null]);
        }

        $finca = $fincas->firstWhere('id', (int) $request->query('finca')) ?? $fincas->first();
        $actual = now('Europe/Madrid')->year;
        $campanas = $this->cuaderno->campanas($finca, $actual);
        $anio = (int) $request->query('anio', $actual);
        $anio = in_array($anio, $campanas, true) ? $anio : $actual;

        return view('cuaderno.index', [
            'fincas'   => $fincas,
            'campanas' => $campanas,
            'datos'    => $this->cuaderno->datos($finca, $anio),
        ]);
    }

    public function imprimir(Finca $finca, int $anio)
    {
        $this->authorize('view', $finca);

        return view('cuaderno.imprimir', ['datos' => $this->cuaderno->datos($finca, $anio)]);
    }

    public function excel(Finca $finca, int $anio, ExportadorCUE $exportador): Response
    {
        $this->authorize('view', $finca);

        return response($exportador->generar($this->cuaderno->datos($finca, $anio)), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . CuadernoDigitalController::nombreFichero($finca, $anio) . '"',
        ]);
    }
}
