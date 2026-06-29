<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    private array $modules = [
        'Vinedo',
        'CalendarioFenologico',
        'Meteorologia',
        'Alertas',
        'Tratamientos',
        'Costes',
        'CuadernoDigital',
        'Usuarios',
    ];

    public function boot(): void
    {
        foreach ($this->modules as $module) {
            $this->loadRoutes($module);
        }
    }

    private function loadRoutes(string $module): void
    {
        $apiPath = app_path("Modules/{$module}/Routes/api.php");
        if (file_exists($apiPath)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiPath);
        }

        $webPath = app_path("Modules/{$module}/Routes/web.php");
        if (file_exists($webPath)) {
            Route::middleware('web')
                ->group($webPath);
        }
    }
}
