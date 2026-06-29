<?php

use App\Modules\CalendarioFenologico\Http\Controllers\RegistroFenologicoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('estados-fenologicos', [RegistroFenologicoController::class, 'estados']);
    Route::apiResource('parcelas.fenologia', RegistroFenologicoController::class)
        ->only(['index', 'store', 'show']);
});
