<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('meteorologia', fn() => view('meteorologia.index'))->name('meteorologia.index');
});
