<?php

namespace Tests\Feature\CalendarioFenologico;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\CalendarioFenologico\Services\CalendarioCampana;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarioCampanaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Parcela $parcela;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'pancierto']);
        $this->parcela = Parcela::create([
            'finca_id' => $finca->id, 'nombre' => 'Parcela 126', 'uso' => 'Viña en espaldera', 'superficie_ha' => 1.9,
            'agregado' => 0, 'poligono' => 70, 'parcela_sigpac' => 126,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function observar(string $bbch, string $fecha, ?Parcela $parcela = null): void
    {
        $estado = EstadoFenologico::firstOrCreate(['codigo_bbch' => $bbch], ['nombre' => "Estado {$bbch}"]);
        RegistroFenologico::create([
            'parcela_id' => ($parcela ?? $this->parcela)->id, 'estado_fenologico_id' => $estado->id,
            'user_id' => $this->user->id, 'fecha_observacion' => $fecha,
        ]);
    }

    private function construir(int $anio, string $hoy): array
    {
        return app(CalendarioCampana::class)->construir(collect([$this->parcela->id]), $anio, Carbon::parse($hoy));
    }

    /** % del año 2026 (365 días) en que empieza el día indicado. */
    private function pct(string $fecha): float
    {
        return round(Carbon::parse('2026-01-01')->diffInDays(Carbon::parse($fecha)) / 365 * 100, 3);
    }

    // ── bandas ───────────────────────────────────────────────────────────────

    public function test_cada_observacion_pinta_su_fase_hasta_la_siguiente_y_la_ultima_hasta_hoy(): void
    {
        $this->observar('09', '2026-04-01');
        $this->observar('15', '2026-05-01');

        $bandas = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['bandas'];

        $this->assertSame(['brotacion', 'hojas'], array_column($bandas, 'fase'));
        $this->assertEqualsWithDelta($this->pct('2026-04-01'), $bandas[0]['izquierda'], 0.001);
        $this->assertEqualsWithDelta($this->pct('2026-05-01') - $this->pct('2026-04-01'), $bandas[0]['ancho'], 0.001);
        // La última llega hasta el final del día de hoy
        $this->assertEqualsWithDelta($this->pct('2026-06-02'), $bandas[1]['izquierda'] + $bandas[1]['ancho'], 0.001);
    }

    public function test_en_una_campana_pasada_la_ultima_fase_llega_a_fin_de_año(): void
    {
        $this->observar('85', '2025-08-20');

        $bandas = $this->construir(2025, '2026-06-01')['filas'][$this->parcela->id]['bandas'];

        $this->assertSame('maduracion', $bandas[0]['fase']);
        $this->assertEqualsWithDelta(100.0, $bandas[0]['izquierda'] + $bandas[0]['ancho'], 0.001);
    }

    public function test_la_campana_empieza_con_el_ultimo_estado_del_año_anterior(): void
    {
        $this->observar('93', '2025-11-10');
        $this->observar('05', '2026-03-25');

        $bandas = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['bandas'];

        $this->assertSame(['senescencia', 'brotacion'], array_column($bandas, 'fase'));
        $this->assertSame(0.0, $bandas[0]['izquierda']);
        $this->assertTrue($bandas[0]['arrastrada']);
        $this->assertStringContainsString('campaña anterior', $bandas[0]['titulo']);
        $this->assertFalse($bandas[1]['arrastrada']);
    }

    public function test_dos_observaciones_el_mismo_dia_manda_la_ultima(): void
    {
        $this->observar('09', '2026-04-01');
        $this->observar('11', '2026-04-01');

        $bandas = $this->construir(2026, '2026-04-10')['filas'][$this->parcela->id]['bandas'];

        $this->assertCount(1, $bandas);
        $this->assertSame('hojas', $bandas[0]['fase']);
    }

    public function test_observaciones_de_años_posteriores_no_aparecen(): void
    {
        $this->observar('65', '2026-06-01');

        $this->assertSame([], $this->construir(2025, '2026-06-10')['filas'][$this->parcela->id]['bandas']);
    }

    public function test_fases_bbch(): void
    {
        $this->assertSame('reposo', CalendarioCampana::fase(0));
        $this->assertSame('brotacion', CalendarioCampana::fase(9));
        $this->assertSame('hojas', CalendarioCampana::fase(19));
        $this->assertSame('inflorescencia', CalendarioCampana::fase(57));
        $this->assertSame('floracion', CalendarioCampana::fase(65));
        $this->assertSame('fruto', CalendarioCampana::fase(77));
        $this->assertSame('maduracion', CalendarioCampana::fase(89));
        $this->assertSame('senescencia', CalendarioCampana::fase(97));
    }

    // ── marcas ───────────────────────────────────────────────────────────────

    public function test_marca_tratamientos_y_heladas_del_año(): void
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Azufre 80']);
        foreach (['2026-05-10', '2025-05-10'] as $fecha) {
            Tratamiento::create([
                'parcela_id' => $this->parcela->id, 'producto_id' => $producto->id, 'user_id' => $this->user->id,
                'fecha' => $fecha, 'dosis_l_ha' => 1,
            ]);
        }
        foreach (['helada' => '2026-04-12', 'prevision_helada' => '2026-06-05'] as $tipo => $fecha) {
            Alerta::create([
                'parcela_id' => $this->parcela->id, 'user_id' => $this->user->id, 'tipo' => $tipo, 'nivel' => 'warning',
                'mensaje' => 'x', 'clave' => "{$tipo}:{$this->parcela->id}:{$fecha}",
            ]);
        }

        $fila = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id];

        $this->assertCount(1, $fila['tratamientos']);
        $this->assertSame('10/05 · Azufre 80', $fila['tratamientos'][0]['titulo']);
        $this->assertEqualsWithDelta($this->pct('2026-05-10'), $fila['tratamientos'][0]['pos'], 0.001);

        $heladas = collect($fila['heladas'])->sortBy('pos')->values();
        $this->assertSame([false, true], $heladas->pluck('prevista')->all());
        $this->assertSame('Helada el 12/04/2026', $heladas[0]['titulo']);
    }

    // ── referencia por variedad ──────────────────────────────────────────────

    private function conVariedad(string $nombre): void
    {
        // La migración siembra las variedades con su precocidad
        $this->parcela->update(['variedad_id' => Variedad::where('nombre', $nombre)->value('id')]);
    }

    private function inicioReferencia(array $referencia, string $fase): float
    {
        return collect($referencia['bandas'])->firstWhere('fase', $fase)['izquierda'];
    }

    public function test_sin_variedad_usa_la_referencia_generica(): void
    {
        $ref = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['referencia'];

        $this->assertTrue($ref['generica']);
        $this->assertSame('Vendimia habitual: del 05/09 al 30/09', $ref['vendimia']['titulo']);
        $this->assertEqualsWithDelta($this->pct('2026-08-01'), $this->inicioReferencia($ref, 'maduracion'), 0.001);
    }

    public function test_una_variedad_temprana_adelanta_la_maduracion_y_la_vendimia(): void
    {
        $this->conVariedad('Tempranillo');

        $ref = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['referencia'];

        $this->assertFalse($ref['generica']);
        $this->assertSame('Temprana', $ref['precocidad']);
        $this->assertSame('Vendimia habitual: del 26/08 al 20/09', $ref['vendimia']['titulo']);
        $this->assertEqualsWithDelta($this->pct('2026-07-22'), $this->inicioReferencia($ref, 'maduracion'), 0.001);
        // La brotación se mueve la mitad
        $this->assertEqualsWithDelta($this->pct('2026-03-27'), $this->inicioReferencia($ref, 'brotacion'), 0.001);
    }

    public function test_una_variedad_muy_tardia_retrasa_la_vendimia(): void
    {
        $this->conVariedad('Monastrell');

        $ref = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['referencia'];

        $this->assertSame('Vendimia habitual: del 25/09 al 20/10', $ref['vendimia']['titulo']);
    }

    public function test_compara_la_ultima_observacion_con_la_referencia(): void
    {
        $this->conVariedad('Tempranillo'); // racimos del 05/05, floración del 22/05

        $casos = ['65' => 'adelantada', '57' => 'en_fecha', '15' => 'retrasada'];
        foreach ($casos as $bbch => $esperado) {
            RegistroFenologico::query()->delete();
            $this->observar($bbch, '2026-05-20');

            $comparacion = $this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['referencia']['comparacion'];
            $this->assertSame($esperado, $comparacion['estado'], "BBCH {$bbch}");
        }
    }

    public function test_reposo_en_diciembre_esta_en_fecha(): void
    {
        $this->observar('97', '2026-11-05');
        $this->observar('00', '2026-12-15');

        $comparacion = $this->construir(2026, '2026-12-20')['filas'][$this->parcela->id]['referencia']['comparacion'];

        $this->assertSame('en_fecha', $comparacion['estado']);
    }

    public function test_sin_observaciones_no_compara(): void
    {
        $this->assertNull($this->construir(2026, '2026-06-01')['filas'][$this->parcela->id]['referencia']['comparacion']);
    }

    // ── página ───────────────────────────────────────────────────────────────

    public function test_la_pagina_indica_la_referencia_y_pide_la_variedad_si_falta(): void
    {
        Carbon::setTestNow('2026-06-01 10:00');

        $this->actingAs($this->user)->get(route('fenologia.index'))
            ->assertSee('Ref. genérica · asigna la variedad')
            ->assertSee(route('vinedo.parcelas.edit', $this->parcela))
            ->assertSee('Vendimia habitual: del 05/09 al 30/09');

        $this->conVariedad('Airén'); // tardía: racimos desde el 15/05, floración desde el 11/06
        $this->observar('61', '2026-05-15');

        $this->actingAs($this->user)->get(route('fenologia.index'))
            ->assertSee('Ref. Airén · maduración tardía')
            ->assertSee('Adelantada');
    }

    public function test_la_pagina_muestra_la_campana_con_sus_fases(): void
    {
        Carbon::setTestNow('2026-06-01 10:00');
        $this->observar('09', '2026-04-01');

        $this->actingAs($this->user)->get(route('fenologia.index'))
            ->assertOk()
            ->assertSee('Campaña 2026')
            ->assertSee('Brotación · BBCH 09 Estado 09 · observado el 01/04/2026')
            ->assertSee('hoy')
            ->assertDontSee('2027 ▶');
    }

    public function test_navega_a_campanas_anteriores_con_observaciones(): void
    {
        Carbon::setTestNow('2026-06-01 10:00');
        $this->observar('85', '2025-08-20');

        $this->actingAs($this->user)->get(route('fenologia.index'))
            ->assertSee('◀ 2025');

        $this->actingAs($this->user)->get(route('fenologia.index', ['anio' => 2025]))
            ->assertSee('Campaña 2025')
            ->assertSee('2026 ▶')
            ->assertSee('Maduración');
    }

    public function test_el_año_pedido_se_limita_al_rango_con_datos(): void
    {
        Carbon::setTestNow('2026-06-01 10:00');

        $this->actingAs($this->user)->get(route('fenologia.index', ['anio' => 1990]))->assertSee('Campaña 2026');
        $this->actingAs($this->user)->get(route('fenologia.index', ['anio' => 2040]))->assertSee('Campaña 2026');
    }

    public function test_no_muestra_parcelas_de_otros_usuarios(): void
    {
        Carbon::setTestNow('2026-06-01 10:00');
        $this->observar('65', '2026-05-20');

        $this->actingAs(User::factory()->create())->get(route('fenologia.index'))
            ->assertOk()
            ->assertDontSee('Floración · BBCH 65');
    }
}
