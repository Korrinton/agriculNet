<?php

namespace App\Modules\Costes\Models;

use App\Models\User;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coste extends Model
{
    protected $fillable = [
        'parcela_id',
        'categoria_id',
        'user_id',
        'fecha',
        'importe',
        'descripcion',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'importe' => 'decimal:2',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaCoste::class, 'categoria_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
