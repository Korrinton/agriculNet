<?php

namespace App\Modules\CuadernoDigital\Services;

use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
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

        // Datos obligatorios de cada tratamiento (Reglamento (UE) 2023/564 y Orden APA/204/2023)
        $faltan = [
            'el nº ROPO del aplicador'     => fn ($t) => !$t->aplicador_ropo,
            'el NIF del aplicador'         => fn ($t) => !$t->aplicador_nif,
            'el estadio BBCH del cultivo'  => fn ($t) => !$t->bbch,
            'el cultivo con código EPPO (en secano, indica el cultivo como variedad de la parcela)' => fn ($t) => !$t->codigoEppo(),
            'la justificación del tratamiento' => fn ($t) => !$t->justificacion,
            'el nº ROMA o REGANIP del equipo'  => fn ($t) => !$t->equipo_roma,
            'el asesor que lo valida (obligatorio salvo explotaciones exentas de asesoramiento)' => fn ($t) => !$t->asesor_ropo,
        ];
        foreach ($faltan as $dato => $falta) {
            $n = $tratamientos->filter($falta)->count();
            if ($n > 0) {
                $avisos[] = "{$n} " . ($n === 1 ? 'tratamiento no indica' : 'tratamientos no indican') . " {$dato}.";
            }
        }

        $inspeccion = $tratamientos->filter(fn ($t) => $t->equipo_roma && $t->inspeccionEquipoCaducada())->count();
        if ($inspeccion > 0) {
            $avisos[] = "{$inspeccion} " . ($inspeccion === 1 ? 'tratamiento se hizo' : 'tratamientos se hicieron')
                . ' con un equipo sin inspección ITEAF en vigor (o sin anotar su fecha).';
        }

        $sinProductoRegistrado = $tratamientos->filter(fn ($t) => !$t->producto?->numero_registro)->count();
        if ($sinProductoRegistrado > 0) {
            $avisos[] = "{$sinProductoRegistrado} " . ($sinProductoRegistrado === 1 ? 'tratamiento usa' : 'tratamientos usan')
                . ' un producto sin nº de registro fitosanitario.';
        }

        $noAutorizados = $tratamientos->filter(
            fn ($t) => $t->producto?->autorizadoPara(ImportadorFitosanitarios::cultivoRegistroDe($t->parcela)) === false
        )->count();
        if ($noAutorizados > 0) {
            $avisos[] = "{$noAutorizados} " . ($noAutorizados === 1 ? 'tratamiento usa' : 'tratamientos usan')
                . ' un producto que el Registro de Productos Fitosanitarios no autoriza para el cultivo de la parcela.';
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
