<?php

namespace App\Modules\Vinedo\Rules;

use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** La variedad elegida tiene que ser del cultivo que corresponde al uso de la parcela. */
class VariedadDelCultivo implements ValidationRule
{
    public function __construct(private readonly ?string $uso) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cultivo = Parcela::cultivoDeUso($this->uso);
        if ($cultivo === null) {
            return; // el uso ya falla con su propia regla
        }

        $variedad = Variedad::find($value);
        if ($variedad && $variedad->cultivo !== $cultivo) {
            $fail(sprintf(
                '«%s» no es %s: una parcela de %s solo admite %s.',
                $variedad->nombre,
                $cultivo === 'herbaceo' ? 'un cultivo de secano' : 'una variedad de ' . mb_strtolower(Variedad::CULTIVOS[$cultivo]['nombre']),
                mb_strtolower($this->uso),
                $cultivo === 'herbaceo' ? 'cultivos herbáceos de secano' : 'variedades de ' . mb_strtolower(Variedad::CULTIVOS[$cultivo]['nombre']),
            ));
        }
    }
}
