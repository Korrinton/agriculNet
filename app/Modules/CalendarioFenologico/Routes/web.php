<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('fenologia', fn() => view('fenologia.index'))->name('fenologia.index');
});
