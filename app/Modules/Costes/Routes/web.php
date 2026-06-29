<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('costes', fn() => view('costes.index'))->name('costes.index');
});
