<?php

namespace App\Modules\Meteorologia\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Models\PrediccionMeteorologica;
use App\Modules\Vinedo\Models\Finca;

class MeteorologiaWebController extends Controller
{
    public function index()
    {
        $fincas = Finca::where('user_id', auth()->id())
            ->with(['estacion.datos' => function ($q) {
                $q->whereDate('fecha', '>=', now()->subDays(30))->orderBy('fecha');
            }])
            ->orderBy('provincia_cod')
            ->get();

        $conMunicipio = $fincas->filter(fn ($f) => $f->provincia_cod && $f->municipio_cod);

        // Sin municipios, el where agrupado quedaría vacío y traería las de todos
        $predicciones = $conMunicipio->isEmpty() ? collect() : PrediccionMeteorologica::query()
            ->where('fecha', '>=', now('Europe/Madrid')->toDateString())
            ->where(function ($q) use ($conMunicipio) {
                foreach ($conMunicipio as $f) {
                    $q->orWhere(fn ($w) => $w->delMunicipio($f->provincia_cod, $f->municipio_cod));
                }
            })
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn ($p) => "{$p->provincia_cod}-{$p->municipio_cod}");

        return view('meteorologia.index', compact('fincas', 'predicciones'));
    }
}
