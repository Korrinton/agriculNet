<?php

namespace Tests\Feature\Vinedo;

use App\Models\User;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** La API de fincas y parcelas escribe con las mismas reglas que la web. */
class ApiEscrituraTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_crear_finca_por_api(): void
    {
        $this->postJson('/api/fincas', [
            'provincia_cod' => 45,
            'municipio_cod' => 54,
            'paraje' => 'La Vega',
        ])->assertCreated()->assertJsonPath('provincia_cod', 45);

        $this->assertDatabaseHas('fincas', [
            'user_id' => $this->user->id,
            'provincia_cod' => 45,
            'municipio_cod' => 54,
            'paraje' => 'La Vega',
        ]);
    }

    public function test_crear_finca_exige_provincia_y_municipio(): void
    {
        // El payload antiguo (nombre, municipio, provincia…) ya no crea fincas vacías
        $this->postJson('/api/fincas', [
            'nombre' => 'Mi finca', 'municipio' => 'Toledo', 'provincia' => 'Toledo', 'superficie_ha' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors(['provincia_cod', 'municipio_cod']);

        $this->assertSame(0, Finca::count());
    }

    public function test_actualizar_finca_por_api(): void
    {
        $finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);

        $this->putJson("/api/fincas/{$finca->id}", [
            'provincia_cod' => 13,
            'municipio_cod' => 34,
            'titular_nif' => 'z-1234567 r',
        ])->assertOk()->assertJsonPath('provincia_cod', 13);

        $finca->refresh();
        $this->assertSame(13, $finca->provincia_cod);
        $this->assertSame('Z1234567R', $finca->titular_nif);
    }

    public function test_crear_parcela_por_api_exige_uso_y_variedad_coherente(): void
    {
        $finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $tempranillo = Variedad::create(['nombre' => 'Tempranillo', 'cultivo' => 'vid', 'tipo' => 'tinta']);
        $picual = Variedad::create(['nombre' => 'Picual', 'cultivo' => 'olivo']);

        // Sin uso no hay cultivo: la parcela quedaría fuera de alertas y fenología
        $this->postJson("/api/fincas/{$finca->id}/parcelas", [
            'superficie_ha' => 1.5,
        ])->assertUnprocessable()->assertJsonValidationErrors('uso');

        // La variedad tiene que ser del cultivo del uso
        $this->postJson("/api/fincas/{$finca->id}/parcelas", [
            'uso' => 'Viña en vaso', 'variedad_id' => $picual->id, 'superficie_ha' => 1.5,
        ])->assertUnprocessable()->assertJsonValidationErrors('variedad_id');

        // Con referencia SIGPAC y sin nombre, se nombra por la parcela
        $this->postJson("/api/fincas/{$finca->id}/parcelas", [
            'uso' => 'Viña en vaso', 'variedad_id' => $tempranillo->id, 'superficie_ha' => 1.5,
            'poligono' => 70, 'parcela_sigpac' => 126, 'recinto' => 1,
        ])->assertCreated()->assertJsonPath('nombre', 'Parcela 126')->assertJsonPath('uso', 'Viña en vaso');

        $this->assertDatabaseHas('parcelas', ['finca_id' => $finca->id, 'poligono' => 70, 'parcela_sigpac' => 126]);
    }

    public function test_actualizar_y_borrar_parcela_por_api(): void
    {
        $finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $parcela = Parcela::create([
            'finca_id' => $finca->id, 'nombre' => 'Vieja', 'uso' => 'Viña en vaso', 'superficie_ha' => 1, 'agregado' => 0,
        ]);

        $this->putJson("/api/fincas/{$finca->id}/parcelas/{$parcela->id}", [
            'nombre' => 'Renombrada', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2.5,
        ])->assertOk()->assertJsonPath('nombre', 'Renombrada');

        $this->assertSame('Viña en espaldera', $parcela->fresh()->uso);

        $this->deleteJson("/api/fincas/{$finca->id}/parcelas/{$parcela->id}")->assertNoContent();
        $this->assertSoftDeleted($parcela);
    }
}
