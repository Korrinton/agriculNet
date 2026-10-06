<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Costes\Models\Coste;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Por la API nadie puede ver ni escribir en parcelas, fincas o estaciones de otro usuario. */
class ApiAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private User $yo;
    private Parcela $ajena;
    private Finca $fincaAjena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yo = User::factory()->create();
        $otro = User::factory()->create();
        $this->fincaAjena = Finca::create(['user_id' => $otro->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $this->ajena = Parcela::create([
            'finca_id' => $this->fincaAjena->id, 'nombre' => 'Ajena', 'uso' => 'Viña en vaso', 'superficie_ha' => 1, 'agregado' => 0,
        ]);

        Sanctum::actingAs($this->yo);
    }

    public function test_tratamientos_de_parcelas_ajenas(): void
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Azufre']);
        $t = Tratamiento::create([
            'parcela_id' => $this->ajena->id, 'producto_id' => $producto->id, 'user_id' => $this->fincaAjena->user_id,
            'fecha' => '2026-06-01', 'dosis_l_ha' => 2,
        ]);

        $this->getJson("/api/parcelas/{$this->ajena->id}/tratamientos")->assertForbidden();
        $this->getJson("/api/parcelas/{$this->ajena->id}/tratamientos/{$t->id}")->assertForbidden();
        $this->postJson("/api/parcelas/{$this->ajena->id}/tratamientos", [
            'producto_id' => $producto->id, 'fecha' => '2026-06-02', 'dosis_l_ha' => 2,
        ])->assertForbidden();
        $this->assertSame(1, Tratamiento::count());
    }

    public function test_costes_de_parcelas_ajenas(): void
    {
        $categoria = CategoriaCoste::create(['nombre' => 'Poda', 'tipo' => 'mano_obra']);

        $this->getJson("/api/parcelas/{$this->ajena->id}/costes")->assertForbidden();
        $this->getJson("/api/parcelas/{$this->ajena->id}/costes/resumen")->assertForbidden();
        $this->postJson("/api/parcelas/{$this->ajena->id}/costes", [
            'categoria_id' => $categoria->id, 'fecha' => '2026-06-01', 'importe' => 100,
        ])->assertForbidden();
        $this->assertSame(0, Coste::count());
    }

    public function test_fenologia_de_parcelas_ajenas(): void
    {
        $estado = EstadoFenologico::create(['codigo_bbch' => '09', 'nombre' => 'Desborre', 'orden' => 1]);
        $r = RegistroFenologico::create([
            'parcela_id' => $this->ajena->id, 'estado_fenologico_id' => $estado->id,
            'user_id' => $this->fincaAjena->user_id, 'fecha_observacion' => '2026-04-01',
        ]);

        $this->getJson("/api/parcelas/{$this->ajena->id}/fenologia")->assertForbidden();
        $this->getJson("/api/parcelas/{$this->ajena->id}/fenologia/{$r->id}")->assertForbidden();
        $this->postJson("/api/parcelas/{$this->ajena->id}/fenologia", [
            'estado_fenologico_id' => $estado->id, 'fecha_observacion' => '2026-04-02',
        ])->assertForbidden();
        $this->assertSame(1, RegistroFenologico::count());
    }

    public function test_fenologia_solo_en_parcelas_de_vina(): void
    {
        $finca = Finca::create(['user_id' => $this->yo->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $secano = Parcela::create(['finca_id' => $finca->id, 'nombre' => 'Cereal', 'uso' => 'Secano', 'superficie_ha' => 5, 'agregado' => 0]);
        $estado = EstadoFenologico::create(['codigo_bbch' => '09', 'nombre' => 'Desborre', 'orden' => 1]);

        $this->postJson("/api/parcelas/{$secano->id}/fenologia", [
            'estado_fenologico_id' => $estado->id, 'fecha_observacion' => '2026-04-02',
        ])->assertUnprocessable()->assertJsonValidationErrors('parcela');
    }

    public function test_una_parcela_ajena_no_se_alcanza_a_traves_de_una_finca_propia(): void
    {
        $mia = Finca::create(['user_id' => $this->yo->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);

        $this->getJson("/api/fincas/{$mia->id}/parcelas/{$this->ajena->id}")->assertNotFound();
        $this->putJson("/api/fincas/{$mia->id}/parcelas/{$this->ajena->id}", ['nombre' => 'Mía'])->assertNotFound();
        $this->deleteJson("/api/fincas/{$mia->id}/parcelas/{$this->ajena->id}")->assertNotFound();
        $this->assertModelExists($this->ajena);
        $this->assertSame('Ajena', $this->ajena->fresh()->nombre);
    }

    public function test_estaciones_aemet_compartidas_y_manuales_solo_de_sus_fincas(): void
    {
        $aemet = EstacionMeteorologica::create(['nombre' => 'TOLEDO', 'latitud' => 39.8, 'longitud' => -4, 'fuente' => 'aemet', 'codigo_externo' => '3260B']);
        $manualAjena = EstacionMeteorologica::create(['nombre' => 'Mi caseta', 'latitud' => 39.8, 'longitud' => -4, 'fuente' => 'manual']);
        $this->fincaAjena->update(['estacion_meteorologica_id' => $manualAjena->id]);

        $this->getJson('/api/estaciones-meteorologicas')
            ->assertOk()->assertSee('TOLEDO')->assertDontSee('Mi caseta');
        $this->getJson("/api/estaciones-meteorologicas/{$aemet->id}")->assertOk()->assertJsonPath('nombre', 'TOLEDO');
        $this->getJson("/api/estaciones-meteorologicas/{$manualAjena->id}")->assertNotFound();

        // Ya no se pueden crear estaciones (las AEMET las da de alta el importador)
        $this->postJson('/api/estaciones-meteorologicas', [
            'nombre' => 'Falsa', 'latitud' => 40, 'longitud' => -3, 'fuente' => 'aemet',
        ])->assertStatus(405);
    }
}
