<?php

use App\Modules\CuadernoDigital\Http\Controllers\CuadernoDigitalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('fincas/{finca}/cuaderno/{anio}', [CuadernoDigitalController::class, 'show'])->whereNumber('anio');
    Route::get('fincas/{finca}/cuaderno/{anio}/excel', [CuadernoDigitalController::class, 'excel'])->whereNumber('anio');
});
