<?php

namespace Tests\Unit;

use App\Modules\Meteorologia\Services\AemetClient;
use PHPUnit\Framework\TestCase;

class AemetClientTest extends TestCase
{
    public function test_parse_decimal_con_coma_espanola(): void
    {
        $this->assertSame(12.5, AemetClient::parseDecimal('12,5'));
        $this->assertSame(-3.4, AemetClient::parseDecimal('-3,4'));
        $this->assertSame(0.0, AemetClient::parseDecimal('0,0'));
    }

    public function test_parse_decimal_de_valores_sin_dato(): void
    {
        $this->assertNull(AemetClient::parseDecimal(null));
        $this->assertNull(AemetClient::parseDecimal(''));
        // «Ip» = precipitación inapreciable; «Acum» = acumulada en otro día
        $this->assertNull(AemetClient::parseDecimal('Ip'));
        $this->assertNull(AemetClient::parseDecimal('Acum'));
        $this->assertNull(AemetClient::parseDecimal('-'));
    }

    public function test_dms_a_decimal(): void
    {
        // 41° 02' 34" N → 41.0428; 003° 45' 12" W → -3.7533
        $this->assertEqualsWithDelta(41.0428, AemetClient::dmsToDecimal('410234N'), 0.0001);
        $this->assertEqualsWithDelta(-3.7533, AemetClient::dmsToDecimal('0034512W'), 0.0001);
        $this->assertEqualsWithDelta(-28.1583, AemetClient::dmsToDecimal('280930S'), 0.0001);
    }
}
