<?php

namespace App\Modules\CalendarioFenologico\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de una observación fenológica, compartidos por la web y la API para que no
 * diverjan. La autorización (policy de la finca) la hace el controlador.
 */
class GuardarRegistroFenologicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_fenologico_id' => 'required|exists:estados_fenologicos,id',
            'fecha_observacion' => 'required|date|before_or_equal:today',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }
}
