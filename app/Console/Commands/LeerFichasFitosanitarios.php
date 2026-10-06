<?php

namespace App\Console\Commands;

use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use App\Modules\Tratamientos\Services\LectorFichasFitosanitarios;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lee poco a poco las fichas de los productos autorizados en los cultivos que tienen las parcelas
 * de la aplicación: primero los ya usados en tratamientos, después los nunca leídos y luego los
 * leídos hace más de dos meses. Con un límite por ejecución para no cargar el servidor del MAPA.
 */
class LeerFichasFitosanitarios extends Command
{
    protected $signature   = 'fitosanitarios:fichas {--limite=150 : Fichas a leer como máximo} {--producto= : Leer solo este producto (id)}';
    protected $description = 'Lee los plazos de seguridad oficiales de las fichas del Registro de Productos Fitosanitarios';

    private const RELEER_TRAS_DIAS = 60;

    public function handle(LectorFichasFitosanitarios $lector): int
    {
        $productos = $this->option('producto')
            ? ProductoFitosanitario::whereKey($this->option('producto'))->whereNotNull('mapa_id')->get()
            : $this->pendientes((int) $this->option('limite'));

        if ($productos->isEmpty()) {
            $this->info('No hay fichas pendientes de leer.');
            return self::SUCCESS;
        }

        $leidas = $errores = 0;
        $bar = $this->output->createProgressBar($productos->count());
        foreach ($productos as $producto) {
            try {
                $lector->leer($producto);
                $leidas++;
            } catch (Throwable $e) {
                $errores++;
                Log::warning('Ficha de fitosanitario no leída', ['producto' => $producto->id, 'error' => $e->getMessage()]);
            }
            $bar->advance();
            if (!app()->runningUnitTests()) {
                usleep(500_000);
            }
        }
        $bar->finish();
        $this->newLine();
        $this->table(['Fichas leídas', 'Errores'], [[$leidas, $errores]]);

        return $errores && !$leidas ? self::FAILURE : self::SUCCESS;
    }

    private function pendientes(int $limite)
    {
        $cultivos = Parcela::with('variedad')->get()
            ->map(fn ($p) => ImportadorFitosanitarios::cultivoRegistroDe($p))
            ->filter()->unique()->values();

        if ($cultivos->isEmpty()) {
            return collect();
        }

        return ProductoFitosanitario::whereNotNull('mapa_id')
            ->where('vigente', true)
            ->where(function ($q) use ($cultivos) {
                foreach ($cultivos as $cultivo) {
                    $q->orWhereJsonContains('cultivos', $cultivo);
                }
            })
            ->where(fn ($q) => $q->whereNull('ficha_leida_at')->orWhere('ficha_leida_at', '<', now()->subDays(self::RELEER_TRAS_DIAS)))
            ->orderByRaw('exists (select 1 from tratamientos t where t.producto_id = productos_fitosanitarios.id) desc')
            ->orderByRaw('ficha_leida_at is not null, ficha_leida_at')
            ->limit($limite)
            ->get();
    }
}
