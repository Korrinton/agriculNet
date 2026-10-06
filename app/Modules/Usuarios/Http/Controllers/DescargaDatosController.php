<?php

namespace App\Modules\Usuarios\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Usuarios\Services\ExportadorDatosUsuario;
use Illuminate\Http\Request;

/** Descarga de todos los datos del usuario (derechos de acceso y portabilidad). */
class DescargaDatosController extends Controller
{
    public function __invoke(Request $request, ExportadorDatosUsuario $exportador)
    {
        $nombre = 'mis-datos-' . str($request->user()->name)->slug() . '-' . now('Europe/Madrid')->format('Y-m-d') . '.zip';

        return response()->download($exportador->generar($request->user()), $nombre, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
