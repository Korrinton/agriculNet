<?php

use App\Modules\Vinedo\Http\Controllers\FincaController;
use App\Modules\Vinedo\Http\Controllers\ParcelaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('fincas', FincaController::class);
    Route::apiResource('fincas.parcelas', ParcelaController::class);
});
