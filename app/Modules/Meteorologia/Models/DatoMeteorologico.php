<?php

namespace App\Modules\Meteorologia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatoMeteorologico extends Model
{
    protected $table = 'datos_meteorologicos';

    protected $fillable = [
        'estacion_id',
        'fecha',
        'temp_max',
        'temp_min',
        'precipitacion_mm',
        'humedad_pct',
        'viento_kmh',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'temp_max'         => 'decimal:2',
        'temp_min'         => 'decimal:2',
        'precipitacion_mm' => 'decimal:2',
        'humedad_pct'      => 'integer',
        'viento_kmh'       => 'decimal:1',
    ];

    public function estacion(): BelongsTo
    {
        return $this->belongsTo(EstacionMeteorologica::class, 'estacion_id');
    }
}
