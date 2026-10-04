<?php

namespace Tests\Feature\Meteorologia;

use App\Models\User;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Services\EstacionesCercanas;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EstacionesCercanasTest extends TestCase
{
    use RefreshDatabase;

    private const SIGPAC = 'sigpac-hubcloud.es/ogcapi/collections/recintos/items*';

    private User $user;
    private Finca $finca;

    protected function setUp(): void
    {
        parent::setUp();

        // Corral de Almaguer (Toledo, este de la provincia) y estaciones alrededor
        $this->estacion('3260B', 'TOLEDO', 45, 39.8847, -4.0458);
        $this->estacion('4061X', 'QUINTANAR DE LA ORDEN', 45, 39.5889, -3.0450);
        $this->estacion('4064Y', 'ALCAZAR DE SAN JUAN', 13, 39.3958, -3.2069);
        $this->estacion('3094B', 'TARANCÓN', 16, 40.0167, -3.0006);
        $this->estacion('1387', 'A CORUÑA', 15, 43.3664, -8.4194);
        $this->estacion('SINXY', 'SIN COORDENADAS', 45, 0, 0);

        $this->user = User::factory()->create();
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Parcela 126', 'uso' => 'Secano', 'superficie_ha' => 1.9,
            'agregado' => 0, 'poligono' => 70, 'parcela_sigpac' => 126, 'recinto' => 1,
        ]);
        $this->finca->refresh();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function estacion(string $codigo, string $nombre, int $provincia, float $lat, float $lon): void
    {
        EstacionMeteorologica::create([
            'nombre' => $nombre, 'codigo_externo' => $codigo, 'provincia_cod' => $provincia,
            'latitud' => $lat, 'longitud' => $lon, 'fuente' => 'aemet',
        ]);
    }

    /** Cuadrado de ~200 m centrado en (lat, lon), como devuelve SIGPAC (lon/lat). */
    private function recinto(float $lat, float $lon): array
    {
        $d = 0.001;

        return ['type' => 'FeatureCollection', 'features' => [[
            'type' => 'Feature', 'properties' => [],
            'geometry' => ['type' => 'Polygon', 'coordinates' => [[
                [$lon - $d, $lat - $d], [$lon + $d, $lat - $d], [$lon + $d, $lat + $d], [$lon - $d, $lat + $d],
            ]]],
        ]]];
    }

    private function vacio(): array
    {
        return ['type' => 'FeatureCollection', 'features' => []];
    }

    // ── ordenación ───────────────────────────────────────────────────────────

    public function test_ordena_por_distancia_incluyendo_provincias_vecinas(): void
    {
        Http::fake([self::SIGPAC => Http::response($this->recinto(39.7600, -3.1650))]);

        $estaciones = app(EstacionesCercanas::class)->para($this->finca);

        $this->assertSame(
            ['QUINTANAR DE LA ORDEN', 'TARANCÓN', 'ALCAZAR DE SAN JUAN', 'TOLEDO', 'A CORUÑA'],
            $estaciones->pluck('nombre')->all(),
        );
        $this->assertEqualsWithDelta(21.62, $estaciones->first()->distancia_km, 0.05);
        $this->assertSame('parcela', $this->finca->fresh()->coordenadas_origen);
    }

    public function test_limita_el_numero_de_estaciones(): void
    {
        Http::fake([self::SIGPAC => Http::response($this->recinto(39.7600, -3.1650))]);

        $this->assertCount(2, app(EstacionesCercanas::class)->para($this->finca, 2));
    }

    public function test_si_la_parcela_no_existe_usa_su_poligono(): void
    {
        Http::fake(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $q);

            return Http::response(isset($q['parcela']) ? $this->vacio() : $this->recinto(39.7614, -3.1479));
        });

        app(EstacionesCercanas::class)->para($this->finca);

        $finca = $this->finca->fresh();
        $this->assertSame('poligono', $finca->coordenadas_origen);
        $this->assertEqualsWithDelta(39.7614, $finca->latitud, 0.0001);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'poligono=70') && str_contains($r->url(), 'limit=1'));
    }

    public function test_sin_sigpac_vuelve_a_la_provincia_y_no_reintenta_en_cada_visita(): void
    {
        Http::fake([self::SIGPAC => Http::response($this->vacio())]);

        $estaciones = app(EstacionesCercanas::class)->para($this->finca);

        $this->assertSame(['QUINTANAR DE LA ORDEN', 'SIN COORDENADAS', 'TOLEDO'], $estaciones->pluck('nombre')->all());
        $this->assertNull($estaciones->first()->distancia_km);
        $this->assertSame('no_disponible', $this->finca->fresh()->coordenadas_origen);

        $enviadas = count(Http::recorded());
        app(EstacionesCercanas::class)->para($this->finca->fresh());
        $this->assertCount($enviadas, Http::recorded());
    }

    // ── recalcular la ubicación ──────────────────────────────────────────────

    public function test_cambiar_la_referencia_de_una_parcela_reubica_la_finca(): void
    {
        $this->finca->update(['latitud' => 39.76, 'longitud' => -3.16, 'coordenadas_origen' => 'parcela']);

        $this->finca->parcelas()->first()->update(['parcela_sigpac' => 127]);

        $this->assertFalse($this->finca->fresh()->tieneCoordenadas());
    }

    public function test_editar_otros_datos_de_la_parcela_no_reubica(): void
    {
        $this->finca->update(['latitud' => 39.76, 'longitud' => -3.16, 'coordenadas_origen' => 'parcela']);

        $this->finca->parcelas()->first()->update(['uso' => 'Viña en espaldera']);

        $this->assertTrue($this->finca->fresh()->tieneCoordenadas());
    }

    public function test_cambiar_el_municipio_reubica_la_finca(): void
    {
        $this->finca->update(['latitud' => 39.76, 'longitud' => -3.16, 'coordenadas_origen' => 'parcela']);

        $this->finca->update(['municipio_cod' => 82]);

        $this->assertNull($this->finca->fresh()->coordenadas_origen);
    }

    public function test_borrar_una_parcela_reubica_la_finca(): void
    {
        $this->finca->update(['latitud' => 39.76, 'longitud' => -3.16, 'coordenadas_origen' => 'no_disponible']);

        $this->finca->parcelas()->first()->delete();

        $this->assertNull($this->finca->fresh()->coordenadas_origen);
    }

    // ── ficha de la finca ────────────────────────────────────────────────────

    public function test_la_ficha_muestra_las_estaciones_con_distancia_y_provincia(): void
    {
        Http::fake([self::SIGPAC => Http::response($this->recinto(39.7600, -3.1650))]);

        $this->actingAs($this->user)->get(route('vinedo.fincas.show', $this->finca))
            ->assertOk()
            ->assertSeeInOrder(['más cercanas primero', '22 km · QUINTANAR DE LA ORDEN', 'TARANCÓN', '(Cuenca)', 'ALCAZAR DE SAN JUAN', '(Ciudad Real)'])
            ->assertSee('ubicación SIGPAC de sus parcelas');
    }

    public function test_la_ficha_muestra_la_distancia_a_la_estacion_vinculada(): void
    {
        $toledo = EstacionMeteorologica::where('codigo_externo', '3260B')->first();
        $this->finca->update([
            'estacion_meteorologica_id' => $toledo->id,
            'latitud' => 39.76, 'longitud' => -3.165, 'coordenadas_origen' => 'parcela',
        ]);
        Http::fake();

        $this->actingAs($this->user)->get(route('vinedo.fincas.show', $this->finca))
            ->assertOk()
            ->assertSee('· a 76 km');

        Http::assertNothingSent();
    }
}
