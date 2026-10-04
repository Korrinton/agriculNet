<?php

namespace App\Modules\Meteorologia\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PrediccionMeteorologica extends Model
{
    protected $table = 'predicciones_meteorologicas';

    protected $fillable = [
        'provincia_cod',
        'municipio_cod',
        'fecha',
        'temp_max',
        'temp_min',
        'prob_precipitacion',
        'humedad_max',
        'humedad_min',
        'estado_cielo',
        'elaborado_at',
    ];

    protected $casts = [
        'fecha'              => 'date',
        'temp_max'           => 'decimal:2',
        'temp_min'           => 'decimal:2',
        'prob_precipitacion' => 'integer',
        'humedad_max'        => 'integer',
        'humedad_min'        => 'integer',
        'elaborado_at'       => 'datetime',
    ];

    public function scopeDelMunicipio(Builder $query, int $provinciaCod, int $municipioCod): Builder
    {
        return $query->where('provincia_cod', $provinciaCod)->where('municipio_cod', $municipioCod);
    }
}
