<?php

namespace App\Modules\Vinedo\Models;

use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\Coste;
use App\Modules\Tratamientos\Models\Tratamiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcela extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'finca_id',
        'variedad_id',
        'nombre',
        'uso',
        'agregado',
        'poligono',
        'parcela_sigpac',
        'recinto',
        'superficie_ha',
        'año_plantacion',
        'sistema_conduccion',
    ];

    protected $casts = [
        'superficie_ha'  => 'decimal:4',
        'año_plantacion' => 'integer',
        'agregado'       => 'integer',
        'poligono'       => 'integer',
        'parcela_sigpac' => 'integer',
        'recinto'        => 'integer',
    ];

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    public function variedad(): BelongsTo
    {
        return $this->belongsTo(Variedad::class);
    }

    public function registrosFenologicos(): HasMany
    {
        return $this->hasMany(RegistroFenologico::class);
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class);
    }

    public function costes(): HasMany
    {
        return $this->hasMany(Coste::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }
}
