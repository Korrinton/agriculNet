<?php

namespace App\Modules\CalendarioFenologico\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroFenologico extends Model
{
    protected $table = 'registro_fenologico';

    protected $fillable = [
        'parcela_id',
        'estado_fenologico_id',
        'user_id',
        'fecha_observacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_observacion' => 'date',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoFenologico::class, 'estado_fenologico_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
