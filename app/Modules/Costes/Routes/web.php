<?php

use App\Modules\Costes\Http\Controllers\Web\CosteWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Índice global — todos los costes del usuario con resumen anual
    Route::get('costes', [CosteWebController::class, 'index'])
        ->name('costes.index');

    // Crear / guardar — anidado bajo parcela
    Route::get('vinedo/parcelas/{parcela}/costes/create', [CosteWebController::class, 'create'])
        ->name('costes.create');
    Route::post('vinedo/parcelas/{parcela}/costes', [CosteWebController::class, 'store'])
        ->name('costes.store');

    // Eliminar — shallow
    Route::delete('costes/{coste}', [CosteWebController::class, 'destroy'])
        ->name('costes.destroy');
});
