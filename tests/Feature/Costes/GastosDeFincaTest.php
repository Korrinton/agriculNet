<?php

namespace Tests\Feature\Costes;

use App\Models\User;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Costes\Models\Coste;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GastosDeFincaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $vina;
    private Parcela $olivar;
    private CategoriaCoste $seguro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Carbon::setTestNow('2026-10-04 10:00');

        $this->user = User::factory()->create();
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'pancierto']);
        $this->vina = Parcela::create(['finca_id' => $this->finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2, 'agregado' => 0]);
        $this->olivar = Parcela::create(['finca_id' => $this->finca->id, 'nombre' => 'Olivar', 'uso' => 'Olivar', 'superficie_ha' => 3, 'agregado' => 0]);
        $this->seguro = CategoriaCoste::create(['nombre' => 'Seguros agrarios', 'tipo' => 'otros']);
    }

    private function datos(array $extra = []): array
    {
        return $extra + ['categoria_id' => $this->seguro->id, 'fecha' => '2026-09-01', 'importe' => 600, 'descripcion' => 'Seguro de la finca'];
    }

    public function test_un_gasto_general_se_anota_una_sola_vez_para_la_finca(): void
    {
        $this->actingAs($this->user)->post(route('costes.finca.store', $this->finca), $this->datos())
            ->assertRedirect(route('vinedo.fincas.show', $this->finca))
            ->assertSessionHas('success', 'Gasto de la finca registrado.');

        $coste = Coste::sole();
        $this->assertSame($this->finca->id, $coste->finca_id);
        $this->assertNull($coste->parcela_id);
        $this->assertTrue($coste->esDeFinca());
        $this->assertSame('600.00', $coste->importe);
    }

    public function test_desde_la_finca_tambien_se_puede_imputar_a_una_parcela(): void
    {
        $this->actingAs($this->user)->post(route('costes.finca.store', $this->finca), $this->datos(['parcela_id' => $this->olivar->id]))
            ->assertRedirect(route('vinedo.parcelas.show', $this->olivar));

        $this->assertSame($this->olivar->id, Coste::sole()->parcela_id);
        $this->assertSame($this->finca->id, Coste::sole()->finca_id);
    }

    public function test_el_gasto_de_parcela_lleva_la_finca_de_la_parcela(): void
    {
        // Ruta antigua desde la parcela y creación directa (como el coste de un tratamiento)
        $this->actingAs($this->user)->post(route('costes.store', $this->vina), $this->datos());
        Coste::create(['parcela_id' => $this->olivar->id, 'categoria_id' => $this->seguro->id, 'user_id' => $this->user->id, 'fecha' => '2026-09-02', 'importe' => 10]);

        $this->assertSame([$this->finca->id, $this->finca->id], Coste::orderBy('id')->pluck('finca_id')->all());
        $this->assertSame([$this->vina->id, $this->olivar->id], Coste::orderBy('id')->pluck('parcela_id')->all());
    }

    public function test_el_formulario_de_la_finca_ofrece_toda_la_finca_o_una_parcela(): void
    {
        $this->actingAs($this->user)->get(route('costes.finca.create', $this->finca))
            ->assertOk()
            ->assertSeeInOrder(['Toda la finca (gasto general)', 'Viña', 'Olivar']);

        // Desde la ficha de la parcela llega con ella elegida
        $this->actingAs($this->user)->get(route('costes.create', $this->olivar))
            ->assertOk()
            ->assertSee('value="' . $this->olivar->id . '" data-ha="3.0000" selected', false);
    }

    public function test_no_se_imputa_a_una_parcela_de_otra_finca(): void
    {
        $otra = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $ajena = Parcela::create(['finca_id' => $otra->id, 'nombre' => 'Otra', 'uso' => 'Secano', 'superficie_ha' => 1, 'agregado' => 0]);

        $this->actingAs($this->user)->post(route('costes.finca.store', $this->finca), $this->datos(['parcela_id' => $ajena->id]))
            ->assertSessionHasErrors(['parcela_id' => 'Esa parcela no es de esta finca.']);
        $this->assertSame(0, Coste::count());
    }

    public function test_otro_usuario_no_anota_ni_borra_gastos_de_la_finca(): void
    {
        $otro = User::factory()->create();
        $coste = $this->finca->costes()->create($this->datos(['user_id' => $this->user->id]));

        $this->actingAs($otro)->get(route('costes.finca.create', $this->finca))->assertForbidden();
        $this->actingAs($otro)->post(route('costes.finca.store', $this->finca), $this->datos())->assertForbidden();
        $this->actingAs($otro)->delete(route('costes.destroy', $coste))->assertForbidden();
        $this->assertSame(1, Coste::count());
        $this->actingAs($otro)->get(route('costes.index'))->assertDontSee('Seguro de la finca');
    }

    public function test_la_lista_de_costes_muestra_los_gastos_generales_y_filtra_por_finca(): void
    {
        $this->finca->costes()->create($this->datos(['user_id' => $this->user->id]));
        $this->vina->costes()->create($this->datos(['user_id' => $this->user->id, 'importe' => 150, 'descripcion' => 'Poda de la viña']));
        $otra = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'otra-finca']);
        $otra->costes()->create($this->datos(['user_id' => $this->user->id, 'descripcion' => 'Gasto de la otra']));

        $this->actingAs($this->user)->get(route('costes.index'))
            ->assertOk()
            ->assertSee('Toda la finca')->assertSee('Poda de la viña')->assertSee('Gasto de la otra')
            ->assertSee('1.200,00 € generales de finca');

        $this->actingAs($this->user)->get(route('costes.index', ['finca' => $this->finca->id]))
            ->assertSee('Seguro de la finca')->assertDontSee('Gasto de la otra');
    }

    public function test_la_ficha_de_la_finca_resume_los_gastos_del_año_y_se_pueden_borrar(): void
    {
        $general = $this->finca->costes()->create($this->datos(['user_id' => $this->user->id]));
        $this->vina->costes()->create($this->datos(['user_id' => $this->user->id, 'importe' => 150]));

        $this->actingAs($this->user)->get(route('vinedo.fincas.show', $this->finca))
            ->assertOk()
            ->assertSeeInOrder(['Generales de la finca', '600,00 €', 'De las parcelas', '150,00 €', 'Total', '750,00 €'])
            ->assertSee('Añadir gasto de la finca')
            ->assertSee(route('costes.finca.create', $this->finca), false);

        $this->actingAs($this->user)->from(route('vinedo.fincas.show', $this->finca))
            ->delete(route('costes.destroy', $general))
            ->assertRedirect(route('vinedo.fincas.show', $this->finca));
        $this->assertModelMissing($general);
    }
}
