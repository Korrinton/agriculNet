<?php

use App\Modules\Costes\Http\Controllers\CosteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('parcelas/{parcela}/costes/resumen', [CosteController::class, 'resumen']);
    Route::apiResource('parcelas.costes', CosteController::class)
        ->only(['index', 'store']);
});
