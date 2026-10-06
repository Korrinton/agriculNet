<?php

namespace App\Modules\Tratamientos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Plazo de seguridad oficial de un producto en un cultivo (ficha del registro del MAPA). */
class PlazoSeguridadProducto extends Model
{
    protected $table = 'plazos_seguridad_productos';

    protected $fillable = ['producto_id', 'cultivo', 'dias'];

    protected $casts = [
        'dias' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoFitosanitario::class, 'producto_id');
    }
}
