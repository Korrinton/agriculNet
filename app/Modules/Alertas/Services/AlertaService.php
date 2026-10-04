<?php

namespace App\Modules\Alertas\Services;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertaService
{
    public function alertasDeUsuario(User $user, bool $soloNoLeidas = false, ?string $nivel = null): LengthAwarePaginator
    {
        return Alerta::where('user_id', $user->id)
            ->when($soloNoLeidas, fn ($q) => $q->where('leida', false))
            ->when($nivel, fn ($q) => $q->where('nivel', $nivel))
            ->with('parcela.finca')
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function contarNoLeidas(User $user): int
    {
        return Alerta::where('user_id', $user->id)->where('leida', false)->count();
    }

    /**
     * Con $clave la creación es idempotente: si el usuario ya tiene una alerta
     * con esa clave se devuelve la existente (aunque esté leída) sin duplicarla.
     */
    public function crear(Parcela $parcela, User|int $user, string $tipo, string $nivel, string $mensaje, ?string $clave = null): Alerta
    {
        $userId = $user instanceof User ? $user->id : $user;

        $datos = [
            'parcela_id' => $parcela->id,
            'tipo'       => $tipo,
            'nivel'      => $nivel,
            'mensaje'    => $mensaje,
            'leida'      => false,
        ];

        if ($clave === null) {
            return Alerta::create($datos + ['user_id' => $userId]);
        }

        return Alerta::firstOrCreate(['user_id' => $userId, 'clave' => $clave], $datos);
    }

    public function marcarTodasLeidas(User $user): int
    {
        return Alerta::where('user_id', $user->id)
            ->where('leida', false)
            ->update(['leida' => true]);
    }

    /**
     * Genera las alertas derivadas de un tratamiento recién registrado:
     * dosis por encima del máximo autorizado y plazo de seguridad en curso.
     */
    public function alertasDeTratamiento(Tratamiento $tratamiento): void
    {
        $tratamiento->loadMissing(['producto', 'parcela', 'user']);

        $producto = $tratamiento->producto;
        $parcela  = $tratamiento->parcela;
        $user     = $tratamiento->user;

        if (!$producto || !$parcela || !$user) {
            return;
        }

        if ($producto->dosis_max_l_ha && (float) $tratamiento->dosis_l_ha > (float) $producto->dosis_max_l_ha) {
            $this->crear($parcela, $user, 'dosis_excedida', 'critical', sprintf(
                'Dosis de %s (%s l/ha) superior al máximo autorizado (%s l/ha) en %s.',
                $producto->nombre,
                number_format((float) $tratamiento->dosis_l_ha, 2, ',', '.'),
                number_format((float) $producto->dosis_max_l_ha, 2, ',', '.'),
                $parcela->nombre,
            ), "dosis_excedida:{$tratamiento->id}");
        }

        $fin = $tratamiento->fechaFinalPlazoSeguridad();

        if ($fin && $fin->endOfDay()->isFuture()) {
            $this->crear($parcela, $user, 'plazo_seguridad', 'warning', sprintf(
                'Plazo de seguridad de %s en %s: no vendimiar hasta el %s.',
                $producto->nombre,
                $parcela->nombre,
                $fin->format('d/m/Y'),
            ), "plazo_seguridad:{$tratamiento->id}");
        }
    }
}
