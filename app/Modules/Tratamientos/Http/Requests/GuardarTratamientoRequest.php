<?php

namespace App\Modules\Tratamientos\Http\Requests;

use App\Models\User;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos de un tratamiento fitosanitario, compartidos por la web y la API para que no
 * diverjan. La autorización (policy de la finca) la hace el controlador.
 */
class GuardarTratamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = self::reglas($this->user());

        // Anidado bajo parcela (crear) o shallow bajo tratamiento (corregir)
        if ($parcela = $this->parcela()) {
            $reglas['superficie_tratada_ha'] = 'nullable|numeric|gt:0|lte:'.$parcela->superficie_ha;
        }

        return $reglas;
    }

    protected function parcela(): ?Parcela
    {
        $parcela = $this->route('parcela');
        if (! $parcela && ($tratamiento = $this->route('tratamiento')) instanceof Tratamiento) {
            $parcela = $tratamiento->parcela;
        }

        return $parcela instanceof Parcela ? $parcela : null;
    }

    public static function reglas(User $user): array
    {
        return [
            'producto_id' => ['required', ProductoFitosanitario::reglaVisiblePara($user)],
            'fecha' => 'required|date|before_or_equal:today',
            'dosis_l_ha' => 'required|numeric|min:0.001',
            'plazo_seguridad_dias' => 'nullable|integer|min:0|max:255',
            'precio_unitario' => 'nullable|numeric|min:0|max:99999',
            'motivo' => 'nullable|string|max:500',
            // Datos que exigen el Reglamento (UE) 2023/564 y la Orden APA/204/2023 en el registro de tratamientos
            'hora_inicio' => 'nullable|date_format:H:i',
            'bbch' => ['nullable', 'regex:/^\d{2}$/'],
            'justificacion' => 'nullable|string|max:500',
            'aplicador_nombre' => 'nullable|string|max:255',
            'aplicador_nif' => 'nullable|string|max:20',
            'aplicador_ropo' => 'nullable|string|max:50',
            'equipo_roma' => 'nullable|string|max:50',
            'equipo_inspeccion_fecha' => 'nullable|date|before_or_equal:today',
            'asesor_nombre' => 'nullable|string|max:255',
            'asesor_nif' => 'nullable|string|max:20',
            'asesor_ropo' => 'nullable|string|max:50',
            'asesor_fecha_validacion' => 'nullable|date|before_or_equal:today',
            'eficacia' => ['nullable', Rule::in(array_keys(Tratamiento::EFICACIAS))],
        ];
    }
}
