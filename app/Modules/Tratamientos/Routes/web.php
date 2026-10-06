<?php

use App\Modules\Tratamientos\Http\Controllers\Web\ProductoFitosanitarioWebController;
use App\Modules\Tratamientos\Http\Controllers\Web\TratamientoWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Índice global — todos los tratamientos del usuario
    Route::get('tratamientos', [TratamientoWebController::class, 'index'])
        ->name('tratamientos.index');

    // Catálogo de productos: registro oficial del MAPA (solo lectura) + productos propios del usuario
    Route::put('tratamientos/productos/{producto}/precio', [ProductoFitosanitarioWebController::class, 'precio'])
        ->name('tratamientos.productos.precio');
    Route::resource('tratamientos/productos', ProductoFitosanitarioWebController::class)
        ->except('show')
        ->parameters(['productos' => 'producto'])
        ->names('tratamientos.productos');

    // Crear / guardar para varias parcelas de una finca a la vez (un tratamiento por parcela)
    Route::get('vinedo/fincas/{finca}/tratamientos/create', [TratamientoWebController::class, 'createFinca'])
        ->name('tratamientos.finca.create');
    Route::post('vinedo/fincas/{finca}/tratamientos', [TratamientoWebController::class, 'storeFinca'])
        ->name('tratamientos.finca.store');

    // Crear / guardar — anidado bajo parcela
    Route::get('vinedo/parcelas/{parcela}/tratamientos/create', [TratamientoWebController::class, 'create'])
        ->name('tratamientos.create');
    Route::post('vinedo/parcelas/{parcela}/tratamientos', [TratamientoWebController::class, 'store'])
        ->name('tratamientos.store');

    // Editar — shallow
    Route::get('tratamientos/{tratamiento}/edit', [TratamientoWebController::class, 'edit'])
        ->whereNumber('tratamiento')->name('tratamientos.edit');
    Route::put('tratamientos/{tratamiento}', [TratamientoWebController::class, 'update'])
        ->whereNumber('tratamiento')->name('tratamientos.update');

    // Eliminar — shallow
    Route::delete('tratamientos/{tratamiento}', [TratamientoWebController::class, 'destroy'])
        ->name('tratamientos.destroy');
});
