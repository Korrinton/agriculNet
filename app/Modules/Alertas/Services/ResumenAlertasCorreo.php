<?php

namespace App\Modules\Alertas\Services;

use App\Models\User;
use App\Modules\Alertas\Mail\ResumenAlertas;
use App\Modules\Alertas\Models\Alerta;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Un correo al día por usuario con sus alertas nuevas (no leídas y no enviadas antes), de los
 * niveles que ha elegido en su perfil. Se envía después de generar las alertas diarias.
 */
class ResumenAlertasCorreo
{
    /** Una alerta más antigua ya no se envía: el aviso de helada de hace una semana no sirve. */
    public const DIAS_MAXIMOS = 3;

    /** @return array{usuarios: int, alertas: int} */
    public function enviar(CarbonInterface $ahora): array
    {
        $usuarios = 0;
        $alertas = 0;

        User::whereNull('bloqueado_at')
            ->where('alertas_por_correo', '!=', 'ninguna')
            ->whereHas('alertas', fn ($q) => $this->pendientes($q, $ahora))
            ->each(function (User $user) use ($ahora, &$usuarios, &$alertas) {
                $pendientes = $this->pendientes(Alerta::where('user_id', $user->id), $ahora)
                    ->with('parcela.finca')
                    ->orderByRaw("case nivel when 'critical' then 0 when 'warning' then 1 else 2 end")
                    ->latest()
                    ->get();
                $aEnviar = $pendientes->whereIn('nivel', $user->nivelesAlertasPorCorreo());

                // Las de niveles que no quiere también cuentan como tratadas: no se le mandarán mañana
                DB::transaction(function () use ($user, $pendientes, $aEnviar, $ahora) {
                    Alerta::whereIn('id', $pendientes->pluck('id'))->update(['notificada_at' => $ahora]);
                    if ($aEnviar->isNotEmpty()) {
                        Mail::to($user)->queue(new ResumenAlertas($user, $this->porFinca($aEnviar)));
                    }
                });

                if ($aEnviar->isNotEmpty()) {
                    $usuarios++;
                    $alertas += $aEnviar->count();
                }
            });

        return ['usuarios' => $usuarios, 'alertas' => $alertas];
    }

    private function pendientes($consulta, CarbonInterface $ahora)
    {
        return $consulta->where('leida', false)
            ->whereNull('notificada_at')
            ->where('created_at', '>=', $ahora->copy()->subDays(self::DIAS_MAXIMOS));
    }

    /**
     * Alertas agrupadas por finca, como se leen en el correo.
     *
     * @return array<string, array<int, array{nivel: string, mensaje: string, fecha: string}>>
     */
    private function porFinca(Collection $alertas): array
    {
        return $alertas
            ->groupBy(fn (Alerta $a) => $a->parcela?->finca?->paraje ?: ($a->parcela?->finca?->provincia_nombre ?? 'Sin finca'))
            ->map(fn (Collection $deFinca) => $deFinca->map(fn (Alerta $a) => [
                'nivel'   => $a->nivel,
                'mensaje' => $a->mensaje,
                'fecha'   => $a->created_at->timezone('Europe/Madrid')->format('d/m'),
            ])->values()->all())
            ->all();
    }
}
