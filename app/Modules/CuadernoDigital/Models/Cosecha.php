<?php

namespace App\Modules\CuadernoDigital\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cosecha extends Model
{
    /** Producto cosechado por defecto según el cultivo de la parcela. */
    public const PRODUCTO_POR_CULTIVO = [
        'vid'      => 'Uva',
        'olivo'    => 'Aceituna',
        'pistacho' => 'Pistacho',
        'herbaceo' => 'Grano',
    ];

    protected $fillable = [
        'parcela_id', 'user_id', 'fecha', 'producto', 'cantidad_kg', 'superficie_ha',
        'destino', 'destinatario_nif', 'albaran', 'observaciones',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'cantidad_kg'   => 'decimal:2',
        'superficie_ha' => 'decimal:4',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rendimiento en kg/ha, si se conoce la superficie cosechada. */
    public function getRendimientoKgHaAttribute(): ?float
    {
        $superficie = (float) ($this->superficie_ha ?: $this->parcela?->superficie_ha);

        return $superficie > 0 ? (float) $this->cantidad_kg / $superficie : null;
    }
}
