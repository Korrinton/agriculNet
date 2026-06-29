<?php

namespace App\Modules\CalendarioFenologico\Services;

use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;

class GradosDiaCalculator
{
    private const TEMPERATURA_BASE_DEFAULT = 10.0;

    public function calcularAcumuladoDesde(Parcela $parcela, Carbon $desde, float $base = self::TEMPERATURA_BASE_DEFAULT): float
    {
        return DatoMeteorologico::whereHas('estacion', fn ($q) => $q->whereHas('fincas', fn ($q2) => $q2->where('id', $parcela->finca_id)))
            ->whereDate('fecha', '>=', $desde)
            ->get()
            ->sum(fn ($dato) => max(0, (($dato->temp_max + $dato->temp_min) / 2) - $base));
    }

    public function calcularDiario(float $tempMax, float $tempMin, float $base = self::TEMPERATURA_BASE_DEFAULT): float
    {
        return max(0, (($tempMax + $tempMin) / 2) - $base);
    }
}
