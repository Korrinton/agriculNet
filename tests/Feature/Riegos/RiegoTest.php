<?php

namespace Tests\Feature\Riegos;

use App\Models\User;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class RiegoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $vina;
    private Parcela $secano;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Carbon::setTestNow('2026-10-04 10:00');

        $this->user = User::factory()->create();
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'pancierto']);
        $this->vina = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2,
            'agregado' => 0, 'poligono' => 70, 'parcela_sigpac' => 126, 'recinto' => 1,
        ]);
        $this->secano = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Cereal', 'uso' => 'Secano', 'superficie_ha' => 5, 'agregado' => 0,
        ]);
    }

    private function riego(array $extra = []): Riego
    {
        return Riego::create($extra + [
            'parcela_id' => $this->vina->id, 'user_id' => $this->user->id, 'fecha' => '2026-07-10',
            'volumen_m3' => 400, 'superficie_ha' => 2, 'sistema' => 'goteo', 'origen' => 'pozo',
        ]);
    }

    // ── cálculo ──────────────────────────────────────────────────────────────

    public function test_dosis_en_m3_por_hectarea_y_en_mm(): void
    {
        $riego = $this->riego(['volumen_m3' => 400, 'superficie_ha' => 2]);

        $this->assertSame(200.0, $riego->dosis_m3_ha);
        $this->assertSame(20.0, $riego->dosis_mm);
    }

    // ── anotar ───────────────────────────────────────────────────────────────

    public function test_anotar_riego_usa_la_superficie_de_la_parcela_por_defecto(): void
    {
        $this->actingAs($this->user)->post(route('riegos.store', $this->finca), [
            'parcela_id' => $this->vina->id, 'fecha' => '2026-07-10', 'volumen_m3' => 300, 'sistema' => 'goteo', 'duracion_horas' => 6,
        ])->assertRedirect(route('riegos.index', ['finca' => $this->finca->id, 'anio' => 2026]));

        $this->assertSame('2.0000', Riego::sole()->superficie_ha);
    }

    public function test_no_se_puede_regar_una_parcela_de_secano(): void
    {
        $this->actingAs($this->user)->post(route('riegos.store', $this->finca), [
            'parcela_id' => $this->secano->id, 'fecha' => '2026-07-10', 'volumen_m3' => 300, 'sistema' => 'goteo',
        ])->assertSessionHasErrors(['parcela_id' => 'Elige una parcela de regadío de esta finca (las de secano no se riegan).']);

        $this->assertSame(0, Riego::count());
    }

    public function test_el_formulario_solo_ofrece_parcelas_regables(): void
    {
        $this->actingAs($this->user)->get(route('riegos.create', $this->finca))
            ->assertOk()
            ->assertSee('Pol. 70 · Par. 126')
            ->assertDontSee('Cereal — Secano');
    }

    public function test_finca_toda_de_secano_no_ofrece_formulario(): void
    {
        $this->vina->update(['uso' => 'Secano']);

        $this->actingAs($this->user)->get(route('riegos.create', $this->finca))
            ->assertSee('No hay parcelas de regadío en esta finca');
    }

    public function test_no_se_puede_regar_una_parcela_de_otra_finca(): void
    {
        $otra = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 82]);
        $ajena = Parcela::create(['finca_id' => $otra->id, 'nombre' => 'X', 'uso' => 'Olivar', 'superficie_ha' => 1, 'agregado' => 0]);

        $this->actingAs($this->user)->post(route('riegos.store', $this->finca), [
            'parcela_id' => $ajena->id, 'fecha' => '2026-07-10', 'volumen_m3' => 300, 'sistema' => 'goteo',
        ])->assertSessionHasErrors('parcela_id');
    }

    public function test_otro_usuario_no_puede_anotar_ni_borrar(): void
    {
        $riego = $this->riego();
        $otro = User::factory()->create();

        $this->actingAs($otro)->get(route('riegos.create', $this->finca))->assertForbidden();
        $this->actingAs($otro)->post(route('riegos.store', $this->finca), [
            'parcela_id' => $this->vina->id, 'fecha' => '2026-07-10', 'volumen_m3' => 300, 'sistema' => 'goteo',
        ])->assertForbidden();
        $this->actingAs($otro)->delete(route('riegos.destroy', $riego))->assertForbidden();

        $this->assertModelExists($riego);
    }

    public function test_borrar_riego(): void
    {
        $riego = $this->riego();

        $this->actingAs($this->user)->delete(route('riegos.destroy', $riego))->assertRedirect();

        $this->assertModelMissing($riego);
    }

    // ── página ───────────────────────────────────────────────────────────────

    public function test_resumen_por_parcela_y_lluvia_de_la_campana(): void
    {
        $estacion = EstacionMeteorologica::create(['nombre' => 'QUINTANAR DE LA ORDEN', 'latitud' => 39.59, 'longitud' => -3.05, 'fuente' => 'aemet', 'codigo_externo' => '4061X']);
        $this->finca->update(['estacion_meteorologica_id' => $estacion->id]);
        DatoMeteorologico::create(['estacion_id' => $estacion->id, 'fecha' => '2026-04-10', 'precipitacion_mm' => 12.5]);
        DatoMeteorologico::create(['estacion_id' => $estacion->id, 'fecha' => '2025-04-10', 'precipitacion_mm' => 99]);

        $this->riego(['fecha' => '2026-07-10', 'volumen_m3' => 400]);
        $this->riego(['fecha' => '2026-08-01', 'volumen_m3' => 600]);
        $this->riego(['fecha' => '2025-08-01', 'volumen_m3' => 9999]);

        $this->actingAs($this->user)->get(route('riegos.index', ['finca' => $this->finca->id, 'anio' => 2026]))
            ->assertOk()
            ->assertSee('Lluvia en QUINTANAR DE LA ORDEN')
            ->assertSee('12,5 mm')
            ->assertSeeInOrder(['Pol. 70 · Par. 126', '2', '1.000', '500', '50,0', '01/08/2026']) // 2 riegos, 1.000 m³, 500 m³/ha, 50 mm
            ->assertSee('Secano: no se riega')
            ->assertDontSee('9.999')
            ->assertSee('Campaña 2025');
    }

    // ── cuaderno de explotación ──────────────────────────────────────────────

    public function test_los_riegos_aparecen_en_el_cuaderno_y_en_su_excel(): void
    {
        $this->riego(['observaciones' => 'Contador 12345']);

        $this->actingAs($this->user)->get(route('cuaderno.index', ['finca' => $this->finca->id, 'anio' => 2026]))
            ->assertSee('5. Riego')
            ->assertSee('Goteo')
            ->assertSee('Pozo propio');

        $this->actingAs($this->user)->get(route('cuaderno.imprimir', [$this->finca, 2026]))
            ->assertSee('5. Riego');

        $contenido = $this->actingAs($this->user)->get(route('cuaderno.excel', [$this->finca, 2026]))->getContent();
        $fichero = tempnam(sys_get_temp_dir(), 'cue') . '.xlsx';
        file_put_contents($fichero, $contenido);
        $hoja = IOFactory::load($fichero)->getSheetByName('Riego');
        unlink($fichero);

        $this->assertSame('45:54:0:0:70:126:1', $hoja->getCell('B2')->getValue());
        $this->assertEquals(200, $hoja->getCell('F2')->getValue());
        $this->assertSame('Contador 12345', $hoja->getCell('J2')->getValue());
    }
}
