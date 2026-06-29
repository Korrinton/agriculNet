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
            ->whereYear('fecha', $año)
            ->with('categoria')
            ->get()
            ->groupBy('categoria.nombre')
            ->map(fn ($grupo) => $grupo->sum('importe'));
    }

    public function costesPorHectarea(Parcela $parcela, int $año): float
    {
        $total = Coste::where('parcela_id', $parcela->id)
            ->whereYear('fecha', $año)
            ->sum('importe');

        return $parcela->superficie_ha > 0
            ? round($total / $parcela->superficie_ha, 2)
            : 0.0;
    }
}
