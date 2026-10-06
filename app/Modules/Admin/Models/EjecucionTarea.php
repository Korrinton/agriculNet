<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjecucionTarea extends Model
{
    protected $table = 'ejecuciones_tareas';

    protected $fillable = ['comando', 'inicio', 'fin', 'codigo_salida', 'salida', 'user_id'];

    protected $casts = [
        'inicio'        => 'datetime',
        'fin'           => 'datetime',
        'codigo_salida' => 'integer',
    ];

    /** Pasado este tiempo sin terminar, se da por interrumpida (proceso caído o contenedor reiniciado). */
    private const HORAS_SIN_TERMINAR = 2;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ok, error, en_curso o interrumpida */
    public function estado(): string
    {
        if ($this->fin === null) {
            return $this->inicio->lt(now()->subHours(self::HORAS_SIN_TERMINAR)) ? 'interrumpida' : 'en_curso';
        }

        return $this->codigo_salida === 0 ? 'ok' : 'error';
    }

    public function duracionSegundos(): ?int
    {
        return $this->fin ? (int) $this->inicio->diffInSeconds($this->fin) : null;
    }
}
