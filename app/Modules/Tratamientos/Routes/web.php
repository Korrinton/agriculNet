<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tratamientos', fn() => view('tratamientos.index'))->name('tratamientos.index');
});
