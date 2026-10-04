<?php

namespace App\Modules\Meteorologia\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Jobs\ImportarDatosMeteorologicos;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Vinedo\Models\Finca;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DatoMeteorologicoWebController extends Controller
{
    /** Vincula una estación AEMET existente a la finca. */
    public function vincularEstacion(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        // Solo estaciones AEMET: las manuales pertenecen a la finca que las creó
        $validated = $request->validate([
            'estacion_id' => 'required|exists:estaciones_meteorologicas,id,fuente,aemet',
        ]);

        $finca->update(['estacion_meteorologica_id' => $validated['estacion_id']]);

        return back()->with('success', 'Estación meteorológica vinculada correctamente.');
    }

    /** Desvincular la estación de la finca. */
    public function desvincularEstacion(Finca $finca)
    {
        $this->authorize('update', $finca);
        $finca->update(['estacion_meteorologica_id' => null]);

        return back()->with('success', 'Estación desvinculada.');
    }

    /** Lanza el job de importación AEMET para los últimos 30 días. */
    public function importarAemet(Finca $finca)
    {
        $this->authorize('update', $finca);

        if (! $finca->estacion_meteorologica_id) {
            return back()->with('error', 'La finca no tiene una estación vinculada.');
        }

        if (! config('aemet.api_key')) {
            return back()->with('error', 'AEMET_API_KEY no está configurada en el servidor.');
        }

        ImportarDatosMeteorologicos::dispatch(
            $finca->estacion,
            Carbon::now()->subDays(30),
            Carbon::now(),
        );

        return back()->with('success', 'Importación AEMET en curso. Los datos aparecerán en breve.');
    }

    /** Entrada manual de un dato diario (fallback sin AEMET). */
    public function store(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate([
            'fecha'            => 'required|date|before_or_equal:today',
            'temp_max'         => 'required|numeric|between:-50,60',
            'temp_min'         => 'required|numeric|between:-50,60|lte:temp_max',
            'precipitacion_mm' => 'nullable|numeric|min:0|max:999',
            'humedad_pct'      => 'nullable|integer|min:0|max:100',
            'viento_kmh'       => 'nullable|numeric|min:0|max:500',
        ]);

        // Los datos de una estación AEMET los comparten todas las fincas vinculadas
        // a ella: un usuario no puede sobrescribirlos con datos propios.
        if ($finca->estacion?->fuente === 'aemet') {
            return back()->with('error', 'La finca usa datos oficiales de AEMET. Desvincula la estación para registrar datos manuales.');
        }

        if (! $finca->estacion_meteorologica_id) {
            $estacion = EstacionMeteorologica::create([
                'nombre'   => 'Manual – ' . ($finca->paraje ?: $finca->provincia_nombre),
                'latitud'  => 0,
                'longitud' => 0,
                'fuente'   => 'manual',
            ]);
            $finca->update(['estacion_meteorologica_id' => $estacion->id]);
        }

        DatoMeteorologico::updateOrCreate(
            ['estacion_id' => $finca->estacion_meteorologica_id, 'fecha' => $validated['fecha']],
            array_merge($validated, ['estacion_id' => $finca->estacion_meteorologica_id]),
        );

        return back()->with('success', 'Dato meteorológico guardado.');
    }

    public function destroy(Finca $finca, DatoMeteorologico $dato)
    {
        $this->authorize('update', $finca);
        abort_unless($dato->estacion_id === $finca->estacion_meteorologica_id, 403);
        abort_if($finca->estacion?->fuente === 'aemet', 403, 'Los datos de AEMET son compartidos y no se pueden borrar.');
        $dato->delete();

        return back()->with('success', 'Dato eliminado.');
    }
}
