<?php

use App\Modules\Alertas\Http\Controllers\Web\AlertaWebController;
use App\Modules\Alertas\Http\Controllers\Web\BajaAlertasCorreoController;
use Illuminate\Support\Facades\Route;

// Baja de las alertas por correo desde el enlace firmado del correo (sin iniciar sesión).
// El POST está excluido del CSRF en bootstrap/app.php: lo envía el cliente de correo.
Route::middleware(['signed', 'throttle:10,1'])->group(function () {
    Route::get('alertas/correo/baja/{usuario}', [BajaAlertasCorreoController::class, 'confirmar'])
        ->name('alertas.correo.baja');
    Route::post('alertas/correo/baja/{usuario}', [BajaAlertasCorreoController::class, 'baja']);
});

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
