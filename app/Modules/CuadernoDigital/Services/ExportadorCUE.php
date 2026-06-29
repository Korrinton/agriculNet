<?php

namespace App\Modules\CuadernoDigital\Services;

use App\Modules\Vinedo\Models\Finca;
use Carbon\Carbon;

class ExportadorCUE
{
    /**
     * Genera el contenido del Cuaderno de Explotación para exportar al MAPA.
     * Formato: XML según especificación CUE del Ministerio de Agricultura.
     */
    public function generar(Finca $finca, Carbon $desde, Carbon $hasta): string
    {
        // TODO: implementar exportación XML CUE
        // Referencia: https://www.mapa.gob.es/es/agricultura/temas/sanidad-vegetal/productos-fitosanitarios/cuaderno-explotacion/
        return '';
    }
}
