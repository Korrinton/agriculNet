<?php

use App\Modules\Riegos\Http\Controllers\Web\RiegoWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Riegos por finca y campaña (?finca=&anio=)
    Route::get('riegos', [RiegoWebController::class, 'index'])->name('riegos.index');

    // Anotar — por finca (se elige la parcela en el formulario)
    Route::get('riegos/{finca}/create', [RiegoWebController::class, 'create'])->name('riegos.create');
    Route::post('riegos/{finca}', [RiegoWebController::class, 'store'])->name('riegos.store');

    Route::delete('riegos/registro/{riego}', [RiegoWebController::class, 'destroy'])->name('riegos.destroy');
});
