<?php

use App\Modules\Usuarios\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('perfil', [ProfileController::class, 'show']);
    Route::patch('perfil', [ProfileController::class, 'update']);
});
