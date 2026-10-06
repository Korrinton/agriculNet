<?php

namespace Tests\Feature\Alertas;

use App\Models\User;
use App\Modules\Alertas\Mail\ResumenAlertas;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AlertasCorreoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Parcela $parcela;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:10');
        Mail::fake();

        $this->user = User::factory()->create(['name' => 'Ramón']);
        $finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'Los Llanos']);
        $this->parcela = Parcela::create([
            'finca_id' => $finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2,
            'agregado' => 0, 'poligono' => 1, 'parcela_sigpac' => 1, 'recinto' => 1,
        ]);
    }

    private function alerta(string $nivel, string $mensaje, array $extra = []): Alerta
    {
        $alerta = Alerta::create(['parcela_id' => $this->parcela->id, 'user_id' => $extra['user_id'] ?? $this->user->id,
            'tipo' => 'helada', 'nivel' => $nivel, 'mensaje' => $mensaje]);
        // created_at y notificada_at no son asignables en masa
        $alerta->forceFill($extra)->save();

        return $alerta;
    }

    private function resumenEnviado(): ResumenAlertas
    {
        $enviados = Mail::queued(ResumenAlertas::class);
        $this->assertCount(1, $enviados);

        return $enviados->first();
    }

    public function test_envia_un_resumen_con_las_alertas_nuevas_y_solo_una_vez(): void
    {
        $nueva = $this->alerta('critical', 'Helada de -3 °C prevista el martes');
        $this->alerta('warning', 'Antigua', ['created_at' => now()->subDays(4)]);
        $this->alerta('warning', 'Ya leída', ['leida' => true]);
        $this->alerta('warning', 'Ya enviada', ['notificada_at' => now()->subDay()]);

        $this->artisan('alertas:enviar')->assertSuccessful();

        $correo = $this->resumenEnviado();
        $this->assertTrue($correo->hasTo($this->user->email));
        $this->assertSame(['Los Llanos' => [['nivel' => 'critical', 'mensaje' => 'Helada de -3 °C prevista el martes', 'fecha' => '05/10']]], $correo->porFinca);
        $this->assertNotNull($nueva->fresh()->notificada_at);

        // Al día siguiente no se repite
        Mail::fake();
        $this->artisan('alertas:enviar')->assertSuccessful();
        Mail::assertNothingQueued();
    }

    public function test_respeta_la_preferencia_del_usuario(): void
    {
        $this->user->forceFill(['alertas_por_correo' => 'criticas'])->save();
        $this->alerta('critical', 'Helada fuerte');
        $aviso = $this->alerta('warning', 'Riesgo de mildiu');

        $this->artisan('alertas:enviar');

        $this->assertSame(['Helada fuerte'], array_column($this->resumenEnviado()->porFinca['Los Llanos'], 'mensaje'));
        // El aviso que no quería recibir tampoco se le mandará mañana
        $this->assertNotNull($aviso->fresh()->notificada_at);
    }

    public function test_no_envia_a_quien_no_quiere_ni_a_las_cuentas_bloqueadas(): void
    {
        $this->user->forceFill(['alertas_por_correo' => 'ninguna'])->save();
        $this->alerta('critical', 'Helada');
        $bloqueado = User::factory()->create();
        $bloqueado->forceFill(['bloqueado_at' => now()])->save();
        $this->alerta('critical', 'Helada', ['user_id' => $bloqueado->id]);

        $this->artisan('alertas:enviar');

        Mail::assertNothingQueued();
    }

    public function test_el_correo_va_en_castellano_con_enlace_de_baja(): void
    {
        $this->alerta('critical', 'Helada de -3 °C prevista el martes');
        $this->alerta('info', 'Termina hoy el plazo de seguridad');
        $this->artisan('alertas:enviar');
        $correo = $this->resumenEnviado();

        $this->assertSame('1 alerta importante en tus fincas', $correo->envelope()->subject);
        $html = $correo->render();
        $this->assertStringContainsString('Hola, Ramón', $html);
        $this->assertStringContainsString('Tienes 2 alertas nuevas', $html);
        $this->assertStringContainsString('Los Llanos', $html);
        // El correo lleva los estilos en línea: <strong style="…">
        $this->assertMatchesRegularExpression('#<strong[^>]*>Importante</strong>#', $html);
        $this->assertMatchesRegularExpression('#<strong[^>]*>Información</strong>#', $html);
        $this->assertStringContainsString('dejar de recibirlas', $html);
        $this->assertStringContainsString('alertas/correo/baja/' . $this->user->id, $correo->headers()->text['List-Unsubscribe']);
    }

    public function test_darse_de_baja_desde_el_enlace_del_correo(): void
    {
        $enlace = URL::signedRoute('alertas.correo.baja', ['usuario' => $this->user->id]);

        // Abrir el enlace no da de baja (los antivirus abren los enlaces de los correos)
        $this->get($enlace)->assertOk()->assertSee('Dejar de recibir las alertas');
        $this->assertSame('todas', $this->user->fresh()->alertas_por_correo);

        // El botón (o la baja con un clic del cliente de correo, sin CSRF) sí
        $this->post($enlace)->assertOk()->assertSee('ya no recibirás las alertas');
        $this->assertSame('ninguna', $this->user->fresh()->alertas_por_correo);

        // Sin firma, o con la de otro usuario, no
        $otro = User::factory()->create();
        $this->post(route('alertas.correo.baja', ['usuario' => $otro->id]))->assertForbidden();
        $this->post(str_replace("/baja/{$this->user->id}", "/baja/{$otro->id}", $enlace))->assertForbidden();
        $this->assertSame('todas', $otro->fresh()->alertas_por_correo);
    }

    public function test_la_preferencia_se_cambia_en_el_perfil(): void
    {
        $this->actingAs($this->user)->get(route('profile'))->assertSeeVolt('profile.alertas-correo-form');

        Volt::test('profile.alertas-correo-form')->assertSet('alertas_por_correo', 'todas')
            ->set('alertas_por_correo', 'avisos')->call('guardar')->assertHasNoErrors();
        $this->assertSame('avisos', $this->user->fresh()->alertas_por_correo);

        Volt::test('profile.alertas-correo-form')->set('alertas_por_correo', 'otra')->call('guardar')
            ->assertHasErrors('alertas_por_correo');
    }
}
