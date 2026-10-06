<?php

namespace App\Modules\Alertas\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Dejar de recibir las alertas por correo desde el enlace del propio correo, sin iniciar sesión.
 * El enlace va firmado (middleware signed): solo sirve para el usuario al que se envió.
 *
 * GET muestra un botón en vez de dar de baja directamente: los antivirus y algunos clientes de
 * correo abren los enlaces para analizarlos y darían de baja al usuario sin que lo pidiera.
 * POST es la baja en sí, también la de «un clic» de Gmail u Outlook (List-Unsubscribe-Post).
 */
class BajaAlertasCorreoController extends Controller
{
    public function confirmar(Request $request, User $usuario)
    {
        return view('alertas.baja-correo', [
            'usuario' => $usuario,
            'hecho'   => $usuario->alertas_por_correo === 'ninguna',
            'accion'  => $request->fullUrl(),
        ]);
    }

    public function baja(Request $request, User $usuario)
    {
        $usuario->forceFill(['alertas_por_correo' => 'ninguna'])->save();

        return view('alertas.baja-correo', ['usuario' => $usuario, 'hecho' => true, 'accion' => null]);
    }
}
