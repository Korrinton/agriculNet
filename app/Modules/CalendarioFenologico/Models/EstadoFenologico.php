<?php

namespace App\Modules\CalendarioFenologico\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoFenologico extends Model
{
    protected $table = 'estados_fenologicos';

    protected $fillable = ['nombre', 'codigo_bbch', 'descripcion', 'orden'];

    protected $casts = ['orden' => 'integer'];

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroFenologico::class);
    }
}
