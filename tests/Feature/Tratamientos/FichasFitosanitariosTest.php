<?php

namespace Tests\Feature\Tratamientos;

use App\Models\User;
use App\Modules\Tratamientos\Models\PlazoSeguridadProducto;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Tratamientos\Services\LectorFichasFitosanitarios;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FichasFitosanitariosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $vina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Carbon::setTestNow('2026-10-04 10:00');

        $this->user = User::factory()->create();
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $this->vina = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2, 'agregado' => 0,
        ]);
    }

    private function producto(array $atributos = []): ProductoFitosanitario
    {
        return ProductoFitosanitario::create($atributos + ['nombre' => 'ACOIDAL WG', 'mapa_id' => 114534, 'cultivos' => ['vid'], 'vigente' => true]);
    }

    // ── lectura de la tabla de plazos ────────────────────────────────────────

    public function test_lee_la_tabla_de_plazos_de_seguridad(): void
    {
        $texto = "Composición\nUSO\tP.S. (días)\n"
            . "Ajo, Berenjena, Calabaza, Cebolla, Coliflor, Melón, Patata, \nRepollo, Tomate, Zanahoria\n3\n"
            . "Olivo, Vid\t4 HORAS\n"
            . "Cereales de invierno, Pistachero\t30\n"
            . "Frondosas, Ornamentales herbáceas\tNP\n"
            . "Plazos de Seguridad (Protección del Consumidor)\nEnvases";

        $filas = LectorFichasFitosanitarios::filasPlazos($texto);

        $this->assertCount(4, $filas);
        $this->assertSame(3, $filas[0]['dias']);
        $this->assertContains('melon', $filas[0]['cultivos']);
        $this->assertContains('zanahoria', $filas[0]['cultivos']);
        $this->assertSame(1, $filas[1]['dias']);           // 4 horas → 1 día
        $this->assertNull($filas[3]['dias']);              // NP

        $this->assertSame(
            ['vid' => 1, 'olivo' => 1, 'pistacho' => 30, 'trigo' => 30, 'cebada' => 30, 'avena' => 30, 'centeno' => 30, 'triticale' => 30],
            LectorFichasFitosanitarios::plazosPorCultivo($texto)
        );
    }

    public function test_el_cultivo_propio_prevalece_sobre_el_generico_y_se_toma_el_plazo_mas_largo(): void
    {
        $texto = "USO\tP.S. (días)\nCereales\t60\nCebada\t30\nCebada, Avena\t45\nPlazos de Seguridad";

        $plazos = LectorFichasFitosanitarios::plazosPorCultivo($texto);

        $this->assertSame(45, $plazos['cebada']);
        $this->assertSame(45, $plazos['avena']);
        $this->assertSame(60, $plazos['trigo']);
    }

    public function test_otros_formatos_de_plazo(): void
    {
        $texto = "USO\tP.S. (días)\n"
            . "Almendro, Olivo, Vid\nN.P.\n"
            . "Vid\t7 DÍAS UVAS DE MESA; 14 DÍAS UVAS DE VINIFICACIÓN\n"
            . "Ornamentales herbáceas\tNO PROCEDE\n"
            . "Plazos de Seguridad";

        $filas = LectorFichasFitosanitarios::filasPlazos($texto);
        $this->assertSame([null, 14, null], array_column($filas, 'dias'));
        $this->assertSame(['vid' => 14, 'olivo' => null], LectorFichasFitosanitarios::plazosPorCultivo($texto));

        // «Especies vegetales» vale para cualquier cultivo
        $this->assertSame(
            ['vid' => null, 'olivo' => null],
            array_intersect_key(LectorFichasFitosanitarios::plazosPorCultivo("USO\tP.S. (días)\nEspecies vegetales\tNO PROCEDE\nPlazos de Seguridad"), ['vid' => 1, 'olivo' => 1])
        );

        // «NA» (no aplica) solo en la última línea de una lista larga
        $this->assertSame(['vid' => null], array_intersect_key(
            LectorFichasFitosanitarios::plazosPorCultivo("USO\tP.S. (días)\nArándano, Especies vegetales,\nTabaco\nNA\nPlazos de Seguridad"),
            ['vid' => 1]
        ));
    }

    public function test_sin_tabla_de_plazos_no_hay_plazos(): void
    {
        $this->assertSame([], LectorFichasFitosanitarios::plazosPorCultivo('Composición AZUFRE 80% [WG] P/P'));
    }

    // ── descarga ─────────────────────────────────────────────────────────────

    public function test_lee_la_ficha_real_del_registro(): void
    {
        Http::fake(['servicio.mapa.gob.es/*' => Http::response(file_get_contents(base_path('tests/Fixtures/ficha_acoidal_wg.pdf')))]);
        $producto = $this->producto();

        $this->assertSame(1, app(LectorFichasFitosanitarios::class)->leer($producto));

        $this->assertSame(7, $producto->fresh()->plazoOficialPara('vid'));
        $this->assertNotNull($producto->fresh()->ficha_leida_at);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'idProducto=114534'));
    }

    public function test_el_comando_lee_las_fichas_pendientes_de_los_cultivos_de_las_parcelas(): void
    {
        Http::fake(['servicio.mapa.gob.es/*' => Http::response(file_get_contents(base_path('tests/Fixtures/ficha_acoidal_wg.pdf')))]);
        $pendiente = $this->producto();
        $reciente = $this->producto(['nombre' => 'LEIDO', 'mapa_id' => 2]);
        $reciente->forceFill(['ficha_leida_at' => now()->subDays(5)])->save();
        $olivo = $this->producto(['nombre' => 'SOLO OLIVO', 'mapa_id' => 3, 'cultivos' => ['olivo']]);   // no hay olivares

        $this->artisan('fitosanitarios:fichas')->assertSuccessful();

        $this->assertNotNull($pendiente->fresh()->ficha_leida_at);
        $this->assertSame(1, PlazoSeguridadProducto::count());
        Http::assertSentCount(1);
    }

    public function test_una_respuesta_que_no_es_pdf_no_marca_la_ficha_como_leida(): void
    {
        Http::fake(['servicio.mapa.gob.es/*' => Http::response('<html>Error</html>')]);
        $producto = $this->producto();

        $this->artisan('fitosanitarios:fichas')->assertFailed();
        $this->assertNull($producto->fresh()->ficha_leida_at);
    }

    // ── uso en los tratamientos ──────────────────────────────────────────────

    public function test_sin_plazo_anotado_se_usa_el_oficial_del_cultivo(): void
    {
        $producto = $this->producto();
        PlazoSeguridadProducto::create(['producto_id' => $producto->id, 'cultivo' => 'vid', 'dias' => 7]);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $producto->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2,
        ])->assertRedirect();

        $this->assertSame(7, Tratamiento::sole()->plazo_seguridad_dias);
    }

    public function test_el_plazo_anotado_prevalece_sobre_el_oficial(): void
    {
        $producto = $this->producto();
        PlazoSeguridadProducto::create(['producto_id' => $producto->id, 'cultivo' => 'vid', 'dias' => 7]);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $producto->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2, 'plazo_seguridad_dias' => 21,
        ]);

        $this->assertSame(21, Tratamiento::sole()->plazo_seguridad_dias);
    }

    public function test_el_formulario_y_el_catalogo_muestran_el_plazo_oficial(): void
    {
        $producto = $this->producto();
        PlazoSeguridadProducto::create(['producto_id' => $producto->id, 'cultivo' => 'vid', 'dias' => 7]);

        $this->actingAs($this->user)->get(route('tratamientos.create', $this->vina))
            ->assertSee('"plazos":{"vid":7}', false)
            ->assertSee('"cultivoParcela":"vid"', false);

        $this->actingAs($this->user)->get(route('tratamientos.productos.index'))
            ->assertSee('Plazo de seguridad oficial: 7 días');
    }
}
