<?php

namespace App\Modules\Tratamientos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoFitosanitario extends Model
{
    protected $table = 'productos_fitosanitarios';

    protected $fillable = [
        'nombre',
        'numero_registro',
        'ingrediente_activo',
        'plazo_seguridad_dias',
        'dosis_max_l_ha',
    ];

    protected $casts = [
        'plazo_seguridad_dias' => 'integer',
        'dosis_max_l_ha'       => 'decimal:3',
    ];

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'producto_id');
    }
}
