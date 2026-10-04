<?php

namespace Tests\Feature\CuadernoDigital;

use App\Models\User;
use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CuadernoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $parcela;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Carbon::setTestNow('2026-10-04 10:00');

        $this->user = User::factory()->create();
        $this->finca = Finca::create([
            'user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'pancierto',
        ]);
        $this->parcela = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Parcela 126', 'uso' => 'Viña en espaldera',
            'superficie_ha' => 1.9, 'agregado' => 0, 'poligono' => 70, 'parcela_sigpac' => 126, 'recinto' => 1,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function tratamiento(array $extra = []): Tratamiento
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Azufre 80', 'numero_registro' => 'ES-00123']);

        return Tratamiento::create($extra + [
            'parcela_id' => $this->parcela->id, 'producto_id' => $producto->id, 'user_id' => $this->user->id,
            'fecha' => '2026-05-10', 'dosis_l_ha' => 2, 'motivo' => 'Oídio',
        ]);
    }

    private function fertilizacion(array $extra = []): Fertilizacion
    {
        return Fertilizacion::create($extra + [
            'parcela_id' => $this->parcela->id, 'user_id' => $this->user->id, 'fecha' => '2026-03-01',
            'tipo' => 'mineral', 'producto' => 'Complejo 8-15-15', 'riqueza_n' => 8, 'riqueza_p' => 15, 'riqueza_k' => 15,
            'dosis' => 300, 'unidad' => 'kg/ha', 'superficie_ha' => 1.9,
        ]);
    }

    private function cosecha(array $extra = []): Cosecha
    {
        return Cosecha::create($extra + [
            'parcela_id' => $this->parcela->id, 'user_id' => $this->user->id, 'fecha' => '2026-09-15',
            'producto' => 'Uva', 'cantidad_kg' => 9500, 'destino' => 'Cooperativa San Isidro', 'albaran' => 'A-77',
        ]);
    }

    // ── página del cuaderno ──────────────────────────────────────────────────

    public function test_sin_fincas_invita_a_crear_una(): void
    {
        $this->actingAs(User::factory()->create())->get(route('cuaderno.index'))
            ->assertOk()->assertSee('Sin fincas registradas');
    }

    public function test_muestra_las_cuatro_secciones_con_los_registros_de_la_campana(): void
    {
        $this->tratamiento();
        $this->fertilizacion();
        $this->cosecha();
        $this->cosecha(['fecha' => '2025-09-20', 'albaran' => 'VIEJO-1']);

        $this->actingAs($this->user)->get(route('cuaderno.index', ['finca' => $this->finca->id, 'anio' => 2026]))
            ->assertOk()
            ->assertSeeInOrder(['1. Datos generales', '2. Tratamientos', '3. Fertilización', '4. Cosecha'])
            ->assertSee('45:54:0:0:70:126:1')
            ->assertSee('Azufre 80')
            ->assertSee('8-15-15')
            ->assertSee('A-77')
            ->assertDontSee('VIEJO-1')
            ->assertSee('Campaña 2025'); // aparece en el selector porque tiene registros
    }

    public function test_avisa_de_lo_que_falta_para_cumplir_la_normativa(): void
    {
        $this->tratamiento();                                                       // sin ROPO
        $this->fertilizacion(['fecha' => '2026-08-01', 'created_at' => '2026-10-01']); // 61 días tarde

        $this->actingAs($this->user)->get(route('cuaderno.index'))
            ->assertSee('Faltan el nombre y el NIF del titular')
            ->assertSee('Falta el número de inscripción en el Registro de Explotaciones Agrícolas')
            ->assertSee('1 tratamiento no indica el nº ROPO')
            ->assertSee('1 fertilización se anotó más de un mes después');
    }

    public function test_con_todo_completo_no_hay_avisos(): void
    {
        $this->finca->update(['titular_nombre' => 'Ramón García', 'titular_nif' => '12345678z', 'rea_numero' => '45054000123']);
        $this->tratamiento(['aplicador_ropo' => '0145-B-1234']);
        $this->fertilizacion(['fecha' => '2026-09-20']);

        $this->actingAs($this->user)->get(route('cuaderno.index'))
            ->assertDontSee('Para que el cuaderno esté completo')
            ->assertSee('12345678Z');
    }

    public function test_no_se_ve_el_cuaderno_de_otro_usuario(): void
    {
        $otro = User::factory()->create();
        Finca::create(['user_id' => $otro->id, 'provincia_cod' => 28, 'municipio_cod' => 79, 'paraje' => 'finca-propia']);

        // Pedir la finca ajena por parámetro muestra la propia
        $this->actingAs($otro)->get(route('cuaderno.index', ['finca' => $this->finca->id]))
            ->assertOk()->assertSee('finca-propia')->assertDontSee('pancierto');

        $this->actingAs($otro)->get(route('cuaderno.imprimir', [$this->finca, 2026]))->assertForbidden();
        $this->actingAs($otro)->get(route('cuaderno.excel', [$this->finca, 2026]))->assertForbidden();
    }

    // ── fertilización ────────────────────────────────────────────────────────

    public function test_anotar_fertilizacion_usa_la_superficie_de_la_parcela_por_defecto(): void
    {
        $this->actingAs($this->user)->post(route('fertilizaciones.store', $this->finca), [
            'parcela_id' => $this->parcela->id, 'fecha' => '2026-03-01', 'tipo' => 'organico',
            'producto' => 'Estiércol de oveja', 'dosis' => 20, 'unidad' => 't/ha', 'metodo' => 'enterrado',
        ])->assertRedirect(route('cuaderno.index', ['finca' => $this->finca->id, 'anio' => 2026]));

        $f = Fertilizacion::sole();
        $this->assertSame('1.9000', $f->superficie_ha);
        $this->assertNull($f->npk);
    }

    public function test_no_se_puede_fertilizar_una_parcela_de_otra_finca(): void
    {
        $otraFinca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 82]);
        $ajena = Parcela::create(['finca_id' => $otraFinca->id, 'nombre' => 'X', 'uso' => 'Secano', 'superficie_ha' => 1, 'agregado' => 0]);

        $this->actingAs($this->user)->post(route('fertilizaciones.store', $this->finca), [
            'parcela_id' => $ajena->id, 'fecha' => '2026-03-01', 'tipo' => 'mineral', 'producto' => 'Urea', 'dosis' => 100, 'unidad' => 'kg/ha',
        ])->assertSessionHasErrors('parcela_id');
    }

    public function test_otro_usuario_no_puede_anotar_ni_borrar(): void
    {
        $otro = User::factory()->create();
        $f = $this->fertilizacion();

        $this->actingAs($otro)->get(route('fertilizaciones.create', $this->finca))->assertForbidden();
        $this->actingAs($otro)->delete(route('fertilizaciones.destroy', $f))->assertForbidden();
        $this->assertModelExists($f);
    }

    public function test_formato_npk(): void
    {
        $this->assertSame('8-15-15', $this->fertilizacion()->npk);
        $this->assertSame('46-0-0', $this->fertilizacion(['riqueza_n' => 46, 'riqueza_p' => null, 'riqueza_k' => null])->npk);
        $this->assertSame('7,5-0-0', $this->fertilizacion(['riqueza_n' => 7.5, 'riqueza_p' => 0, 'riqueza_k' => 0])->npk);
    }

    // ── cosecha ──────────────────────────────────────────────────────────────

    public function test_anotar_y_borrar_cosecha(): void
    {
        $this->actingAs($this->user)->post(route('cosechas.store', $this->finca), [
            'parcela_id' => $this->parcela->id, 'fecha' => '2026-09-15', 'producto' => 'Uva', 'cantidad_kg' => 9500,
        ])->assertSessionHasNoErrors();

        $cosecha = Cosecha::sole();
        $this->assertEqualsWithDelta(5000.0, $cosecha->rendimiento_kg_ha, 0.1); // 9.500 kg / 1,9 ha

        $this->actingAs($this->user)->delete(route('cosechas.destroy', $cosecha))->assertRedirect();
        $this->assertModelMissing($cosecha);
    }

    // ── tratamientos con los datos del cuaderno ──────────────────────────────

    public function test_el_tratamiento_guarda_aplicador_equipo_y_superficie(): void
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Cobre 50', 'numero_registro' => 'ES-1']);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->parcela), [
            'producto_id' => $producto->id, 'fecha' => '2026-05-10', 'dosis_l_ha' => 2,
            'superficie_tratada_ha' => 1.2, 'aplicador_nombre' => 'Ramón', 'aplicador_ropo' => '0145-B-1234',
            'equipo_roma' => 'ROMA-99', 'eficacia' => 'buena',
        ])->assertSessionHasNoErrors();

        $t = Tratamiento::sole();
        $this->assertSame(['1.2000', '0145-B-1234', 'ROMA-99', 'buena'], [$t->superficie_tratada_ha, $t->aplicador_ropo, $t->equipo_roma, $t->eficacia]);
    }

    public function test_la_superficie_tratada_no_puede_superar_la_de_la_parcela(): void
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Cobre 50']);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->parcela), [
            'producto_id' => $producto->id, 'fecha' => '2026-05-10', 'dosis_l_ha' => 2, 'superficie_tratada_ha' => 5,
        ])->assertSessionHasErrors('superficie_tratada_ha');
    }

    public function test_el_formulario_propone_el_ultimo_aplicador_de_la_finca(): void
    {
        $this->tratamiento(['aplicador_nombre' => 'Ramón', 'aplicador_ropo' => '0145-B-1234', 'equipo_roma' => 'ROMA-99']);

        $this->actingAs($this->user)->get(route('tratamientos.create', $this->parcela))
            ->assertSee('value="0145-B-1234"', false)
            ->assertSee('value="ROMA-99"', false);
    }

    // ── exportación ──────────────────────────────────────────────────────────

    public function test_exporta_a_excel_con_una_hoja_por_seccion(): void
    {
        $this->finca->update(['titular_nombre' => 'Ramón García', 'titular_nif' => '12345678Z']);
        $this->tratamiento(['aplicador_ropo' => '0145-B-1234']);
        $this->fertilizacion();
        $this->cosecha();

        $respuesta = $this->actingAs($this->user)->get(route('cuaderno.excel', [$this->finca, 2026]));

        $respuesta->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="cuaderno-explotacion-pancierto-2026.xlsx"');

        $fichero = tempnam(sys_get_temp_dir(), 'cue') . '.xlsx';
        file_put_contents($fichero, $respuesta->getContent());
        $libro = IOFactory::load($fichero);
        unlink($fichero);

        $this->assertSame(['Datos generales', 'Tratamientos', 'Fertilización', 'Cosecha', 'Riego'], $libro->getSheetNames());
        $this->assertSame('Ramón García', $libro->getSheetByName('Datos generales')->getCell('B3')->getValue());
        $tratamientos = $libro->getSheetByName('Tratamientos');
        $this->assertSame('45:54:0:0:70:126:1', $tratamientos->getCell('B2')->getValue());
        $this->assertSame('ES-00123', $tratamientos->getCell('H2')->getValue());
        $this->assertSame('0145-B-1234', $tratamientos->getCell('K2')->getValue());
        $this->assertSame('8-15-15', $libro->getSheetByName('Fertilización')->getCell('G2')->getValue());
        $this->assertEquals(9500, $libro->getSheetByName('Cosecha')->getCell('F2')->getValue());
    }

    public function test_excel_de_una_campana_vacia(): void
    {
        $respuesta = $this->actingAs($this->user)->get(route('cuaderno.excel', [$this->finca, 2026]))->assertOk();

        $fichero = tempnam(sys_get_temp_dir(), 'cue') . '.xlsx';
        file_put_contents($fichero, $respuesta->getContent());
        $libro = IOFactory::load($fichero);
        unlink($fichero);

        $this->assertSame('Sin registros en esta campaña', $libro->getSheetByName('Cosecha')->getCell('A2')->getValue());
    }

    public function test_version_imprimible(): void
    {
        $this->tratamiento();

        $this->actingAs($this->user)->get(route('cuaderno.imprimir', [$this->finca, 2026]))
            ->assertOk()
            ->assertSee('Cuaderno de explotación — campaña 2026')
            ->assertSee('Imprimir / Guardar como PDF')
            ->assertSee('ES-00123')
            ->assertSee('Firma del titular');
    }

    // ── API ──────────────────────────────────────────────────────────────────

    public function test_api_del_cuaderno_con_permisos(): void
    {
        $this->cosecha();

        $this->getJson("/api/fincas/{$this->finca->id}/cuaderno/2026")->assertUnauthorized();

        $ajeno = User::factory()->create()->createToken('t')->plainTextToken;
        $this->withToken($ajeno)->getJson("/api/fincas/{$this->finca->id}/cuaderno/2026")->assertForbidden();
        $this->app['auth']->forgetGuards();

        $propio = $this->user->createToken('t')->plainTextToken;
        $this->withToken($propio)->getJson("/api/fincas/{$this->finca->id}/cuaderno/2026")
            ->assertOk()
            ->assertJsonPath('cosechas.0.albaran', 'A-77')
            ->assertJsonStructure(['anio', 'finca', 'parcelas', 'tratamientos', 'fertilizaciones', 'cosechas', 'avisos']);
    }
}
