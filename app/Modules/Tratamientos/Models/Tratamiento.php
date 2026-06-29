<?php

namespace App\Modules\Tratamientos\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tratamiento extends Model
{
    protected $fillable = [
        'parcela_id',
        'producto_id',
        'user_id',
        'fecha',
        'dosis_l_ha',
        'motivo',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'dosis_l_ha' => 'decimal:4',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoFitosanitario::class, 'producto_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fechaFinalPlazoSeguridad(): ?\Carbon\Carbon
    {
        if (!$this->producto || !$this->producto->plazo_seguridad_dias) {
            return null;
        }
        return $this->fecha->addDays($this->producto->plazo_seguridad_dias);
    }
}
