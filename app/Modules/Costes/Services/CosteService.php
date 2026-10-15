<?php

namespace App\Modules\Costes\Services;

use App\Modules\Costes\Models\Coste;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Support\Collection;

class CosteService
{
    public function costesTotalesPorCategoria(Parcela $parcela, int $año): Collection
    {
        return Coste::where('parcela_id', $parcela->id)
            ->whereBetween('fecha', ["{$año}-01-01", "{$año}-12-31"])
            ->join('categoria_costes', 'categoria_costes.id', '=', 'costes.categoria_id')
            ->groupBy('categoria_costes.nombre')
            ->selectRaw('categoria_costes.nombre, SUM(costes.importe) AS total')
            ->pluck('total', 'nombre')
            ->map(fn ($total) => (float) $total);
    }

    public function costesPorHectarea(Parcela $parcela, int $año): float
    {
        $total = Coste::where('parcela_id', $parcela->id)
            ->whereBetween('fecha', ["{$año}-01-01", "{$año}-12-31"])
            ->sum('importe');

        return $parcela->superficie_ha > 0
            ? round($total / $parcela->superficie_ha, 2)
            : 0.0;
    }
}
