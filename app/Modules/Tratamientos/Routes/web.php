<?php

use App\Modules\Tratamientos\Http\Controllers\Web\TratamientoWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Índice global — todos los tratamientos del usuario
    Route::get('tratamientos', [TratamientoWebController::class, 'index'])
        ->name('tratamientos.index');

    // Crear / guardar — anidado bajo parcela
    Route::get('vinedo/parcelas/{parcela}/tratamientos/create', [TratamientoWebController::class, 'create'])
        ->name('tratamientos.create');
    Route::post('vinedo/parcelas/{parcela}/tratamientos', [TratamientoWebController::class, 'store'])
        ->name('tratamientos.store');

    // Eliminar — shallow
    Route::delete('tratamientos/{tratamiento}', [TratamientoWebController::class, 'destroy'])
        ->name('tratamientos.destroy');
});
