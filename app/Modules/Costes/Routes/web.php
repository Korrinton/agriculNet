<?php

use App\Modules\Costes\Http\Controllers\Web\CosteWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Índice global — todos los costes del usuario con resumen anual
    Route::get('costes', [CosteWebController::class, 'index'])
        ->name('costes.index');

    // Gasto de la finca: general (sin parcela) o imputado a una de sus parcelas
    Route::get('vinedo/fincas/{finca}/costes/create', [CosteWebController::class, 'createFinca'])
        ->name('costes.finca.create');
    Route::post('vinedo/fincas/{finca}/costes', [CosteWebController::class, 'storeFinca'])
        ->name('costes.finca.store');

    // Crear / guardar — anidado bajo parcela
    Route::get('vinedo/parcelas/{parcela}/costes/create', [CosteWebController::class, 'create'])
        ->name('costes.create');
    Route::post('vinedo/parcelas/{parcela}/costes', [CosteWebController::class, 'store'])
        ->name('costes.store');

    // Eliminar — shallow
    Route::delete('costes/{coste}', [CosteWebController::class, 'destroy'])
        ->name('costes.destroy');
});
