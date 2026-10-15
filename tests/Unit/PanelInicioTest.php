<?php

namespace Tests\Unit;

use App\Services\PanelInicio;
use PHPUnit\Framework\TestCase;

class PanelInicioTest extends TestCase
{
    public function test_icono_segun_el_estado_del_cielo_de_aemet(): void
    {
        $this->assertSame('tormenta', PanelInicio::iconoCielo('Intervalos nubosos con tormenta'));
        $this->assertSame('nieve', PanelInicio::iconoCielo('Nieve'));
        $this->assertSame('lluvia', PanelInicio::iconoCielo('Cubierto con lluvia escasa'));
        $this->assertSame('niebla', PanelInicio::iconoCielo('Bruma'));
        $this->assertSame('sol', PanelInicio::iconoCielo('Despejado'));
        $this->assertSame('sol-nubes', PanelInicio::iconoCielo('Poco nuboso'));
        $this->assertSame('sol-nubes', PanelInicio::iconoCielo('Intervalos nubosos'));
        $this->assertSame('nubes', PanelInicio::iconoCielo('Muy nuboso'));
        $this->assertSame('desconocido', PanelInicio::iconoCielo(''));
        $this->assertSame('desconocido', PanelInicio::iconoCielo(null));
    }
}
