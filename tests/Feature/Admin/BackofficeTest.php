<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Admin\Jobs\LanzarTarea;
use App\Modules\Admin\Models\EjecucionTarea;
use App\Modules\Admin\Services\Tareas;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BackofficeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $agricultor;

    private const PAGINAS = [
        'admin.panel', 'admin.usuarios.index', 'admin.tareas.index', 'admin.variedades.index', 'admin.variedades.create',
        'admin.categorias.index', 'admin.estaciones.index', 'admin.fitosanitarios.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->forceFill(['is_admin' => true])->save();
        $this->agricultor = User::factory()->create(['name' => 'Ramón', 'email' => 'ramon@example.com']);

        $finca = Finca::create(['user_id' => $this->agricultor->id, 'provincia_cod' => 45, 'municipio_cod' => 54]);
        $parcela = Parcela::create([
            'finca_id' => $finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2.5,
            'agregado' => 0, 'poligono' => 1, 'parcela_sigpac' => 1, 'recinto' => 1,
        ]);
        $producto = ProductoFitosanitario::create(['nombre' => 'Azufre', 'numero_registro' => 'ES-1', 'vigente' => true]);
        Tratamiento::create(['parcela_id' => $parcela->id, 'producto_id' => $producto->id, 'user_id' => $this->agricultor->id,
            'fecha' => now()->toDateString(), 'dosis_l_ha' => 2]);
    }

    // ── acceso ───────────────────────────────────────────────────────────────

    public function test_solo_los_administradores_entran(): void
    {
        $this->get(route('admin.panel'))->assertRedirect(route('login'));

        foreach (self::PAGINAS as $pagina) {
            $this->actingAs($this->agricultor)->get(route($pagina))->assertForbidden();
            $this->actingAs($this->admin)->get(route($pagina))->assertOk();
        }
    }

    public function test_el_menu_solo_muestra_administracion_a_los_administradores(): void
    {
        $this->actingAs($this->agricultor)->get(route('profile'))->assertDontSee('Administración');
        $this->actingAs($this->admin)->get(route('profile'))->assertSee('Administración');
    }

    public function test_el_comando_da_y_quita_el_acceso(): void
    {
        $this->artisan('usuarios:admin', ['email' => 'ramon@example.com'])->assertSuccessful();
        $this->assertTrue($this->agricultor->fresh()->is_admin);

        $this->artisan('usuarios:admin', ['email' => 'ramon@example.com', '--quitar' => true])->assertSuccessful();
        $this->assertFalse($this->agricultor->fresh()->is_admin);

        $this->artisan('usuarios:admin', ['email' => 'nadie@example.com'])->assertFailed();
    }

    // ── panel y usuarios ─────────────────────────────────────────────────────

    public function test_el_panel_muestra_las_cifras_globales(): void
    {
        $this->actingAs($this->admin)->get(route('admin.panel'))
            ->assertSee('Superficie por cultivo')
            ->assertSee('2,5 ha')
            ->assertSee('Tratamientos registrados por mes');
    }

    public function test_el_listado_de_usuarios_muestra_su_uso(): void
    {
        $this->actingAs($this->admin)->get(route('admin.usuarios.index', ['q' => 'ramón']))
            ->assertSee('ramon@example.com')
            ->assertSee('2,5')
            ->assertSee('1 usuario');
    }

    public function test_se_anota_el_ultimo_acceso(): void
    {
        $this->assertNull($this->agricultor->ultimo_acceso_at);
        $this->actingAs($this->agricultor)->get(route('dashboard'))->assertOk();
        $this->assertNotNull($this->agricultor->fresh()->ultimo_acceso_at);
    }

    public function test_un_usuario_bloqueado_no_puede_entrar(): void
    {
        $this->actingAs($this->admin)->post(route('admin.usuarios.bloquear', $this->agricultor))->assertRedirect();
        $this->assertTrue($this->agricultor->fresh()->estaBloqueado());

        // Con la sesión abierta, lo echa en la siguiente petición
        $this->actingAs($this->agricultor->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        // Y no puede volver a entrar
        Volt::test('pages.auth.login')->set('form.email', 'ramon@example.com')->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email');
        $this->assertGuest();

        $this->actingAs($this->admin)->post(route('admin.usuarios.desbloquear', $this->agricultor));
        $this->assertFalse($this->agricultor->fresh()->estaBloqueado());
    }

    public function test_no_se_puede_bloquear_ni_borrar_a_si_mismo_ni_a_otro_administrador(): void
    {
        $otroAdmin = User::factory()->create();
        $otroAdmin->forceFill(['is_admin' => true])->save();

        foreach ([$this->admin, $otroAdmin] as $u) {
            $this->actingAs($this->admin)->post(route('admin.usuarios.bloquear', $u))->assertSessionHas('error');
            $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $u), ['confirmacion' => $u->email])->assertSessionHas('error');
            $this->assertNotNull($u->fresh());
            $this->assertFalse($u->fresh()->estaBloqueado());
        }
    }

    public function test_borrar_una_cuenta_exige_escribir_su_email(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $this->agricultor), ['confirmacion' => 'otro'])
            ->assertSessionHasErrors('confirmacion');
        $this->assertNotNull($this->agricultor->fresh());

        $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $this->agricultor), ['confirmacion' => 'ramon@example.com'])
            ->assertRedirect(route('admin.usuarios.index'));
        $this->assertNull($this->agricultor->fresh());
        $this->assertSame(0, Finca::count());
    }

    // ── tareas ───────────────────────────────────────────────────────────────

    public function test_lanzar_una_tarea_la_encola(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)->post(route('admin.tareas.lanzar', 'grados-dia:recalcular'))->assertSessionHas('success');
        Queue::assertPushed(LanzarTarea::class, fn ($job) => $job->comando === 'grados-dia:recalcular' && $job->userId === $this->admin->id);

        $this->actingAs($this->admin)->post(route('admin.tareas.lanzar', 'migrate:fresh'))->assertNotFound();
    }

    /**
     * Laravel no dispara los eventos de consola en los tests (sí con `php artisan`, el programador
     * y el worker de colas): se activan y se recrea la consola para que los use.
     */
    private function conEventosDeConsola(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->rerouteSymfonyCommandEvents();
        $kernel->setArtisan(null);
    }

    public function test_la_tarea_lanzada_guarda_quien_la_lanzo_y_su_salida(): void
    {
        $this->conEventosDeConsola();
        $ejecucion = app(Tareas::class)->ejecutar('grados-dia:recalcular', $this->admin->id);

        $this->assertSame(1, EjecucionTarea::count());
        $this->assertSame('ok', $ejecucion->estado());
        $this->assertSame($this->admin->id, $ejecucion->user_id);
        $this->assertStringContainsString('Campaña', $ejecucion->salida);

        $this->actingAs($this->admin)->get(route('admin.tareas.index'))->assertSee('Lanzada por Admin');
    }

    public function test_las_ejecuciones_de_consola_quedan_registradas(): void
    {
        $this->conEventosDeConsola();
        $this->artisan('grados-dia:recalcular')->assertSuccessful();
        $this->artisan('usuarios:admin', ['email' => 'ramon@example.com'])->assertSuccessful(); // no es del catálogo

        $ejecucion = EjecucionTarea::sole();
        $this->assertSame('grados-dia:recalcular', $ejecucion->comando);
        $this->assertSame(0, $ejecucion->codigo_salida);
        $this->assertNull($ejecucion->user_id);
    }

    public function test_el_panel_avisa_de_las_tareas_que_fallaron(): void
    {
        EjecucionTarea::create(['comando' => 'aemet:sincronizar', 'inicio' => now()->subMinute(), 'fin' => now(), 'codigo_salida' => 1]);

        $this->actingAs($this->admin)->get(route('admin.panel'))->assertSee('Tareas con problemas')->assertSee('Datos de AEMET');
    }

    // ── catálogos ────────────────────────────────────────────────────────────

    public function test_variedades_se_crean_y_no_se_borran_si_estan_en_uso(): void
    {
        $this->actingAs($this->admin)->post(route('admin.variedades.store'), [
            'cultivo' => 'vid', 'nombre' => 'Moravia agria', 'tipo' => 'tinta', 'precocidad' => 'temprana',
        ])->assertRedirect(route('admin.variedades.index'));
        $moravia = Variedad::where('nombre', 'Moravia agria')->sole();

        // Mismo nombre en el mismo cultivo, no
        $this->actingAs($this->admin)->post(route('admin.variedades.store'), ['cultivo' => 'vid', 'nombre' => 'Moravia agria'])
            ->assertSessionHasErrors('nombre');

        // En un cultivo que no es la vid se ignoran color y maduración
        $this->actingAs($this->admin)->post(route('admin.variedades.store'), ['cultivo' => 'olivo', 'nombre' => 'Cornicabra', 'precocidad' => 'tardia']);
        $this->assertNull(Variedad::where('nombre', 'Cornicabra')->value('precocidad'));

        Parcela::query()->update(['variedad_id' => $moravia->id]);
        $this->actingAs($this->admin)->delete(route('admin.variedades.destroy', $moravia))->assertSessionHas('error');
        $this->assertModelExists($moravia);
    }

    public function test_la_categoria_de_los_tratamientos_no_se_renombra_ni_se_borra(): void
    {
        $protegida = CategoriaCoste::create(['nombre' => TratamientoService::CATEGORIA_COSTE, 'tipo' => 'insumos']);

        $this->actingAs($this->admin)->put(route('admin.categorias.update', $protegida), ['nombre' => 'Otro nombre', 'tipo' => 'otros']);
        $this->assertSame([TratamientoService::CATEGORIA_COSTE, 'otros'], [$protegida->fresh()->nombre, $protegida->fresh()->tipo]);

        $this->actingAs($this->admin)->delete(route('admin.categorias.destroy', $protegida))->assertSessionHas('error');
        $this->assertModelExists($protegida);

        $this->actingAs($this->admin)->post(route('admin.categorias.store'), ['nombre' => 'Análisis de suelo', 'tipo' => 'otros']);
        $nueva = CategoriaCoste::where('nombre', 'Análisis de suelo')->sole();
        $this->actingAs($this->admin)->delete(route('admin.categorias.destroy', $nueva))->assertSessionHas('success');
        $this->assertModelMissing($nueva);
    }

    public function test_el_catalogo_de_fitosanitarios_se_puede_buscar(): void
    {
        ProductoFitosanitario::create(['nombre' => 'Cobre Nordox', 'numero_registro' => 'ES-2', 'vigente' => true]);

        $this->actingAs($this->admin)->get(route('admin.fitosanitarios.index', ['q' => 'nordox']))
            ->assertSee('Cobre Nordox')->assertDontSee('Azufre');
    }
}
