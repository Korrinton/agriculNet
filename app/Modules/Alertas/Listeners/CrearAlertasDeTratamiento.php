<?php

namespace App\Modules\Alertas\Listeners;

use App\Modules\Alertas\Services\AlertaService;
use App\Modules\Tratamientos\Events\TratamientoRegistrado;

class CrearAlertasDeTratamiento
{
    public function __construct(private readonly AlertaService $service) {}

    public function handle(TratamientoRegistrado $event): void
    {
        $this->service->alertasDeTratamiento($event->tratamiento);
    }
}
