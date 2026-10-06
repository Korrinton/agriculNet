<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Meteorologia\Models\PrediccionMeteorologica;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Services\PanelInicio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelInicioTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $vina;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-04 10:00');

        $this->user = User::factory()->create(['name' => 'Ramón García']);
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'Los Llanos']);
        $this->vina = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2,
            'agregado' => 0, 'poligono' => 70, 'parcela_sigpac' => 126, 'recinto' => 1,
        ]);
    }

    private function prevision(array $minimas): void
    {
        foreach ($minimas as $i => $min) {
            PrediccionMeteorologica::create([
                'provincia_cod' => 45, 'municipio_cod' => 54, 'fecha' => today()->addDays($i),
                'temp_max' => $min + 12, 'temp_min' => $min, 'prob_precipitacion' => $i === 1 ? 80 : 0,
                'estado_cielo' => $i === 1 ? 'Nuboso con lluvia' : 'Despejado',
            ]);
        }
    }

    public function test_muestra_la_prevision_y_avisa_de_las_heladas(): void
    {
        $this->prevision([8, 5, 0.5, -2, 4, 6, 7]);

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ramón')
            ->assertSee('Riesgo de helada: mar 6 y mié 7')
            ->assertSee('Previsión AEMET para Los Llanos (45054)');
    }

    public function test_sin_heladas_avisa_de_la_lluvia(): void
    {
        $this->prevision([8, 9, 10, 11, 12, 10, 9]);

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertSee('Lluvia probable: lun 5')
            ->assertDontSee('Riesgo de helada');
    }

    public function test_reune_alertas_y_plazos_de_seguridad_en_curso(): void
    {
        Alerta::create(['parcela_id' => $this->vina->id, 'user_id' => $this->user->id, 'tipo' => 'mildiu', 'nivel' => 'warning', 'mensaje' => 'Condiciones de mildiu en Viña', 'leida' => false]);
        $producto = ProductoFitosanitario::create(['nombre' => 'AZUFRE', 'vigente' => true]);
        Tratamiento::create([
            'parcela_id' => $this->vina->id, 'producto_id' => $producto->id, 'user_id' => $this->user->id,
            'fecha' => '2026-10-01', 'dosis_l_ha' => 2, 'plazo_seguridad_dias' => 7,
        ]);

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertSee('Condiciones de mildiu en Viña')
            ->assertSee('No cosechar Pol. 70 · Par. 126 · Rec. 1')
            ->assertSee('hasta el 08/10')
            ->assertSee('quedan 4 días')
            ->assertSee('AZUFRE');
    }

    public function test_sin_pendientes_dice_que_todo_esta_en_orden(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertSee('Todo en orden')
            ->assertSee('Todavía no hay previsión para Los Llanos');
    }

    public function test_sin_fincas_invita_a_crear_la_primera(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Da de alta tu primera finca')
            ->assertSee(route('vinedo.fincas.create'), false);
    }

    public function test_no_muestra_fincas_ni_alertas_de_otros_usuarios(): void
    {
        $otro = User::factory()->create();
        $ajena = Finca::create(['user_id' => $otro->id, 'provincia_cod' => 28, 'municipio_cod' => 79, 'paraje' => 'finca-ajena']);
        $parcelaAjena = Parcela::create(['finca_id' => $ajena->id, 'nombre' => 'Ajena', 'uso' => 'Secano', 'superficie_ha' => 1, 'agregado' => 0]);
        Alerta::create(['parcela_id' => $parcelaAjena->id, 'user_id' => $otro->id, 'tipo' => 'x', 'nivel' => 'critical', 'mensaje' => 'Alerta ajena', 'leida' => false]);

        // Pedir la finca ajena por parámetro muestra la propia
        $this->actingAs($this->user)->get(route('dashboard', ['finca' => $ajena->id]))
            ->assertOk()->assertSee('Los Llanos')->assertDontSee('finca-ajena')->assertDontSee('Alerta ajena');
    }

    public function test_icono_segun_el_estado_del_cielo(): void
    {
        $this->assertSame('tormenta', PanelInicio::iconoCielo('Muy nuboso con tormenta'));
        $this->assertSame('lluvia', PanelInicio::iconoCielo('Intervalos nubosos con lluvia'));
        $this->assertSame('sol-nubes', PanelInicio::iconoCielo('Poco nuboso'));
        $this->assertSame('sol', PanelInicio::iconoCielo('Despejado'));
        $this->assertSame('nubes', PanelInicio::iconoCielo('Cubierto'));
        $this->assertSame('desconocido', PanelInicio::iconoCielo(null));
    }
}
