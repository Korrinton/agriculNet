<?php

use App\Modules\Alertas\Http\Controllers\Web\AlertaWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('alertas', [AlertaWebController::class, 'index'])
        ->name('alertas.index');

    Route::post('alertas/leer-todas', [AlertaWebController::class, 'marcarTodasLeidas'])
        ->name('alertas.leer-todas');

    Route::patch('alertas/{alerta}/leer', [AlertaWebController::class, 'marcarLeida'])
        ->name('alertas.leer');

    Route::delete('alertas/{alerta}', [AlertaWebController::class, 'destroy'])
        ->name('alertas.destroy');
});
