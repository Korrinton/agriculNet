<?php

namespace App\Modules\Tratamientos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lo que paga un usuario por un producto, en € por litro o kg (la unidad de la dosis). */
class PrecioProductoFitosanitario extends Model
{
    protected $table = 'precios_productos_fitosanitarios';

    protected $fillable = ['user_id', 'producto_id', 'precio'];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoFitosanitario::class, 'producto_id');
    }
}
