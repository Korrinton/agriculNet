<?php

namespace App\Modules\Costes\Http\Requests;

use App\Modules\Vinedo\Models\Finca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos de un coste, compartidos por la web y la API para que no diverjan.
 * La autorización (policy de la finca) la hace el controlador.
 */
class GuardarCosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::reglas();
    }

    /** Con finca (formulario web), el gasto puede imputarse a una de sus parcelas. */
    public static function reglas(?Finca $finca = null): array
    {
        $reglas = [
            'categoria_id' => 'required|exists:categoria_costes,id',
            'fecha' => 'required|date|before_or_equal:today',
            'importe' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
        ];

        if ($finca) {
            $reglas['parcela_id'] = ['nullable', 'integer', Rule::in($finca->parcelas()->pluck('id')->all())];
        }

        return $reglas;
    }
}
