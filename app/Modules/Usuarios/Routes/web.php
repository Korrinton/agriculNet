<?php

use App\Modules\Usuarios\Http\Controllers\DescargaDatosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:5,1'])->group(function () {
    Route::get('profile/mis-datos', DescargaDatosController::class)->name('profile.datos');
});
