<?php

namespace Tests\Unit;

use App\Modules\CalendarioFenologico\Services\CalendarioCampana;
use PHPUnit\Framework\TestCase;

class CalendarioCampanaTest extends TestCase
{
    public function test_cada_fase_cubre_su_rango_bbch(): void
    {
        foreach (CalendarioCampana::FASES as $clave => $fase) {
            [$desde, $hasta] = $fase['bbch'];

            $this->assertSame($clave, CalendarioCampana::fase($desde), "El BBCH {$desde} debería ser {$clave}");
            $this->assertSame($clave, CalendarioCampana::fase($hasta), "El BBCH {$hasta} debería ser {$clave}");
        }
    }

    public function test_sin_bbch_no_hay_fase(): void
    {
        $this->assertNull(CalendarioCampana::fase(null));
    }
}
