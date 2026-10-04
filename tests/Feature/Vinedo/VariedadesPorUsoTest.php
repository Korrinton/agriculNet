<?php

namespace Tests\Feature\Vinedo;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariedadesPorUsoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->user = User::factory()->create();
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function variedad(string $nombre): int
    {
        return Variedad::where('nombre', $nombre)->value('id');
    }

    private function parcela(string $uso, array $extra = []): Parcela
    {
        return Parcela::create($extra + [
            'finca_id' => $this->finca->id, 'nombre' => 'Parcela 1', 'uso' => $uso,
            'superficie_ha' => 1, 'agregado' => 0,
        ]);
    }

    private function datosParcela(string $uso, ?string $variedad): array
    {
        return ['uso' => $uso, 'superficie_ha' => 1.5, 'variedad_id' => $variedad ? $this->variedad($variedad) : null];
    }

    // ── catálogo ─────────────────────────────────────────────────────────────

    public function test_hay_variedades_de_cada_cultivo(): void
    {
        $this->assertSame('vid', Variedad::where('nombre', 'Tempranillo')->value('cultivo'));
        $this->assertSame('olivo', Variedad::where('nombre', 'Cornicabra')->value('cultivo'));
        $this->assertSame('pistacho', Variedad::where('nombre', 'Kerman')->value('cultivo'));
        $this->assertSame('herbaceo', Variedad::where('nombre', 'Cebada')->value('cultivo'));
        $this->assertNull(Variedad::where('nombre', 'Cebada')->value('tipo'));
    }

    public function test_cada_uso_tiene_su_cultivo(): void
    {
        $this->assertSame('herbaceo', Parcela::cultivoDeUso('Secano'));
        $this->assertSame('vid', Parcela::cultivoDeUso('Viña en espaldera'));
        $this->assertSame('vid', Parcela::cultivoDeUso('Viña en vaso'));
        $this->assertSame('olivo', Parcela::cultivoDeUso('Olivar'));
        $this->assertSame('pistacho', Parcela::cultivoDeUso('Pistachos'));
    }

    // ── validación ───────────────────────────────────────────────────────────

    public function test_secano_no_admite_variedades_de_uva(): void
    {
        $this->actingAs($this->user)
            ->post(route('vinedo.parcelas.store', $this->finca), $this->datosParcela('Secano', 'Tempranillo'))
            ->assertSessionHasErrors(['variedad_id' => '«Tempranillo» no es un cultivo de secano: una parcela de secano solo admite cultivos herbáceos de secano.']);

        $this->assertSame(0, Parcela::count());
    }

    public function test_secano_admite_cultivos_herbaceos(): void
    {
        $this->actingAs($this->user)
            ->post(route('vinedo.parcelas.store', $this->finca), $this->datosParcela('Secano', 'Cebada'))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->variedad('Cebada'), Parcela::sole()->variedad_id);
    }

    public function test_olivar_solo_admite_variedades_de_olivo(): void
    {
        $parcela = $this->parcela('Olivar');

        $this->actingAs($this->user)
            ->put(route('vinedo.parcelas.update', $parcela), $this->datosParcela('Olivar', 'Kerman'))
            ->assertSessionHasErrors('variedad_id');

        $this->actingAs($this->user)
            ->put(route('vinedo.parcelas.update', $parcela), $this->datosParcela('Olivar', 'Cornicabra'))
            ->assertSessionHasNoErrors();
    }

    public function test_cambiar_el_uso_exige_una_variedad_compatible(): void
    {
        $parcela = $this->parcela('Viña en vaso', ['variedad_id' => $this->variedad('Airén')]);

        // Pasa a pistachos sin cambiar la variedad: no se admite
        $this->actingAs($this->user)
            ->put(route('vinedo.parcelas.update', $parcela), $this->datosParcela('Pistachos', 'Airén'))
            ->assertSessionHasErrors('variedad_id');

        $this->actingAs($this->user)
            ->put(route('vinedo.parcelas.update', $parcela), $this->datosParcela('Pistachos', 'Kerman'))
            ->assertSessionHasNoErrors();
    }

    public function test_sin_variedad_vale_para_cualquier_uso(): void
    {
        $this->actingAs($this->user)
            ->post(route('vinedo.parcelas.store', $this->finca), $this->datosParcela('Pistachos', null))
            ->assertSessionHasNoErrors();
    }

    public function test_el_uso_tiene_que_ser_uno_de_los_conocidos(): void
    {
        $this->actingAs($this->user)
            ->post(route('vinedo.parcelas.store', $this->finca), $this->datosParcela('Huerta', null))
            ->assertSessionHasErrors('uso');
    }

    public function test_al_crear_una_finca_cada_parcela_se_valida_con_su_uso(): void
    {
        $this->actingAs($this->user)->post(route('vinedo.fincas.store'), [
            'provincia_cod' => 45, 'municipio_cod' => 54,
            'parcelas' => [
                ['uso' => 'Viña en espaldera', 'superficie_ha' => 1, 'agregado' => 0, 'variedad_id' => $this->variedad('Tempranillo')],
                ['uso' => 'Secano', 'superficie_ha' => 2, 'agregado' => 0, 'variedad_id' => $this->variedad('Tempranillo')],
            ],
        ])
            ->assertSessionHasErrors('parcelas.1.variedad_id')
            ->assertSessionDoesntHaveErrors('parcelas.0.variedad_id');
    }

    // ── formulario ───────────────────────────────────────────────────────────

    public function test_el_formulario_lleva_el_filtro_por_uso(): void
    {
        $parcela = $this->parcela('Secano');

        $this->actingAs($this->user)->get(route('vinedo.parcelas.edit', $parcela))
            ->assertOk()
            ->assertSee('data-parcela-form', false)
            ->assertSee('data-uso', false)
            ->assertSee('data-variedad', false)
            ->assertSee('"Secano":"herbaceo"', false)
            ->assertSee('"cultivo":"olivo"', false);
    }

    public function test_editar_finca_lleva_a_editar_cada_parcela(): void
    {
        $parcela = $this->parcela('Secano', ['nombre' => 'La Hoya']);

        $this->actingAs($this->user)->get(route('vinedo.fincas.edit', $this->finca))
            ->assertOk()
            ->assertSee('La Hoya')
            ->assertSee('Secano')
            ->assertSee(route('vinedo.parcelas.edit', $parcela));
    }

    // ── solo viña ────────────────────────────────────────────────────────────

    public function test_no_se_anota_fenologia_de_vid_en_secano(): void
    {
        $parcela = $this->parcela('Secano');

        $this->actingAs($this->user)->get(route('fenologia.create', $parcela))
            ->assertRedirect(route('vinedo.parcelas.show', $parcela))
            ->assertSessionHas('error');
    }

    public function test_el_calendario_solo_muestra_viña(): void
    {
        $this->parcela('Viña en vaso', ['nombre' => 'La Viña Vieja']);
        $this->parcela('Olivar', ['nombre' => 'El Olivar Grande']);

        $this->actingAs($this->user)->get(route('fenologia.index'))
            ->assertOk()
            ->assertSee('La Viña Vieja')
            ->assertDontSee('El Olivar Grande')
            ->assertSee('1 parcela tuya de secano, olivar o pistacho');
    }

    public function test_las_alertas_de_mildiu_y_helada_son_solo_para_viña(): void
    {
        $estacion = EstacionMeteorologica::create(['nombre' => 'TOLEDO', 'latitud' => 39.88, 'longitud' => -4.05, 'fuente' => 'aemet', 'codigo_externo' => '3260B']);
        $this->finca->update(['estacion_meteorologica_id' => $estacion->id]);
        $vina = $this->parcela('Viña en espaldera');
        $this->parcela('Secano');
        $this->parcela('Pistachos');
        DatoMeteorologico::create(['estacion_id' => $estacion->id, 'fecha' => '2026-05-10', 'temp_min' => -1, 'temp_max' => 14, 'precipitacion_mm' => 0]);
        DatoMeteorologico::create(['estacion_id' => $estacion->id, 'fecha' => '2026-05-12', 'temp_min' => 12, 'temp_max' => 22, 'precipitacion_mm' => 15]);

        Carbon::setTestNow('2026-05-14 08:00');
        $this->artisan('alertas:generar')->assertSuccessful();

        $this->assertSame([$vina->id], Alerta::whereIn('tipo', ['helada', 'riesgo_mildiu'])->distinct()->pluck('parcela_id')->all());
    }
}
