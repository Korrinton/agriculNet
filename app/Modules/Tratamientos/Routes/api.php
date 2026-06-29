<?php

use App\Modules\Tratamientos\Http\Controllers\TratamientoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('parcelas.tratamientos', TratamientoController::class)
        ->only(['index', 'store', 'show']);
});
