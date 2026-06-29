<?php

namespace App\Modules\Vinedo\Services;

use App\Models\User;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Pagination\LengthAwarePaginator;

class VinedoService
{
    public function fincasDeUsuario(User $user): LengthAwarePaginator
    {
        return Finca::where('user_id', $user->id)
            ->with('parcelas.variedad')
            ->paginate(15);
    }

    public function crearFinca(User $user, array $data): Finca
    {
        return Finca::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function crearParcela(Finca $finca, array $data): Parcela
    {
        return $finca->parcelas()->create($data);
    }

    public function superficieTotalFinca(Finca $finca): float
    {
        return (float) $finca->parcelas()->sum('superficie_ha');
    }
}
