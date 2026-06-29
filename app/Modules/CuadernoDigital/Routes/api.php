<?php

use App\Modules\CuadernoDigital\Http\Controllers\CuadernoDigitalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('parcelas/{parcela}/cuaderno', [CuadernoDigitalController::class, 'index']);
    Route::post('fincas/{finca}/exportar-cue', [CuadernoDigitalController::class, 'exportarCUE']);
});
