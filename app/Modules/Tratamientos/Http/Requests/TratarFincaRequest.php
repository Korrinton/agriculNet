<?php

namespace App\Modules\Tratamientos\Http\Requests;

use Illuminate\Validation\Rule;

/** Una misma aplicación en varias parcelas de la finca (formulario «tratar finca»). */
class TratarFincaRequest extends GuardarTratamientoRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'parcelas' => 'required|array|min:1',
            'parcelas.*' => ['integer', Rule::in($this->route('finca')->parcelas()->pluck('id')->all())],
        ];
    }

    public function messages(): array
    {
        return [
            'parcelas.required' => 'Marca al menos una parcela.',
            'parcelas.*.in' => 'Alguna de las parcelas no es de esta finca.',
        ];
    }
}
