<?php

namespace App\Modules\Alertas\Services;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertaService
{
    public function alertasDeUsuario(User $user, bool $soloNoLeidas = false): LengthAwarePaginator
    {
        return Alerta::where('user_id', $user->id)
            ->when($soloNoLeidas, fn ($q) => $q->where('leida', false))
            ->with('parcela')
            ->latest()
            ->paginate(20);
    }

    public function crear(Parcela $parcela, User $user, string $tipo, string $nivel, string $mensaje): Alerta
    {
        return Alerta::create([
            'parcela_id' => $parcela->id,
            'user_id'    => $user->id,
            'tipo'       => $tipo,
            'nivel'      => $nivel,
            'mensaje'    => $mensaje,
            'leida'      => false,
        ]);
    }

    public function marcarTodasLeidas(User $user): int
    {
        return Alerta::where('user_id', $user->id)
            ->where('leida', false)
            ->update(['leida' => true]);
    }
}
