<?php

namespace Tests\Feature\Alertas;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function parcelaDe(User $user): Parcela
    {
        $finca = Finca::create([
            'user_id'       => $user->id,
            'provincia_cod' => 45,
            'municipio_cod' => 54,
            'paraje'        => 'El Monte',
        ]);

        return Parcela::create([
            'finca_id'       => $finca->id,
            'nombre'         => 'Parcela 126',
            'uso'            => 'Viña en espaldera',
            'superficie_ha'  => 1.5,
            'agregado'       => 0,
            'poligono'       => 70,
            'parcela_sigpac' => 126,
        ]);
    }

    private function alerta(User $user, array $overrides = []): Alerta
    {
        return Alerta::create(array_merge([
            'parcela_id' => $this->parcelaDe($user)->id,
            'user_id'    => $user->id,
            'tipo'       => 'plazo_seguridad',
            'nivel'      => 'warning',
            'mensaje'    => 'Alerta de prueba',
            'leida'      => false,
        ], $overrides));
    }

    private function producto(array $overrides = []): ProductoFitosanitario
    {
        return ProductoFitosanitario::create(array_merge([
            'nombre'               => 'Azufre 80',
            'plazo_seguridad_dias' => 21,
            'dosis_max_l_ha'       => 2.5,
        ], $overrides));
    }

    // ── listado ──────────────────────────────────────────────────────────────

    public function test_listado_requiere_autenticacion(): void
    {
        $this->get(route('alertas.index'))->assertRedirectToRoute('login');
    }

    public function test_listado_muestra_solo_alertas_propias_sin_leer(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $this->alerta($user, ['mensaje' => 'Mía pendiente']);
        $this->alerta($user, ['mensaje' => 'Mía leída', 'leida' => true]);
        $this->alerta($other, ['mensaje' => 'Ajena']);

        $this->actingAs($user)->get(route('alertas.index'))
            ->assertOk()
            ->assertSee('Mía pendiente')
            ->assertDontSee('Mía leída')
            ->assertDontSee('Ajena');
    }

    public function test_filtro_todas_incluye_leidas(): void
    {
        $user = User::factory()->create();
        $this->alerta($user, ['mensaje' => 'Mía leída', 'leida' => true]);

        $this->actingAs($user)->get(route('alertas.index', ['estado' => 'todas']))
            ->assertOk()
            ->assertSee('Mía leída');
    }

    public function test_filtro_por_nivel(): void
    {
        $user = User::factory()->create();
        $this->alerta($user, ['mensaje' => 'Es crítica', 'nivel' => 'critical']);
        $this->alerta($user, ['mensaje' => 'Es informativa', 'nivel' => 'info']);

        $this->actingAs($user)->get(route('alertas.index', ['nivel' => 'critical']))
            ->assertOk()
            ->assertSee('Es crítica')
            ->assertDontSee('Es informativa');
    }

    // ── acciones ─────────────────────────────────────────────────────────────

    public function test_usuario_puede_marcar_alerta_como_leida(): void
    {
        $user   = User::factory()->create();
        $alerta = $this->alerta($user);

        $this->actingAs($user)->patch(route('alertas.leer', $alerta))->assertRedirect();

        $this->assertTrue($alerta->fresh()->leida);
    }

    public function test_usuario_no_puede_marcar_alerta_ajena(): void
    {
        $alerta = $this->alerta(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->patch(route('alertas.leer', $alerta))
            ->assertForbidden();

        $this->assertFalse($alerta->fresh()->leida);
    }

    public function test_marcar_todas_solo_afecta_al_usuario(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $this->alerta($user);
        $this->alerta($user);
        $ajena = $this->alerta($other);

        $this->actingAs($user)->post(route('alertas.leer-todas'))->assertRedirect();

        $this->assertSame(0, Alerta::where('user_id', $user->id)->where('leida', false)->count());
        $this->assertFalse($ajena->fresh()->leida);
    }

    public function test_usuario_puede_eliminar_alerta_propia(): void
    {
        $user   = User::factory()->create();
        $alerta = $this->alerta($user);

        $this->actingAs($user)->delete(route('alertas.destroy', $alerta))->assertRedirect();

        $this->assertModelMissing($alerta);
    }

    public function test_usuario_no_puede_eliminar_alerta_ajena(): void
    {
        $alerta = $this->alerta(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete(route('alertas.destroy', $alerta))
            ->assertForbidden();

        $this->assertModelExists($alerta);
    }

    // ── generación desde tratamientos ────────────────────────────────────────

    public function test_tratamiento_con_plazo_de_seguridad_genera_alerta(): void
    {
        $user     = User::factory()->create();
        $parcela  = $this->parcelaDe($user);
        $producto = $this->producto();

        $this->actingAs($user)->post(route('tratamientos.store', $parcela), [
            'producto_id' => $producto->id,
            'fecha'       => now()->toDateString(),
            'dosis_l_ha'  => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('alertas', [
            'parcela_id' => $parcela->id,
            'user_id'    => $user->id,
            'tipo'       => 'plazo_seguridad',
            'nivel'      => 'warning',
        ]);
        $this->assertDatabaseMissing('alertas', ['tipo' => 'dosis_excedida']);
    }

    public function test_tratamiento_con_plazo_ya_vencido_no_genera_alerta(): void
    {
        $user    = User::factory()->create();
        $parcela = $this->parcelaDe($user);

        $this->actingAs($user)->post(route('tratamientos.store', $parcela), [
            'producto_id' => $this->producto(['plazo_seguridad_dias' => 7])->id,
            'fecha'       => now()->subDays(30)->toDateString(),
            'dosis_l_ha'  => 2,
        ]);

        $this->assertDatabaseEmpty('alertas');
    }

    public function test_dosis_superior_al_maximo_genera_alerta_critica(): void
    {
        $user    = User::factory()->create();
        $parcela = $this->parcelaDe($user);

        $this->actingAs($user)->post(route('tratamientos.store', $parcela), [
            'producto_id' => $this->producto(['plazo_seguridad_dias' => null])->id,
            'fecha'       => now()->toDateString(),
            'dosis_l_ha'  => 3,
        ]);

        $this->assertDatabaseHas('alertas', [
            'parcela_id' => $parcela->id,
            'tipo'       => 'dosis_excedida',
            'nivel'      => 'critical',
        ]);
        $this->assertDatabaseMissing('alertas', ['tipo' => 'plazo_seguridad']);
    }

    // ── API ──────────────────────────────────────────────────────────────────

    public function test_api_requiere_token(): void
    {
        $this->getJson('/api/alertas')->assertUnauthorized();
    }

    public function test_api_con_token_lista_alertas_propias(): void
    {
        $user = User::factory()->create();
        $this->alerta($user, ['mensaje' => 'Mía por API']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/alertas')
            ->assertOk()
            ->assertJsonPath('data.0.mensaje', 'Mía por API');
    }

    public function test_api_no_expone_alertas_ajenas(): void
    {
        $alerta = $this->alerta(User::factory()->create());
        $token  = User::factory()->create()->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson("/api/alertas/{$alerta->id}")->assertForbidden();
    }
}
