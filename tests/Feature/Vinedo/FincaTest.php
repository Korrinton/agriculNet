<?php

namespace Tests\Feature\Vinedo;

use App\Models\User;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FincaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function user(): User
    {
        return User::factory()->create();
    }

    private function validFincaData(array $overrides = []): array
    {
        return array_merge([
            'provincia_cod' => 45,
            'municipio_cod' => 54,
            'paraje'        => 'El Monte',
            'parcelas'      => [
                [
                    'poligono'      => 70,
                    'parcela_sigpac'=> 126,
                    'recinto'       => 1,
                    'superficie_ha' => 1.50,
                    'uso'           => 'Viña en espaldera',
                    'agregado'      => 0,
                ],
            ],
        ], $overrides);
    }

    // ── autenticación ────────────────────────────────────────────────────────

    public function test_crear_finca_requiere_autenticacion(): void
    {
        $response = $this->post(route('vinedo.fincas.store'), $this->validFincaData());

        $response->assertRedirectToRoute('login');
        $this->assertDatabaseEmpty('fincas');
    }

    // ── creación básica ──────────────────────────────────────────────────────

    public function test_usuario_puede_crear_finca_con_parcelas(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)
            ->post(route('vinedo.fincas.store'), $this->validFincaData());

        $finca = Finca::where('user_id', $user->id)->first();

        $response->assertRedirectToRoute('vinedo.fincas.show', $finca);

        $this->assertDatabaseHas('fincas', [
            'user_id'       => $user->id,
            'provincia_cod' => 45,
            'municipio_cod' => 54,
            'paraje'        => 'El Monte',
        ]);

        $this->assertDatabaseHas('parcelas', [
            'finca_id'      => $finca->id,
            'poligono'      => 70,
            'parcela_sigpac'=> 126,
            'recinto'       => 1,
            'uso'           => 'Viña en espaldera',
        ]);
    }

    public function test_nombre_parcela_se_genera_del_numero_de_parcela(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('vinedo.fincas.store'), $this->validFincaData());

        $finca = Finca::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('parcelas', [
            'finca_id' => $finca->id,
            'nombre'   => 'Parcela 126',
        ]);
    }

    public function test_nombre_parcela_fallback_sin_numero_sigpac(): void
    {
        $user = $this->user();

        $data = $this->validFincaData([
            'parcelas' => [
                ['superficie_ha' => 0.5, 'uso' => 'Secano', 'agregado' => 0],
            ],
        ]);

        $this->actingAs($user)->post(route('vinedo.fincas.store'), $data);

        $finca = Finca::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('parcelas', [
            'finca_id' => $finca->id,
            'nombre'   => 'Parcela 1',
        ]);
    }

    public function test_finca_pertenece_al_usuario_autenticado(): void
    {
        $user  = $this->user();
        $other = $this->user();

        $this->actingAs($user)->post(route('vinedo.fincas.store'), $this->validFincaData());

        $this->assertDatabaseCount('fincas', 1);
        $this->assertDatabaseHas('fincas', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('fincas', ['user_id' => $other->id]);
    }

    // ── múltiples parcelas ───────────────────────────────────────────────────

    public function test_puede_crear_finca_con_multiples_parcelas(): void
    {
        $user = $this->user();

        $data = $this->validFincaData([
            'parcelas' => [
                ['poligono' => 70, 'parcela_sigpac' => 126, 'superficie_ha' => 1.5, 'uso' => 'Viña en espaldera', 'agregado' => 0],
                ['poligono' => 70, 'parcela_sigpac' => 127, 'superficie_ha' => 0.8, 'uso' => 'Secano',            'agregado' => 0],
            ],
        ]);

        $this->actingAs($user)->post(route('vinedo.fincas.store'), $data);

        $finca = Finca::where('user_id', $user->id)->first();

        $this->assertSame(2, $finca->parcelas()->count());
    }

    // ── validación ───────────────────────────────────────────────────────────

    public function test_provincia_es_obligatoria(): void
    {
        $data = $this->validFincaData(['provincia_cod' => null]);

        $response = $this->actingAs($this->user())
            ->post(route('vinedo.fincas.store'), $data);

        $response->assertSessionHasErrors('provincia_cod');
        $this->assertDatabaseEmpty('fincas');
    }

    public function test_municipio_es_obligatorio(): void
    {
        $data = $this->validFincaData(['municipio_cod' => null]);

        $response = $this->actingAs($this->user())
            ->post(route('vinedo.fincas.store'), $data);

        $response->assertSessionHasErrors('municipio_cod');
        $this->assertDatabaseEmpty('fincas');
    }

    public function test_se_requiere_al_menos_una_parcela(): void
    {
        $data = $this->validFincaData(['parcelas' => []]);

        $response = $this->actingAs($this->user())
            ->post(route('vinedo.fincas.store'), $data);

        $response->assertSessionHasErrors('parcelas');
        $this->assertDatabaseEmpty('fincas');
    }

    public function test_superficie_parcela_es_obligatoria(): void
    {
        $data = $this->validFincaData([
            'parcelas' => [
                ['poligono' => 70, 'parcela_sigpac' => 126, 'uso' => 'Secano', 'agregado' => 0],
            ],
        ]);

        $response = $this->actingAs($this->user())
            ->post(route('vinedo.fincas.store'), $data);

        $response->assertSessionHasErrors('parcelas.0.superficie_ha');
        $this->assertDatabaseEmpty('fincas');
    }

    public function test_uso_parcela_es_obligatorio(): void
    {
        $data = $this->validFincaData([
            'parcelas' => [
                ['poligono' => 70, 'parcela_sigpac' => 126, 'superficie_ha' => 1.5, 'agregado' => 0],
            ],
        ]);

        $response = $this->actingAs($this->user())
            ->post(route('vinedo.fincas.store'), $data);

        $response->assertSessionHasErrors('parcelas.0.uso');
        $this->assertDatabaseEmpty('fincas');
    }

    // ── aislamiento entre usuarios ───────────────────────────────────────────

    public function test_usuario_no_puede_ver_finca_de_otro(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $this->actingAs($owner)->post(route('vinedo.fincas.store'), $this->validFincaData());
        $finca = Finca::where('user_id', $owner->id)->first();

        $response = $this->actingAs($other)
            ->get(route('vinedo.fincas.show', $finca));

        $response->assertForbidden();
    }
}
