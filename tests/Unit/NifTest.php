<?php

namespace Tests\Unit;

use App\Support\Nif;
use PHPUnit\Framework\TestCase;

class NifTest extends TestCase
{
    public function test_normaliza_mayusculas_espacios_y_guiones(): void
    {
        $this->assertSame('Z1234567R', Nif::normalizar(' z-1234567 r '));
        $this->assertSame('B86000000', Nif::normalizar('b 86000000'));
    }

    public function test_el_nulo_se_conserva(): void
    {
        $this->assertNull(Nif::normalizar(null));
    }
}
