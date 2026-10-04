<?php

namespace App\Modules\Meteorologia\Services;

use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Models\PrediccionMeteorologica;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ImportadorMeteorologico
{
    public function __construct(private readonly AemetClient $aemet) {}

    /**
     * Importa datos climatológicos diarios desde AEMET para un rango de fechas.
     * Devuelve el número de registros creados o actualizados.
     */
    public function importarDesdeAemet(EstacionMeteorologica $estacion, Carbon $desde, Carbon $hasta): int
    {
        $fechaIni = $desde->format('Y-m-d') . 'T00:00:00UTC';
        $fechaFin = $hasta->format('Y-m-d') . 'T23:59:59UTC';
        $idema    = $estacion->codigo_externo;

        $registros = $this->aemet->get(
            "/valores/climatologicos/diarios/datos/fechaini/{$fechaIni}/fechafin/{$fechaFin}/estacion/{$idema}/"
        );

        $importados = 0;

        foreach ($registros as $r) {
            if (empty($r['fecha'])) {
                continue;
            }

            DatoMeteorologico::updateOrCreate(
                ['estacion_id' => $estacion->id, 'fecha' => $r['fecha']],
                [
                    'temp_max'         => AemetClient::parseDecimal($r['tmax'] ?? null),
                    'temp_min'         => AemetClient::parseDecimal($r['tmin'] ?? null),
                    'precipitacion_mm' => AemetClient::parseDecimal($r['prec'] ?? null),
                    'humedad_pct'      => isset($r['hrMedia']) ? (int) AemetClient::parseDecimal($r['hrMedia']) : null,
                    'viento_kmh'       => AemetClient::parseDecimal($r['velmedia'] ?? null),
                ]
            );

            $importados++;
        }

        return $importados;
    }

    /**
     * Importa la predicción diaria AEMET (7 días) de un municipio.
     * Devuelve el número de días guardados.
     */
    public function importarPrediccion(int $provinciaCod, int $municipioCod): int
    {
        $codigoIne = sprintf('%02d%03d', $provinciaCod, $municipioCod);
        $respuesta = $this->aemet->get("/prediccion/especifica/municipio/diaria/{$codigoIne}");

        $prediccion = $respuesta[0] ?? null;
        if (!$prediccion || empty($prediccion['prediccion']['dia'])) {
            return 0;
        }

        // "elaborado" viene en hora local peninsular, sin zona. Eloquent guarda la
        // hora tal cual sin convertirla, así que se pasa a UTC (zona de la app).
        $elaborado = isset($prediccion['elaborado'])
            ? Carbon::parse($prediccion['elaborado'], 'Europe/Madrid')->utc()
            : null;

        $guardados = 0;

        foreach ($prediccion['prediccion']['dia'] as $dia) {
            if (empty($dia['fecha'])) {
                continue;
            }

            PrediccionMeteorologica::updateOrCreate(
                [
                    'provincia_cod' => $provinciaCod,
                    'municipio_cod' => $municipioCod,
                    'fecha'         => substr($dia['fecha'], 0, 10),
                ],
                [
                    'temp_max'           => $dia['temperatura']['maxima'] ?? null,
                    'temp_min'           => $dia['temperatura']['minima'] ?? null,
                    'prob_precipitacion' => $this->maximo($dia['probPrecipitacion'] ?? []),
                    'humedad_max'        => $dia['humedadRelativa']['maxima'] ?? null,
                    'humedad_min'        => $dia['humedadRelativa']['minima'] ?? null,
                    'estado_cielo'       => $this->estadoCielo($dia['estadoCielo'] ?? []),
                    'elaborado_at'       => $elaborado,
                ]
            );

            $guardados++;
        }

        return $guardados;
    }

    /**
     * La probabilidad viene por tramos (00-24, 00-12, 12-24...). En el día en curso
     * el tramo 00-24 suele venir a 0 aunque llueva por la tarde: se toma el máximo.
     */
    private function maximo(array $periodos): ?int
    {
        $valores = array_filter(array_column($periodos, 'value'), fn ($v) => $v !== '' && $v !== null);

        return $valores ? (int) max($valores) : null;
    }

    /** Descripción del día completo o, si no viene (día en curso), la del primer tramo con datos. */
    private function estadoCielo(array $periodos): ?string
    {
        $conDescripcion = array_values(array_filter($periodos, fn ($p) => !empty($p['descripcion'])));
        if (!$conDescripcion) {
            return null;
        }

        foreach ($conDescripcion as $p) {
            if (($p['periodo'] ?? null) === '00-24') {
                return $p['descripcion'];
            }
        }

        return $conDescripcion[0]['descripcion'];
    }

    public function importarManual(EstacionMeteorologica $estacion, array $filas): int
    {
        $importados = 0;
        foreach ($filas as $fila) {
            DatoMeteorologico::updateOrCreate(
                ['estacion_id' => $estacion->id, 'fecha' => $fila['fecha']],
                $fila
            );
            $importados++;
        }
        return $importados;
    }

    public function ultimosDias(EstacionMeteorologica $estacion, int $dias = 30): Collection
    {
        return $estacion->datos()
            ->whereDate('fecha', '>=', now()->subDays($dias))
            ->orderBy('fecha')
            ->get();
    }
}
