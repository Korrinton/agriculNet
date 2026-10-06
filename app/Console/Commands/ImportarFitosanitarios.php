<?php

namespace App\Console\Commands;

use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportarFitosanitarios extends Command
{
    protected $signature   = 'fitosanitarios:importar';
    protected $description = 'Sincroniza el catálogo de productos con el Registro Oficial de Productos Fitosanitarios del MAPA';

    public function handle(ImportadorFitosanitarios $importador): int
    {
        $this->info('Descargando el Registro de Productos Fitosanitarios del MAPA…');

        try {
            $resultado = $importador->importar();
        } catch (Throwable $e) {
            Log::error('Importación de fitosanitarios fallida', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['Vigentes recibidos', 'Nuevos', 'Actualizados', 'Dados de baja'],
            [[$resultado['recibidos'], $resultado['nuevos'], $resultado['actualizados'], $resultado['cancelados']]]
        );

        return self::SUCCESS;
    }
}
