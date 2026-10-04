<?php

use App\Modules\Meteorologia\Http\Controllers\Web\DatoMeteorologicoWebController;
use App\Modules\Meteorologia\Http\Controllers\Web\MeteorologiaWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('meteorologia', [MeteorologiaWebController::class, 'index'])->name('meteorologia.index');

    Route::prefix('fincas/{finca}/meteorologia')->name('meteorologia.datos.')->group(function () {
        Route::post('/vincular',    [DatoMeteorologicoWebController::class, 'vincularEstacion'])->name('vincular');
        Route::delete('/vincular',  [DatoMeteorologicoWebController::class, 'desvincularEstacion'])->name('desvincular');
        Route::post('/importar',    [DatoMeteorologicoWebController::class, 'importarAemet'])->name('importar');
        Route::post('/',            [DatoMeteorologicoWebController::class, 'store'])->name('store');
        Route::delete('/{dato}',    [DatoMeteorologicoWebController::class, 'destroy'])->name('destroy');
    });
});
