<?php

namespace App\Modules\Vinedo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variedad extends Model
{
    protected $table = 'variedades';

    protected $fillable = ['nombre', 'tipo', 'descripcion'];

    protected $casts = [
        'tipo' => 'string',
    ];

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }
}
