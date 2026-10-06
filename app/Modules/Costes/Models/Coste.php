<?php

namespace App\Modules\Costes\Models;

use App\Models\User;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gasto de una finca. Con parcela se imputa a ella; sin parcela es un gasto general de la finca
 * (seguro, gestoría, reparación del pozo…) y no se reparte entre las parcelas.
 */
class Coste extends Model
{
    protected $fillable = [
        'finca_id',
        'parcela_id',
        'categoria_id',
        'tratamiento_id',
        'user_id',
        'fecha',
        'importe',
        'descripcion',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'importe' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Un coste de parcela es siempre de la finca de esa parcela
        static::saving(function (Coste $coste) {
            if ($coste->parcela_id && ($coste->isDirty('parcela_id') || !$coste->finca_id)) {
                $coste->finca_id = Parcela::withTrashed()->whereKey($coste->parcela_id)->value('finca_id');
            }
        });
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function esDeFinca(): bool
    {
        return $this->parcela_id === null;
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaCoste::class, 'categoria_id');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
