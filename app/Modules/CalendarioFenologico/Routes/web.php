<?php

use App\Modules\CalendarioFenologico\Http\Controllers\Web\RegistroFenologicoWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Índice global — estado actual de todas las parcelas
    Route::get('fenologia', [RegistroFenologicoWebController::class, 'index'])
        ->name('fenologia.index');

    // Crear / guardar — anidado bajo parcela
    Route::get('vinedo/parcelas/{parcela}/fenologia/create', [RegistroFenologicoWebController::class, 'create'])
        ->name('fenologia.create');
    Route::post('vinedo/parcelas/{parcela}/fenologia', [RegistroFenologicoWebController::class, 'store'])
        ->name('fenologia.store');

    // Eliminar — shallow
    Route::delete('fenologia/{registro}', [RegistroFenologicoWebController::class, 'destroy'])
        ->name('fenologia.destroy');
});
