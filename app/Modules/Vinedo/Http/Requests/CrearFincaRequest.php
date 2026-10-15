<?php

namespace App\Modules\Vinedo\Http\Requests;

/**
 * Alta de una finca con sus parcelas (formulario web «nueva finca»).
 * Cada parcela valida su variedad contra el cultivo de su propio uso.
 */
class CrearFincaRequest extends GuardarFincaRequest
{
    public function rules(): array
    {
        $reglas = array_merge(parent::rules(), [
            'parcelas' => 'required|array|min:1',
        ]);

        foreach (array_keys((array) $this->input('parcelas', [])) as $i) {
            foreach (GuardarParcelaRequest::reglas($this->input("parcelas.{$i}.uso")) as $campo => $regla) {
                $reglas["parcelas.{$i}.{$campo}"] = $regla;
            }
        }

        return $reglas;
    }
}
