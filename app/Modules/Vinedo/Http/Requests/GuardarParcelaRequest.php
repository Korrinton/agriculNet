<?php

namespace App\Modules\Vinedo\Http\Requests;

use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Rules\VariedadDelCultivo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos de una parcela, compartidos por la web y la API para que no diverjan.
 * La autorización (policy de la finca) la hace el controlador.
 */
class GuardarParcelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::reglas($this->input('uso'));
    }

    public static function reglas(?string $uso): array
    {
        return [
            'nombre' => 'nullable|string|max:255',
            'uso' => ['required', Rule::in(Parcela::usos())],
            'variedad_id' => ['nullable', 'integer', 'exists:variedades,id', new VariedadDelCultivo($uso)],
            'superficie_ha' => 'required|numeric|min:0.0001',
            'año_plantacion' => 'nullable|integer|min:1900|max:2099',
            'sistema_conduccion' => 'nullable|string|max:100',
            'agregado' => 'nullable|integer|min:0|max:99',
            'poligono' => 'nullable|integer|min:1',
            'parcela_sigpac' => 'nullable|integer|min:1',
            'recinto' => 'nullable|integer|min:1',
        ];
    }
}
