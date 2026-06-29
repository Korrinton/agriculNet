<?php

use App\Modules\Alertas\Http\Controllers\AlertaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('alertas', [AlertaController::class, 'index']);
    Route::get('alertas/{alerta}', [AlertaController::class, 'show']);
    Route::patch('alertas/{alerta}/leer', [AlertaController::class, 'marcarLeida']);
    Route::post('alertas/leer-todas', [AlertaController::class, 'marcarTodasLeidas']);
});
