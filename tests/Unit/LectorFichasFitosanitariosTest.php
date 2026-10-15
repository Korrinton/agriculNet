<?php

namespace Tests\Unit;

use App\Modules\Tratamientos\Services\LectorFichasFitosanitarios;
use PHPUnit\Framework\TestCase;

class LectorFichasFitosanitariosTest extends TestCase
{
    private function ficha(string $tabla): string
    {
        return "Cabecera de la ficha\nUSO P.S. (días)\n{$tabla}\nPlazos de Seguridad: lo anterior";
    }

    public function test_lee_los_plazos_de_la_tabla(): void
    {
        $plazos = LectorFichasFitosanitarios::plazosPorCultivo($this->ficha("Vid\t21\nOlivo, Pistachero\tNP"));

        $this->assertSame(21, $plazos['vid']);
        $this->assertArrayHasKey('olivo', $plazos);
        $this->assertNull($plazos['olivo']);     // NP = no procede
        $this->assertNull($plazos['pistacho']);
    }

    public function test_el_generico_cereales_vale_para_trigo_y_cebada(): void
    {
        $plazos = LectorFichasFitosanitarios::plazosPorCultivo($this->ficha("Cereales de invierno\t45"));

        $this->assertSame(45, $plazos['trigo']);
        $this->assertSame(45, $plazos['cebada']);
        $this->assertArrayNotHasKey('vid', $plazos);
    }

    public function test_con_varios_plazos_se_toma_el_mas_largo(): void
    {
        $plazos = LectorFichasFitosanitarios::plazosPorCultivo(
            $this->ficha("Vid\t7 DÍAS UVAS DE MESA; 14 DÍAS UVAS DE VINIFICACIÓN")
        );

        $this->assertSame(14, $plazos['vid']);
    }

    public function test_las_horas_se_redondean_al_dia(): void
    {
        $plazos = LectorFichasFitosanitarios::plazosPorCultivo($this->ficha("Vid\t4 HORAS"));

        $this->assertSame(1, $plazos['vid']);
    }

    public function test_sin_tabla_no_hay_plazos(): void
    {
        $this->assertSame([], LectorFichasFitosanitarios::plazosPorCultivo('Un PDF sin la tabla esperada'));
    }
}
