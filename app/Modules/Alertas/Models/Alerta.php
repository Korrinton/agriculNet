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
        'clave',
        'nivel',
        'mensaje',
        'leida',
    ];

    protected $casts = [
        'leida'         => 'boolean',
        'notificada_at' => 'datetime',
    ];

    /** Cómo se nombra cada nivel fuera de la aplicación (correo). */
    public const NIVELES = [
        'critical' => 'Importante',
        'warning'  => 'Aviso',
        'info'     => 'Información',
    ];

    /** Preferencia del usuario (users.alertas_por_correo) => niveles que recibe por correo. */
    public const PREFERENCIAS_CORREO = [
        'todas'   => ['etiqueta' => 'Todas las alertas', 'niveles' => ['critical', 'warning', 'info']],
        'avisos'  => ['etiqueta' => 'Avisos e importantes', 'niveles' => ['critical', 'warning']],
        'criticas' => ['etiqueta' => 'Solo las importantes', 'niveles' => ['critical']],
        'ninguna' => ['etiqueta' => 'Ninguna', 'niveles' => []],
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
