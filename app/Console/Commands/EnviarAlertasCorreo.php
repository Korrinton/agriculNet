<?php

namespace App\Console\Commands;

use App\Modules\Alertas\Services\ResumenAlertasCorreo;
use Illuminate\Console\Command;

class EnviarAlertasCorreo extends Command
{
    protected $signature   = 'alertas:enviar';
    protected $description = 'Envía a cada usuario un correo con sus alertas nuevas (según su preferencia en el perfil)';

    public function handle(ResumenAlertasCorreo $resumen): int
    {
        $resultado = $resumen->enviar(now());

        $this->table(['Correos', 'Alertas enviadas'], [[$resultado['usuarios'], $resultado['alertas']]]);

        return self::SUCCESS;
    }
}
