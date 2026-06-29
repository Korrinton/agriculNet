<?php

namespace App\Modules\Tratamientos\Services;

use App\Modules\Tratamientos\Events\TratamientoRegistrado;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Parcela;
use App\Models\User;

class TratamientoService
{
    public function registrar(Parcela $parcela, User $user, array $data): Tratamiento
    {
        $tratamiento = Tratamiento::create(array_merge($data, [
            'parcela_id' => $parcela->id,
            'user_id'    => $user->id,
        ]));

        TratamientoRegistrado::dispatch($tratamiento);

        return $tratamiento;
    }
}
