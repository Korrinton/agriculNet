<?php

namespace App\Modules\Meteorologia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstacionMeteorologica extends Model
{
    protected $table = 'estaciones_meteorologicas';

    protected $fillable = ['nombre', 'latitud', 'longitud', 'fuente', 'codigo_externo'];

    protected $casts = [
        'latitud'  => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    public function datos(): HasMany
    {
        return $this->hasMany(DatoMeteorologico::class, 'estacion_id');
    }
}
