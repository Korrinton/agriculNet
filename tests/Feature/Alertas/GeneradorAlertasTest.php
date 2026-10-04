<?php

namespace Tests\Feature\Alertas;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneradorAlertasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private EstacionMeteorologica $estacion;
    private Parcela $parcela;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->estacion = EstacionMeteorologica::create([
            'nombre' => 'TOLEDO', 'latitud' => 39.88, 'longitud' => -4.05,
            'fuente' => 'aemet', 'codigo_externo' => '3260B',
        ]);
        $finca = Finca::create([
            'user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54,
            'estacion_meteorologica_id' => $this->estacion->id,
        ]);
        $this->parcela = Parcela::create([
            'finca_id' => $finca->id, 'nombre' => 'Parcela 126', 'uso' => 'Viña en espaldera',
            'superficie_ha' => 1.9, 'agregado' => 0,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function dato(string $fecha, array $valores): void
    {
        DatoMeteorologico::create(['estacion_id' => $this->estacion->id, 'fecha' => $fecha] + $valores);
    }

    private function fase(string $bbch, string $fecha): void
    {
        $estado = EstadoFenologico::create(['codigo_bbch' => $bbch, 'nombre' => "BBCH {$bbch}"]);
        RegistroFenologico::create([
            'parcela_id' => $this->parcela->id, 'estado_fenologico_id' => $estado->id,
            'user_id' => $this->user->id, 'fecha_observacion' => $fecha,
        ]);
    }

    private function tratamiento(string $fecha, int $plazo, string $producto = 'Azufre 80'): Tratamiento
    {
        $p = ProductoFitosanitario::create(['nombre' => $producto, 'plazo_seguridad_dias' => $plazo]);

        return Tratamiento::create([
            'parcela_id' => $this->parcela->id, 'producto_id' => $p->id, 'user_id' => $this->user->id,
            'fecha' => $fecha, 'dosis_l_ha' => 1,
        ]);
    }

    private function generar(string $hoy): void
    {
        Carbon::setTestNow($hoy);
        $this->artisan('alertas:generar')->assertSuccessful();
    }

    // ── fin de plazo de seguridad ────────────────────────────────────────────

    public function test_avisa_el_dia_que_termina_el_plazo(): void
    {
        $this->tratamiento('2026-08-01', 21);

        $this->generar('2026-08-22 08:00');

        $alerta = Alerta::where('tipo', 'fin_plazo_seguridad')->sole();
        $this->assertSame($this->user->id, $alerta->user_id);
        $this->assertStringContainsString('ya se puede vendimiar', $alerta->mensaje);
    }

    public function test_no_avisa_antes_de_tiempo(): void
    {
        $this->tratamiento('2026-08-01', 21);

        $this->generar('2026-08-21 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'fin_plazo_seguridad']);
    }

    public function test_no_dice_que_se_puede_vendimiar_si_queda_otro_plazo(): void
    {
        $this->tratamiento('2026-08-01', 21, 'Azufre 80');
        $this->tratamiento('2026-08-15', 28, 'Cobre 50');

        $this->generar('2026-08-22 08:00');

        $alerta = Alerta::where('tipo', 'fin_plazo_seguridad')->sole();
        $this->assertStringContainsString('sigue vigente otro tratamiento hasta el 12/09/2026', $alerta->mensaje);
    }

    public function test_ejecutar_dos_veces_no_duplica(): void
    {
        $this->tratamiento('2026-08-01', 21);
        $this->dato('2026-04-10', ['temp_min' => -2, 'temp_max' => 12, 'precipitacion_mm' => 0]);

        $this->generar('2026-08-22 08:00');
        $this->generar('2026-08-22 09:00');
        Carbon::setTestNow('2026-04-12 08:00');
        $this->artisan('alertas:generar')->assertSuccessful();
        $this->artisan('alertas:generar')->assertSuccessful();

        $this->assertSame(1, Alerta::where('tipo', 'fin_plazo_seguridad')->count());
        $this->assertSame(1, Alerta::where('tipo', 'helada')->count());
    }

    // ── heladas ──────────────────────────────────────────────────────────────

    public function test_helada_con_viña_brotada_es_critica(): void
    {
        $this->fase('09', '2026-04-01');
        $this->dato('2026-04-10', ['temp_min' => -1.5, 'temp_max' => 14, 'precipitacion_mm' => 0]);

        $this->generar('2026-04-14 08:00');

        $alerta = Alerta::where('tipo', 'helada')->sole();
        $this->assertSame('critical', $alerta->nivel);
        $this->assertStringContainsString('-1,5 °C', $alerta->mensaje);
        $this->assertStringContainsString('10/04/2026', $alerta->mensaje);
    }

    public function test_helada_en_reposo_invernal_no_avisa(): void
    {
        $this->fase('00', '2026-03-01');
        $this->dato('2026-03-10', ['temp_min' => -4, 'temp_max' => 9, 'precipitacion_mm' => 0]);

        $this->generar('2026-03-14 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'helada']);
    }

    public function test_helada_sin_fase_registrada_en_primavera_es_aviso(): void
    {
        $this->dato('2026-04-10', ['temp_min' => 0, 'temp_max' => 15, 'precipitacion_mm' => 0]);

        $this->generar('2026-04-14 08:00');

        $this->assertSame('warning', Alerta::where('tipo', 'helada')->sole()->nivel);
    }

    public function test_helada_sin_fase_fuera_de_temporada_no_avisa(): void
    {
        $this->dato('2026-01-10', ['temp_min' => -5, 'temp_max' => 6, 'precipitacion_mm' => 0]);

        $this->generar('2026-01-14 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'helada']);
    }

    public function test_registro_fenologico_antiguo_no_cuenta(): void
    {
        // Brotación registrada el año anterior: no describe el estado actual
        $this->fase('15', '2025-05-01');
        $this->dato('2026-01-10', ['temp_min' => -5, 'temp_max' => 6, 'precipitacion_mm' => 0]);

        $this->generar('2026-01-14 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'helada']);
    }

    // ── mildiu (regla de los tres dieces) ────────────────────────────────────

    public function test_mildiu_con_lluvia_en_48h_y_temperatura_suave(): void
    {
        $this->fase('15', '2026-05-01');
        $this->dato('2026-05-09', ['temp_min' => 11, 'temp_max' => 20, 'precipitacion_mm' => 6]);
        $this->dato('2026-05-10', ['temp_min' => 12, 'temp_max' => 21, 'precipitacion_mm' => 5]);

        $this->generar('2026-05-14 08:00');

        $alerta = Alerta::where('tipo', 'riesgo_mildiu')->sole();
        $this->assertSame('warning', $alerta->nivel);
        $this->assertStringContainsString('11,0 mm en 48 h', $alerta->mensaje);
        $this->assertStringContainsString('10/05/2026', $alerta->mensaje);
    }

    public function test_mildiu_no_avisa_con_poca_lluvia(): void
    {
        $this->fase('15', '2026-05-01');
        $this->dato('2026-05-10', ['temp_min' => 12, 'temp_max' => 21, 'precipitacion_mm' => 8]);

        $this->generar('2026-05-14 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'riesgo_mildiu']);
    }

    public function test_mildiu_no_avisa_con_frio(): void
    {
        $this->fase('15', '2026-05-01');
        $this->dato('2026-05-10', ['temp_min' => 3, 'temp_max' => 14, 'precipitacion_mm' => 20]);

        $this->generar('2026-05-14 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'riesgo_mildiu']);
    }

    public function test_mildiu_no_avisa_con_brotes_pequeños(): void
    {
        $this->fase('09', '2026-05-01');
        $this->dato('2026-05-10', ['temp_min' => 12, 'temp_max' => 21, 'precipitacion_mm' => 20]);

        $this->generar('2026-05-14 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'riesgo_mildiu']);
    }

    public function test_solo_revisa_la_ventana_de_dias(): void
    {
        $this->fase('09', '2026-04-01');
        $this->dato('2026-04-01', ['temp_min' => -3, 'temp_max' => 10, 'precipitacion_mm' => 0]);

        $this->generar('2026-04-30 08:00');

        $this->assertDatabaseMissing('alertas', ['tipo' => 'helada']);
    }

    public function test_el_comando_esta_programado_a_diario(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('alertas:generar')
            ->assertSuccessful();
    }
}
