<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Admin\Services\Tareas;
use App\Modules\Alertas\Listeners\CrearAlertasDeTratamiento;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Alertas\Policies\AlertaPolicy;
use App\Modules\Tratamientos\Events\TratamientoRegistrado;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Policies\ProductoFitosanitarioPolicy;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Policies\FincaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Finca::class  => FincaPolicy::class,
        Alerta::class => AlertaPolicy::class,
        ProductoFitosanitario::class => ProductoFitosanitarioPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Event::listen(TratamientoRegistrado::class, CrearAlertasDeTratamiento::class);

        // Historial de las tareas programadas para el backoffice
        Tareas::escucharEjecuciones();
    }
}
