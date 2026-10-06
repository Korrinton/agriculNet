<?php

namespace Tests\Feature\CalendarioFenologico;

use App\Models\User;
use App\Modules\CalendarioFenologico\Models\GradoDia;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradosDiaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $vina;
    private EstacionMeteorologica $estacion;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-10 10:00');

        $this->user = User::factory()->create();
        $this->estacion = EstacionMeteorologica::create(['nombre' => 'Manual', 'latitud' => 0, 'longitud' => 0, 'fuente' => 'manual']);
        $this->finca = Finca::create([
            'user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54,
            'estacion_meteorologica_id' => $this->estacion->id,
        ]);
        $this->vina = $this->parcela('Viña en espaldera');
    }

    private function parcela(string $uso): Parcela
    {
        return Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => $uso, 'uso' => $uso, 'superficie_ha' => 1,
            'agregado' => 0, 'poligono' => 1, 'parcela_sigpac' => 1, 'recinto' => 1,
        ]);
    }

    private function dato(string $fecha, float $max, float $min): void
    {
        DatoMeteorologico::create(['estacion_id' => $this->estacion->id, 'fecha' => $fecha, 'temp_max' => $max, 'temp_min' => $min]);
    }

    private function acumulados(Parcela $parcela): array
    {
        return GradoDia::where('parcela_id', $parcela->id)->orderBy('fecha')->get()
            ->mapWithKeys(fn (GradoDia $g) => [$g->fecha->toDateString() => (float) $g->acumulado])
            ->all();
    }

    public function test_guarda_el_acumulado_diario_solo_del_ciclo_de_la_vid(): void
    {
        $this->dato('2026-03-31', 30, 20); // antes del 1 de abril: no cuenta
        $this->dato('2026-04-01', 24, 10); // media 17 → 7
        $this->dato('2026-04-02', 14, 2);  // media 8 → 0
        $this->dato('2026-04-03', 28, 14); // media 21 → 11
        $this->parcela('Olivar');          // no es viña: no se calcula

        $this->artisan('grados-dia:recalcular')->assertSuccessful();

        $this->assertSame(['2026-04-01' => 7.0, '2026-04-02' => 7.0, '2026-04-03' => 18.0], $this->acumulados($this->vina));
        $this->assertSame(3, GradoDia::count());
    }

    public function test_recalcular_sustituye_lo_guardado(): void
    {
        $this->dato('2026-04-01', 24, 10);
        $this->artisan('grados-dia:recalcular')->assertSuccessful();

        DatoMeteorologico::where('fecha', '2026-04-01')->update(['temp_max' => 30]);
        $this->artisan('grados-dia:recalcular')->assertSuccessful();

        $this->assertSame(['2026-04-01' => 10.0], $this->acumulados($this->vina));
    }

    public function test_un_dato_manual_recalcula_las_vinas_de_la_finca(): void
    {
        $this->dato('2026-04-01', 24, 10);

        $this->actingAs($this->user)->post(route('meteorologia.datos.store', $this->finca), [
            'fecha' => '2026-04-02', 'temp_max' => 26, 'temp_min' => 14,
        ])->assertRedirect();

        $this->assertSame(['2026-04-01' => 7.0, '2026-04-02' => 17.0], $this->acumulados($this->vina));
    }

    public function test_desvincular_la_estacion_borra_los_grados_dia(): void
    {
        $this->dato('2026-04-01', 24, 10);
        $this->artisan('grados-dia:recalcular')->assertSuccessful();

        $this->actingAs($this->user)->delete(route('meteorologia.datos.desvincular', $this->finca))->assertRedirect();

        $this->assertSame([], $this->acumulados($this->vina));
    }

    public function test_el_panel_muestra_los_grados_dia_guardados(): void
    {
        GradoDia::create(['parcela_id' => $this->vina->id, 'fecha' => '2026-06-01', 'acumulado' => 412.4]);

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('412');
    }
}
