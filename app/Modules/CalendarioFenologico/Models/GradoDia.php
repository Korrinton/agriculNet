<?php

namespace App\Modules\CalendarioFenologico\Models;

use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradoDia extends Model
{
    protected $table = 'grados_dia';

    protected $fillable = ['parcela_id', 'fecha', 'acumulado', 'temperatura_base'];

    protected $casts = [
        'fecha'            => 'date',
        'acumulado'        => 'decimal:2',
        'temperatura_base' => 'decimal:1',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }
}
