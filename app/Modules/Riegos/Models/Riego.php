<?php

namespace App\Modules\Riegos\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Riego extends Model
{
    public const SISTEMAS = [
        'goteo'          => 'Goteo',
        'microaspersion' => 'Microaspersión',
        'aspersion'      => 'Aspersión',
        'pivot'          => 'Pívot',
        'gravedad'       => 'Gravedad / a manta',
    ];

    public const ORIGENES = [
        'pozo'               => 'Pozo propio',
        'comunidad_regantes' => 'Comunidad de regantes',
        'balsa'              => 'Balsa',
        'red'                => 'Red municipal',
        'otro'               => 'Otro',
    ];

    protected $fillable = [
        'parcela_id', 'user_id', 'fecha', 'volumen_m3', 'superficie_ha',
        'duracion_horas', 'sistema', 'origen', 'observaciones',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'volumen_m3'     => 'decimal:2',
        'superficie_ha'  => 'decimal:4',
        'duracion_horas' => 'decimal:2',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Dosis aplicada en m³/ha. */
    public function getDosisM3HaAttribute(): ?float
    {
        return (float) $this->superficie_ha > 0 ? (float) $this->volumen_m3 / (float) $this->superficie_ha : null;
    }

    /** Dosis en mm (litros por m²): 1 mm = 10 m³/ha. Comparable con la lluvia. */
    public function getDosisMmAttribute(): ?float
    {
        return $this->dosis_m3_ha !== null ? $this->dosis_m3_ha / 10 : null;
    }
}
