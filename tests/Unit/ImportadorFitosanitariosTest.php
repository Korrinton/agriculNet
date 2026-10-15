<?php

namespace Tests\Unit;

use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use PHPUnit\Framework\TestCase;

class ImportadorFitosanitariosTest extends TestCase
{
    public function test_unidad_por_codigo_de_formulado(): void
    {
        // WG (gránulos dispersables) es sólido; SC y EC son líquidos
        $this->assertSame('kg', ImportadorFitosanitarios::unidadDeFormulado('AZUFRE 80% [WG] P/P'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado('COBRE 25% [SC] P/V'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado('GLIFOSATO 36% [EC] P/V'));
    }

    public function test_sin_codigo_decide_la_expresion_de_la_riqueza(): void
    {
        $this->assertSame('kg', ImportadorFitosanitarios::unidadDeFormulado('AZUFRE 98,5% P/P'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado('COBRE 20% P/V'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado(null));
    }
}
