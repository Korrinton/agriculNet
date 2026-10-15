<?php

namespace Tests\Unit;

use App\Modules\CalendarioFenologico\Services\GradosDiaCalculator;
use PHPUnit\Framework\TestCase;

class GradosDiaCalculatorTest extends TestCase
{
    public function test_grados_dia_sobre_la_base(): void
    {
        $calculator = new GradosDiaCalculator;

        // Media (30 + 14) / 2 = 22; base 10 → 12 grados-día
        $this->assertSame(12.0, $calculator->calcularDiario(30, 14));
    }

    public function test_nunca_resta_por_debajo_de_la_base(): void
    {
        $calculator = new GradosDiaCalculator;

        $this->assertSame(0.0, (float) $calculator->calcularDiario(8, 2));
        $this->assertSame(0.0, (float) $calculator->calcularDiario(12, 8)); // media exactamente en la base
    }

    public function test_admite_otra_temperatura_base(): void
    {
        $calculator = new GradosDiaCalculator;

        $this->assertSame(10.0, $calculator->calcularDiario(30, 14, 12.0));
    }
}
