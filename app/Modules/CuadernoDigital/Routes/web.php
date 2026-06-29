<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('cuaderno', fn() => view('cuaderno.index'))->name('cuaderno.index');
});
