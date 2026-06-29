<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('alertas', fn() => view('alertas.index'))->name('alertas.index');
});
