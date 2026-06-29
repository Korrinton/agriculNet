<?php

use App\Modules\Vinedo\Http\Controllers\Web\FincaWebController;
use App\Modules\Vinedo\Http\Controllers\Web\ParcelaWebController;
use App\Modules\Vinedo\Http\Controllers\Web\SigpacProxyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Fincas
    Route::resource('vinedo/fincas', FincaWebController::class)->names([
        'index'   => 'vinedo.fincas.index',
        'create'  => 'vinedo.fincas.create',
        'store'   => 'vinedo.fincas.store',
        'show'    => 'vinedo.fincas.show',
        'edit'    => 'vinedo.fincas.edit',
        'update'  => 'vinedo.fincas.update',
        'destroy' => 'vinedo.fincas.destroy',
    ]);

    // Parcelas (anidadas bajo finca para create/store; shallow para edit/update/destroy)
    Route::get('vinedo/fincas/{finca}/parcelas/create', [ParcelaWebController::class, 'create'])
        ->name('vinedo.parcelas.create');
    Route::post('vinedo/fincas/{finca}/parcelas', [ParcelaWebController::class, 'store'])
        ->name('vinedo.parcelas.store');
    Route::get('vinedo/parcelas/{parcela}/edit', [ParcelaWebController::class, 'edit'])
        ->name('vinedo.parcelas.edit');
    Route::put('vinedo/parcelas/{parcela}', [ParcelaWebController::class, 'update'])
        ->name('vinedo.parcelas.update');
    Route::delete('vinedo/parcelas/{parcela}', [ParcelaWebController::class, 'destroy'])
        ->name('vinedo.parcelas.destroy');

    // Proxy SIGPAC (por parcela)
    Route::get('vinedo/parcelas/{parcela}/sigpac', [SigpacProxyController::class, 'recinto'])
        ->name('vinedo.parcelas.sigpac');
});
