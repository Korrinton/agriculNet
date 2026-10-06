<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Echa a los usuarios bloqueados desde el backoffice (también a los que ya tenían sesión o
 * entran con «mantener la sesión») y anota el último acceso, con el que se cuentan los usuarios activos.
 */
class ComprobarCuentaActiva
{
    /** No se escribe en la base de datos en cada petición: basta con saber el acceso con este margen. */
    private const MINUTOS_ENTRE_ANOTACIONES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        if ($user->estaBloqueado()) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'Cuenta bloqueada.'], 403)
                : redirect()->route('login')->with('status', 'Tu cuenta está bloqueada. Ponte en contacto con el administrador.');
        }

        if (! $user->ultimo_acceso_at || $user->ultimo_acceso_at->lt(now()->subMinutes(self::MINUTOS_ENTRE_ANOTACIONES))) {
            // Sin pasar por el modelo para no tocar updated_at
            DB::table('users')->where('id', $user->id)->update(['ultimo_acceso_at' => now()]);
            $user->ultimo_acceso_at = now();
        }

        return $next($request);
    }
}
