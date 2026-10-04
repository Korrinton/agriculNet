<?php

namespace App\Modules\CuadernoDigital\Services;

use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Variedad;
use Carbon\Carbon;

/**
 * Cuaderno de explotación de una finca en una campaña (año natural), con las secciones
 * de la Orden APA/204/2023 que la aplicación registra: datos generales y parcelas,
 * tratamientos fitosanitarios, fertilización, cosecha y riego.
 */
class CuadernoCampana
{
    /** La fertilización debe anotarse como máximo un mes después de aplicarla (RD 1051/2022). */
    public const PLAZO_FERTILIZACION_DIAS = 30;

    public function datos(Finca $finca, int $anio): array
    {
        $finca->loadMissing(['parcelas.variedad', 'parcelas.finca']);
        $parcelaIds = $finca->parcelas->pluck('id');
        $desde = Carbon::create($anio, 1, 1)->toDateString();
        $hasta = Carbon::create($anio, 12, 31)->toDateString();

        $tratamientos = Tratamiento::with(['producto', 'parcela.finca', 'parcela.variedad'])
            ->whereIn('parcela_id', $parcelaIds)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('id')
            ->get();

        $fertilizaciones = Fertilizacion::with(['parcela.finca', 'parcela.variedad'])
            ->whereIn('parcela_id', $parcelaIds)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('id')
            ->get();

        $cosechas = Cosecha::with(['parcela.finca', 'parcela.variedad'])
            ->whereIn('parcela_id', $parcelaIds)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('id')
            ->get();

        $riegos = Riego::with(['parcela.finca', 'parcela.variedad'])
            ->whereIn('parcela_id', $parcelaIds)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('id')
            ->get();

        return [
            'finca'           => $finca,
            'anio'            => $anio,
            'parcelas'        => $finca->parcelas,
            'tratamientos'    => $tratamientos,
            'fertilizaciones' => $fertilizaciones,
            'cosechas'        => $cosechas,
            'riegos'          => $riegos,
            'avisos'          => $this->avisos($finca, $tratamientos, $fertilizaciones),
        ];
    }

    /** Años con algún registro en la finca, más el actual, de más reciente a más antiguo. */
    public function campanas(Finca $finca, int $actual): array
    {
        $ids = $finca->parcelas()->pluck('id');
        $anios = collect([$actual]);

        foreach ([Tratamiento::class, Fertilizacion::class, Cosecha::class, Riego::class] as $modelo) {
            $anios = $anios->merge(
                $modelo::whereIn('parcela_id', $ids)->pluck('fecha')->map(fn ($f) => (int) substr((string) $f, 0, 4))
            );
        }

        return $anios->unique()->sortDesc()->values()->all();
    }

    public static function cultivoDe($parcela): string
    {
        $cultivo = $parcela->cultivo();

        return $cultivo ? Variedad::CULTIVOS[$cultivo]['nombre'] : ($parcela->uso ?? '—');
    }

    /** Lo que falta para que el cuaderno esté completo según la normativa. */
    private function avisos(Finca $finca, $tratamientos, $fertilizaciones): array
    {
        $avisos = [];

        if (!$finca->titular_nombre || !$finca->titular_nif) {
            $avisos[] = 'Faltan el nombre y el NIF del titular de la explotación (se indican al editar la finca).';
        }
        if (!$finca->rea_numero) {
            $avisos[] = 'Falta el número de inscripción en el Registro de Explotaciones Agrícolas (REA).';
        }

        $sinAplicador = $tratamientos->filter(fn ($t) => !$t->aplicador_ropo)->count();
        if ($sinAplicador > 0) {
            $avisos[] = "{$sinAplicador} " . ($sinAplicador === 1 ? 'tratamiento no indica' : 'tratamientos no indican')
                . ' el nº ROPO del aplicador, obligatorio en el registro de tratamientos.';
        }

        $sinProductoRegistrado = $tratamientos->filter(fn ($t) => !$t->producto?->numero_registro)->count();
        if ($sinProductoRegistrado > 0) {
            $avisos[] = "{$sinProductoRegistrado} " . ($sinProductoRegistrado === 1 ? 'tratamiento usa' : 'tratamientos usan')
                . ' un producto sin nº de registro fitosanitario.';
        }

        $tarde = $fertilizaciones->filter(
            fn ($f) => $f->created_at && $f->fecha->diffInDays($f->created_at) > self::PLAZO_FERTILIZACION_DIAS
        )->count();
        if ($tarde > 0) {
            $avisos[] = "{$tarde} " . ($tarde === 1 ? 'fertilización se anotó' : 'fertilizaciones se anotaron')
                . ' más de un mes después de aplicarse (plazo legal: un mes).';
        }

        return $avisos;
    }
}
