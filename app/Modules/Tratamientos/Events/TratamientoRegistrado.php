<?php

namespace App\Modules\Tratamientos\Events;

use App\Modules\Tratamientos\Models\Tratamiento;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TratamientoRegistrado
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Tratamiento $tratamiento) {}
}
