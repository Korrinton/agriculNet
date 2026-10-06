<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\MetricasUso;
use App\Modules\Admin\Services\Tareas;

class PanelAdminController extends Controller
{
    public function __invoke(MetricasUso $metricas, Tareas $tareas)
    {
        return view('admin.panel', [
            'm'      => $metricas->calcular(now('Europe/Madrid')),
            // Las tareas cuya última ejecución falló o se interrumpió, para verlas nada más entrar
            'tareasConProblemas' => $tareas->resumen(1)->filter(
                fn ($t) => in_array($t['ultima']?->estado(), ['error', 'interrumpida'], true)
            ),
        ]);
    }
}
