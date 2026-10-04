<?php

namespace App\Modules\CuadernoDigital\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fertilizacion extends Model
{
    protected $table = 'fertilizaciones';

    public const TIPOS = [
        'mineral'        => 'Mineral (químico)',
        'organico'       => 'Orgánico (estiércol, compost…)',
        'organo_mineral' => 'Órgano-mineral',
        'enmienda'       => 'Enmienda (calizo, yeso…)',
    ];

    public const UNIDADES = ['kg/ha', 't/ha', 'l/ha', 'm3/ha'];

    public const METODOS = [
        'voleo'          => 'A voleo',
        'localizado'     => 'Localizado',
        'enterrado'      => 'Enterrado / incorporado',
        'fertirrigacion' => 'Fertirrigación',
        'foliar'         => 'Foliar',
    ];

    protected $fillable = [
        'parcela_id', 'user_id', 'fecha', 'tipo', 'producto',
        'riqueza_n', 'riqueza_p', 'riqueza_k', 'dosis', 'unidad',
        'superficie_ha', 'metodo', 'observaciones',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'riqueza_n'     => 'decimal:2',
        'riqueza_p'     => 'decimal:2',
        'riqueza_k'     => 'decimal:2',
        'dosis'         => 'decimal:3',
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

    /** Riqueza en formato habitual N-P-K (p. ej. "15-15-15"), o null si no se indicó. */
    public function getNpkAttribute(): ?string
    {
        if ($this->riqueza_n === null && $this->riqueza_p === null && $this->riqueza_k === null) {
            return null;
        }

        $fmt = fn ($v) => $v === null ? '0' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');

        return "{$fmt($this->riqueza_n)}-{$fmt($this->riqueza_p)}-{$fmt($this->riqueza_k)}";
    }
}
