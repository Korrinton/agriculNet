<?php

namespace App\Providers;

use App\Modules\Admin\Services\Tareas;
use App\Modules\Alertas\Listeners\CrearAlertasDeTratamiento;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Alertas\Policies\AlertaPolicy;
use App\Modules\Tratamientos\Events\TratamientoRegistrado;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Policies\ProductoFitosanitarioPolicy;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Policies\FincaPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Finca::class => FincaPolicy::class,
        Alerta::class => AlertaPolicy::class,
        ProductoFitosanitario::class => ProductoFitosanitarioPolicy::class,
    ];

    public function boot(): void
    {
        // En desarrollo y tests, una relación cargada perezosamente (N+1) lanza excepción
        Model::preventLazyLoading(! $this->app->isProduction());

        // Límite de toda la API (middleware throttle:api): por usuario o, sin sesión, por IP
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        $this->registerPolicies();

        Event::listen(TratamientoRegistrado::class, CrearAlertasDeTratamiento::class);

        // Historial de las tareas programadas para el backoffice
        Tareas::escucharEjecuciones();
    }
}
