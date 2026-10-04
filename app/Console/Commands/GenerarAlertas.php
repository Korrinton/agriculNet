<?php

namespace App\Console\Commands;

use App\Modules\Alertas\Services\GeneradorAlertas;
use Illuminate\Console\Command;

class GenerarAlertas extends Command
{
    protected $signature   = 'alertas:generar {--dias=10 : Días hacia atrás de datos meteorológicos a revisar}';
    protected $description = 'Genera las alertas diarias: fin de plazos de seguridad, heladas (observadas y previstas) y riesgo de mildiu';

    public function handle(GeneradorAlertas $generador): int
    {
        // Los datos de AEMET llegan con varios días de retraso: se revisa una ventana
        // y las claves únicas evitan repetir alertas ya creadas en días anteriores.
        $resultado = $generador->generar(now('Europe/Madrid'), (int) $this->option('dias'));

        $this->table(
            ['Fin de plazo', 'Heladas', 'Mildiu', 'Heladas previstas'],
            [[$resultado['fin_plazo'], $resultado['helada'], $resultado['mildiu'], $resultado['prevision_helada']]]
        );

        return self::SUCCESS;
    }
}
