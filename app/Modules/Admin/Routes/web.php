<?php

use App\Modules\Admin\Http\Controllers\CatalogoAdminController;
use App\Modules\Admin\Http\Controllers\CategoriaCosteAdminController;
use App\Modules\Admin\Http\Controllers\PanelAdminController;
use App\Modules\Admin\Http\Controllers\TareaAdminController;
use App\Modules\Admin\Http\Controllers\UsuarioAdminController;
use App\Modules\Admin\Http\Controllers\VariedadAdminController;
use Illuminate\Support\Facades\Route;

// Backoffice: solo usuarios con is_admin (se da con `php artisan usuarios:admin email`)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', PanelAdminController::class)->name('panel');

    Route::get('usuarios', [UsuarioAdminController::class, 'index'])->name('usuarios.index');
    Route::post('usuarios/{usuario}/bloquear', [UsuarioAdminController::class, 'bloquear'])->name('usuarios.bloquear');
    Route::post('usuarios/{usuario}/desbloquear', [UsuarioAdminController::class, 'desbloquear'])->name('usuarios.desbloquear');
    Route::delete('usuarios/{usuario}', [UsuarioAdminController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('tareas', [TareaAdminController::class, 'index'])->name('tareas.index');
    Route::post('tareas/{comando}', [TareaAdminController::class, 'lanzar'])->name('tareas.lanzar');

    Route::get('catalogos/variedades', [VariedadAdminController::class, 'index'])->name('variedades.index');
    Route::get('catalogos/variedades/create', [VariedadAdminController::class, 'create'])->name('variedades.create');
    Route::post('catalogos/variedades', [VariedadAdminController::class, 'store'])->name('variedades.store');
    Route::get('catalogos/variedades/{variedad}/edit', [VariedadAdminController::class, 'edit'])->name('variedades.edit');
    Route::put('catalogos/variedades/{variedad}', [VariedadAdminController::class, 'update'])->name('variedades.update');
    Route::delete('catalogos/variedades/{variedad}', [VariedadAdminController::class, 'destroy'])->name('variedades.destroy');

    Route::get('catalogos/categorias', [CategoriaCosteAdminController::class, 'index'])->name('categorias.index');
    Route::post('catalogos/categorias', [CategoriaCosteAdminController::class, 'store'])->name('categorias.store');
    Route::put('catalogos/categorias/{categoria}', [CategoriaCosteAdminController::class, 'update'])->name('categorias.update');
    Route::delete('catalogos/categorias/{categoria}', [CategoriaCosteAdminController::class, 'destroy'])->name('categorias.destroy');

    Route::get('catalogos/estaciones', [CatalogoAdminController::class, 'estaciones'])->name('estaciones.index');
    Route::get('catalogos/fitosanitarios', [CatalogoAdminController::class, 'fitosanitarios'])->name('fitosanitarios.index');
});
