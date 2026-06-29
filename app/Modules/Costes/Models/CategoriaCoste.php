<?php

namespace App\Modules\Costes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaCoste extends Model
{
    protected $fillable = ['nombre', 'tipo'];

    public function costes(): HasMany
    {
        return $this->hasMany(Coste::class, 'categoria_id');
    }
}
