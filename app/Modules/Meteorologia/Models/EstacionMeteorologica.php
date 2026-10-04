<?php

namespace App\Modules\Meteorologia\Models;

use App\Modules\Vinedo\Models\Finca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstacionMeteorologica extends Model
{
    protected $table = 'estaciones_meteorologicas';

    protected $fillable = ['nombre', 'latitud', 'longitud', 'fuente', 'codigo_externo', 'provincia_cod'];

    protected $casts = [
        'latitud'  => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    public function fincas(): HasMany
    {
        return $this->hasMany(Finca::class, 'estacion_meteorologica_id');
    }

    public function datos(): HasMany
    {
        return $this->hasMany(DatoMeteorologico::class, 'estacion_id');
    }
}
