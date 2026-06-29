<?php

use App\Modules\Meteorologia\Http\Controllers\EstacionMeteorologicaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('estaciones-meteorologicas', EstacionMeteorologicaController::class)
        ->only(['index', 'store', 'show']);
});
