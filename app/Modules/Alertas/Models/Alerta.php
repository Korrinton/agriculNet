<?php

namespace App\Modules\Alertas\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $fillable = [
        'parcela_id',
        'user_id',
        'tipo',
        'nivel',
        'mensaje',
        'leida',
    ];

    protected $casts = [
        'leida' => 'boolean',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marcarLeida(): void
    {
        $this->update(['leida' => true]);
    }
}
