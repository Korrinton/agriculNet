<?php

namespace Tests\Feature\Meteorologia;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Models\PrediccionMeteorologica;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AemetTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://opendata.aemet.es/opendata/api';

    private User $user;
    private Finca $finca;
    private Parcela $parcela;
    private EstacionMeteorologica $estacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['aemet.api_key' => 'clave-de-prueba']);

        $this->user = User::factory()->create();
        $this->estacion = EstacionMeteorologica::create([
            'nombre' => 'TOLEDO', 'latitud' => 39.88, 'longitud' => -4.05,
            'fuente' => 'aemet', 'codigo_externo' => '3260B',
        ]);
        $this->finca = Finca::create([
            'user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54,
            'paraje' => 'pancierto', 'estacion_meteorologica_id' => $this->estacion->id,
        ]);
        $this->parcela = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Parcela 126', 'uso' => 'Viña en espaldera',
            'superficie_ha' => 1.9, 'agregado' => 0,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** Respuesta de dos pasos de AEMET; el contenido final llega en ISO-8859-1. */
    private function fakeAemet(?array $prediccion = null, array $observados = []): void
    {
        $prediccion ??= $this->prediccion([['2026-04-14', 18, 5, [0, 30]], ['2026-04-15', 16, -1, [10, 70]]]);

        Http::fake([
            self::API . '/prediccion/*'  => Http::response(['estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/pred']),
            self::API . '/valores/*'     => Http::response(['estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/obs']),
            'opendata.aemet.es/opendata/sh/pred' => Http::response(mb_convert_encoding(json_encode($prediccion), 'ISO-8859-1', 'UTF-8')),
            'opendata.aemet.es/opendata/sh/obs'  => Http::response(mb_convert_encoding(json_encode($observados), 'ISO-8859-1', 'UTF-8')),
        ]);
    }

    /** @param array<array{0:string,1:int,2:int,3:int[]}> $dias fecha, máx, mín, probabilidades por tramo */
    private function prediccion(array $dias): array
    {
        return [[
            'nombre' => 'Corral de Almaguer', 'provincia' => 'Toledo', 'elaborado' => '2026-04-14T11:01:09',
            'prediccion' => ['dia' => array_map(fn ($d) => [
                'fecha' => "{$d[0]}T00:00:00",
                'temperatura' => ['maxima' => $d[1], 'minima' => $d[2]],
                'probPrecipitacion' => array_map(fn ($v) => ['value' => $v, 'periodo' => '00-12'], $d[3]),
                'humedadRelativa' => ['maxima' => 90, 'minima' => 40],
                'estadoCielo' => [
                    ['value' => '', 'periodo' => '00-24', 'descripcion' => ''],
                    ['value' => '12', 'periodo' => '12-24', 'descripcion' => 'Poco nuboso'],
                ],
            ], $dias)],
        ]];
    }

    private function fase(string $bbch, string $fecha): void
    {
        $estado = EstadoFenologico::create(['codigo_bbch' => $bbch, 'nombre' => "BBCH {$bbch}"]);
        RegistroFenologico::create([
            'parcela_id' => $this->parcela->id, 'estado_fenologico_id' => $estado->id,
            'user_id' => $this->user->id, 'fecha_observacion' => $fecha,
        ]);
    }

    // ── importación de la predicción ─────────────────────────────────────────

    public function test_sincronizar_guarda_la_prediccion_del_municipio(): void
    {
        $this->fakeAemet();

        $this->artisan('aemet:sincronizar')->assertSuccessful();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/prediccion/especifica/municipio/diaria/45054'));

        $dia = PrediccionMeteorologica::delMunicipio(45, 54)->whereDate('fecha', '2026-04-15')->sole();
        $this->assertSame('-1.00', $dia->temp_min);
        $this->assertSame(70, $dia->prob_precipitacion);            // máximo de los tramos
        $this->assertSame('Poco nuboso', $dia->estado_cielo);       // primer tramo con descripción
        $this->assertSame('2026-04-14 09:01:09', $dia->elaborado_at->utc()->format('Y-m-d H:i:s')); // hora peninsular → UTC
    }

    public function test_sincronizar_actualiza_sin_duplicar(): void
    {
        $this->fakeAemet();
        $this->artisan('aemet:sincronizar')->assertSuccessful();
        $this->artisan('aemet:sincronizar')->assertSuccessful();

        $this->assertSame(2, PrediccionMeteorologica::count());
    }

    public function test_sincronizar_importa_datos_observados_de_estaciones_vinculadas(): void
    {
        $this->fakeAemet(observados: [
            ['fecha' => '2026-04-10', 'tmax' => '15,2', 'tmin' => '-0,8', 'prec' => '0,0', 'hrMedia' => '70', 'velmedia' => '2,5'],
        ]);

        $this->artisan('aemet:sincronizar')->assertSuccessful();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/estacion/3260B/'));
        $dato = DatoMeteorologico::sole();
        $this->assertSame('-0.80', $dato->temp_min);
    }

    public function test_un_error_de_aemet_no_detiene_el_resto(): void
    {
        Http::fake([
            self::API . '/prediccion/*' => Http::response('', 401),
            self::API . '/valores/*'    => Http::response(['estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/obs']),
            'opendata.aemet.es/opendata/sh/obs' => Http::response(json_encode([
                ['fecha' => '2026-04-10', 'tmax' => '15,2', 'tmin' => '3,1', 'prec' => '0,0'],
            ])),
        ]);

        $this->artisan('aemet:sincronizar')
            ->expectsOutputToContain('Predicción 45054: AEMET: error HTTP 401')
            ->assertSuccessful();

        $this->assertSame(1, DatoMeteorologico::count());
    }

    public function test_sin_api_key_falla_sin_llamar_a_aemet(): void
    {
        config(['aemet.api_key' => null]);
        Http::fake();

        $this->artisan('aemet:sincronizar')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_aemet_esta_programado_antes_que_las_alertas(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('aemet:sincronizar')
            ->expectsOutputToContain('alertas:generar')
            ->assertSuccessful();
    }

    // ── previsión de helada ──────────────────────────────────────────────────

    public function test_prevision_de_helada_con_viña_brotada_es_critica(): void
    {
        $this->fase('09', '2026-04-01');
        $this->fakeAemet();
        $this->artisan('aemet:sincronizar');

        Carbon::setTestNow('2026-04-14 08:00');
        $this->artisan('alertas:generar')->assertSuccessful();

        $alerta = Alerta::where('tipo', 'prevision_helada')->sole();
        $this->assertSame('critical', $alerta->nivel);
        $this->assertStringContainsString('mínima de -1 °C el 15/04/2026', $alerta->mensaje);
    }

    public function test_prevision_usa_margen_de_un_grado(): void
    {
        $this->fase('09', '2026-04-01');
        $this->fakeAemet($this->prediccion([['2026-04-15', 15, 1, [0]], ['2026-04-16', 15, 2, [0]]]));
        $this->artisan('aemet:sincronizar');

        Carbon::setTestNow('2026-04-14 08:00');
        $this->artisan('alertas:generar');

        $this->assertSame(['2026-04-15'], Alerta::where('tipo', 'prevision_helada')->get()
            ->map(fn ($a) => explode(':', $a->clave)[2])->all());
    }

    public function test_prevision_de_dias_pasados_no_avisa(): void
    {
        $this->fase('09', '2026-04-01');
        $this->fakeAemet();
        $this->artisan('aemet:sincronizar');

        Carbon::setTestNow('2026-04-20 08:00');
        $this->artisan('alertas:generar');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'prevision_helada']);
    }

    // ── página de meteorología ───────────────────────────────────────────────

    public function test_la_pagina_muestra_la_prevision_de_sus_fincas(): void
    {
        PrediccionMeteorologica::create(['provincia_cod' => 45, 'municipio_cod' => 54, 'fecha' => now()->addDay(), 'temp_max' => 21, 'temp_min' => 7]);
        PrediccionMeteorologica::create(['provincia_cod' => 28, 'municipio_cod' => 79, 'fecha' => now()->addDay(), 'temp_max' => 33, 'temp_min' => 19]);

        $this->actingAs($this->user)->get(route('meteorologia.index'))
            ->assertOk()
            ->assertSee('municipio 45054')
            ->assertSee('21°')
            ->assertDontSee('33°');
    }

    public function test_sin_fincas_no_carga_previsiones_ajenas(): void
    {
        PrediccionMeteorologica::create(['provincia_cod' => 45, 'municipio_cod' => 54, 'fecha' => now()->addDay(), 'temp_max' => 21, 'temp_min' => 7]);

        $this->actingAs(User::factory()->create())->get(route('meteorologia.index'))
            ->assertOk()
            ->assertDontSee('municipio 45054');
    }

    // ── integridad de los datos compartidos de AEMET ─────────────────────────

    public function test_no_se_pueden_sobrescribir_datos_de_una_estacion_aemet(): void
    {
        DatoMeteorologico::create(['estacion_id' => $this->estacion->id, 'fecha' => '2026-04-10', 'temp_max' => 15, 'temp_min' => 3]);

        $this->actingAs($this->user)
            ->post(route('meteorologia.datos.store', $this->finca), ['fecha' => '2026-04-10', 'temp_max' => 40, 'temp_min' => 30])
            ->assertSessionHas('error');

        $this->assertSame('15.00', DatoMeteorologico::sole()->temp_max);
    }

    public function test_no_se_pueden_borrar_datos_de_una_estacion_aemet(): void
    {
        $dato = DatoMeteorologico::create(['estacion_id' => $this->estacion->id, 'fecha' => '2026-04-10', 'temp_max' => 15, 'temp_min' => 3]);

        $this->actingAs($this->user)
            ->delete(route('meteorologia.datos.destroy', [$this->finca, $dato]))
            ->assertForbidden();

        $this->assertModelExists($dato);
    }

    public function test_no_se_puede_vincular_la_estacion_manual_de_otro(): void
    {
        $manual = EstacionMeteorologica::create(['nombre' => 'Manual – ajena', 'latitud' => 0, 'longitud' => 0, 'fuente' => 'manual']);

        $this->actingAs($this->user)
            ->post(route('meteorologia.datos.vincular', $this->finca), ['estacion_id' => $manual->id])
            ->assertSessionHasErrors('estacion_id');

        $this->assertSame($this->estacion->id, $this->finca->fresh()->estacion_meteorologica_id);
    }

    public function test_finca_sin_estacion_puede_registrar_datos_manuales(): void
    {
        $this->finca->update(['estacion_meteorologica_id' => null]);

        $this->actingAs($this->user)
            ->post(route('meteorologia.datos.store', $this->finca), ['fecha' => '2026-04-10', 'temp_max' => 20, 'temp_min' => 5])
            ->assertSessionHas('success');

        $this->assertSame('manual', $this->finca->fresh()->estacion->fuente);
    }
}
