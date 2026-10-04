<?php

use App\Modules\CuadernoDigital\Http\Controllers\Web\CosechaWebController;
use App\Modules\CuadernoDigital\Http\Controllers\Web\CuadernoWebController;
use App\Modules\CuadernoDigital\Http\Controllers\Web\FertilizacionWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Cuaderno por finca y campaña (?finca=&anio=)
    Route::get('cuaderno', [CuadernoWebController::class, 'index'])->name('cuaderno.index');
    Route::get('cuaderno/{finca}/{anio}/imprimir', [CuadernoWebController::class, 'imprimir'])
        ->whereNumber('anio')->name('cuaderno.imprimir');
    Route::get('cuaderno/{finca}/{anio}/excel', [CuadernoWebController::class, 'excel'])
        ->whereNumber('anio')->name('cuaderno.excel');

    // Registros propios del cuaderno, por finca (se elige la parcela en el formulario)
    Route::get('cuaderno/{finca}/fertilizaciones/create', [FertilizacionWebController::class, 'create'])->name('fertilizaciones.create');
    Route::post('cuaderno/{finca}/fertilizaciones', [FertilizacionWebController::class, 'store'])->name('fertilizaciones.store');
    Route::delete('fertilizaciones/{fertilizacion}', [FertilizacionWebController::class, 'destroy'])->name('fertilizaciones.destroy');

    Route::get('cuaderno/{finca}/cosechas/create', [CosechaWebController::class, 'create'])->name('cosechas.create');
    Route::post('cuaderno/{finca}/cosechas', [CosechaWebController::class, 'store'])->name('cosechas.store');
    Route::delete('cosechas/{cosecha}', [CosechaWebController::class, 'destroy'])->name('cosechas.destroy');
});
