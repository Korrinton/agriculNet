<?php

namespace App\Modules\Vinedo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de una finca, compartidos por la web y la API para que no diverjan.
 * La autorización (policy de Finca) la hace el controlador.
 */
class GuardarFincaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::reglas();
    }

    public static function reglas(): array
    {
        return [
            'provincia_cod' => 'required|integer|min:1|max:52',
            'municipio_cod' => 'required|integer|min:1|max:999',
            'paraje' => 'nullable|string|max:255',
            // Titular de la explotación, para el cuaderno de explotación
            'titular_nombre' => 'nullable|string|max:255',
            'titular_nif' => 'nullable|string|max:20',
            'rea_numero' => 'nullable|string|max:50',
        ];
    }
}
