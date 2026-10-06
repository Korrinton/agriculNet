<?php

namespace App\Modules\Usuarios\Services;

use App\Models\User;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Support\Facades\DB;

/**
 * Derecho de supresión (art. 17 RGPD): borra la cuenta y todos sus datos.
 *
 * El orden importa: primero las fincas, que arrastran en cascada parcelas y todos sus registros
 * (tratamientos, costes, riegos, cosechas, fenología…); si se borrara antes el usuario, sus
 * registros (user_id RESTRICT) y sus productos propios (usados por sus tratamientos) lo impedirían.
 */
class BorradoCuenta
{
    public function borrar(User $user): void
    {
        DB::transaction(function () use ($user) {
            $fincas = Finca::where('user_id', $user->id);

            // Las estaciones manuales son de la finca que las creó; las de AEMET se comparten y se quedan
            $estacionesManuales = EstacionMeteorologica::where('fuente', 'manual')
                ->whereIn('id', (clone $fincas)->whereNotNull('estacion_meteorologica_id')->select('estacion_meteorologica_id'))
                ->pluck('id');

            $fincas->delete();

            EstacionMeteorologica::whereIn('id', $estacionesManuales)->whereDoesntHave('fincas')->delete();

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Productos propios, precios y alertas se borran en cascada con el usuario
            $user->delete();
        });
    }
}
