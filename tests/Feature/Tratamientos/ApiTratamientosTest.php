<?php

namespace Tests\Feature\Tratamientos;

use App\Models\User;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** La API de tratamientos aplica las mismas reglas que la web. */
class ApiTratamientosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Parcela $vina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $this->vina = Parcela::create([
            'finca_id' => $finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en vaso', 'superficie_ha' => 2, 'agregado' => 0,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_registrar_tratamiento_por_api(): void
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Azufre', 'unidad' => 'kg']);

        $this->postJson("/api/parcelas/{$this->vina->id}/tratamientos", [
            'producto_id' => $producto->id,
            'fecha' => now()->toDateString(),
            'dosis_l_ha' => 2,
            'eficacia' => array_key_first(Tratamiento::EFICACIAS),
        ])->assertCreated()->assertJsonPath('producto.nombre', 'Azufre');

        $this->assertDatabaseHas('tratamientos', ['parcela_id' => $this->vina->id, 'producto_id' => $producto->id]);
    }

    public function test_no_admite_fechas_futuras(): void
    {
        $producto = ProductoFitosanitario::create(['nombre' => 'Azufre']);

        $this->postJson("/api/parcelas/{$this->vina->id}/tratamientos", [
            'producto_id' => $producto->id,
            'fecha' => now()->addDay()->toDateString(),
            'dosis_l_ha' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('fecha');

        $this->assertSame(0, Tratamiento::count());
    }

    public function test_rechaza_productos_no_autorizados_para_el_cultivo(): void
    {
        // Producto del registro autorizado solo para olivo: no puede aplicarse en una viña
        $soloOlivo = ProductoFitosanitario::create([
            'nombre' => 'Olivocret', 'mapa_id' => 999, 'vigente' => true, 'cultivos' => ['olivo'],
        ]);

        $this->postJson("/api/parcelas/{$this->vina->id}/tratamientos", [
            'producto_id' => $soloOlivo->id,
            'fecha' => now()->toDateString(),
            'dosis_l_ha' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('parcelas');

        $this->assertSame(0, Tratamiento::count());
    }

    public function test_costes_y_fenologia_tampoco_admiten_fechas_futuras(): void
    {
        $categoria = CategoriaCoste::create(['nombre' => 'Poda', 'tipo' => 'mano_obra']);
        $this->postJson("/api/parcelas/{$this->vina->id}/costes", [
            'categoria_id' => $categoria->id, 'fecha' => now()->addDay()->toDateString(), 'importe' => 50,
        ])->assertUnprocessable()->assertJsonValidationErrors('fecha');

        $estado = EstadoFenologico::create(['codigo_bbch' => '09', 'nombre' => 'Desborre', 'orden' => 1]);
        $this->postJson("/api/parcelas/{$this->vina->id}/fenologia", [
            'estado_fenologico_id' => $estado->id, 'fecha_observacion' => now()->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('fecha_observacion');
    }
}
