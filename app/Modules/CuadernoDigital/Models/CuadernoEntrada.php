<?php

namespace App\Modules\CuadernoDigital\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuadernoEntrada extends Model
{
    protected $fillable = [
        'parcela_id',
        'user_id',
        'fecha',
        'tipo',
        'descripcion',
        'metadata',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'metadata' => 'array',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
