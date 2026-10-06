<?php

namespace Tests\Feature\Tratamientos;

use App\Models\User;
use App\Modules\Costes\Models\Coste;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CatalogoFitosanitariosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Finca $finca;
    private Parcela $vina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Carbon::setTestNow('2026-10-04 10:00');

        $this->user = User::factory()->create();
        $this->finca = Finca::create(['user_id' => $this->user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'paraje' => 'pancierto']);
        $this->vina = Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2,
            'agregado' => 0, 'poligono' => 70, 'parcela_sigpac' => 126, 'recinto' => 1,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function productoMapa(int $id, string $nombre, string $registro, string $formulado): array
    {
        return [
            'IdProducto' => $id, 'NumRegistro' => $registro, 'Nombre' => $nombre, 'Titular' => 'Titular S.A.',
            'Formulado' => $formulado, 'Estado' => 'Vigente', 'FechaCaducidad' => '2028-10-15T00:00:00',
        ];
    }

    /** Respuesta con el doble envoltorio JSON que devuelve REGFIWEB. */
    private function respuestaMapa(array $productos): string
    {
        return json_encode(json_encode(['Contenido' => json_encode($productos), 'Fecha' => '2026-10-04T08:00:00']));
    }

    /** Lo que devuelve el registro falso: 'todos' y, por idCultivo, los autorizados en ese cultivo. */
    private ?array $registro = null;

    /** @param array<int, list<array>> $porCultivo idCultivo del registro → productos */
    private function fakeRegistro(array $todos, array $porCultivo = []): void
    {
        // Http::fake acumula callbacks y responde el primero: se registra una vez y lee el estado actual
        if ($this->registro === null) {
            Http::fake(function (Request $request) {
                $idCultivo = $request->data()['dataDto']['idCultivo'] ?? null;

                return Http::response($this->respuestaMapa(
                    $idCultivo ? ($this->registro['porCultivo'][$idCultivo] ?? []) : $this->registro['todos']
                ));
            });
        }

        $this->registro = ['todos' => $todos, 'porCultivo' => $porCultivo];
    }

    private function producto(array $atributos): ProductoFitosanitario
    {
        return ProductoFitosanitario::create($atributos + ['vigente' => true]);
    }

    // ── importación del registro ─────────────────────────────────────────────

    public function test_importa_el_registro_del_mapa_con_los_cultivos_autorizados(): void
    {
        $azufre = $this->productoMapa(114534, 'ACOIDAL WG', '25904', 'AZUFRE 80% [WG] P/P');
        $cobre  = $this->productoMapa(106612, 'AFROCOBRE M', '12507', 'OXICLORURO DE COBRE 50% [WP] P/P');
        $this->fakeRegistro([$azufre, $cobre], [2014 => [$azufre, $cobre], 1974 => [$cobre]]);

        $resultado = app(ImportadorFitosanitarios::class)->importar();

        $this->assertSame(['recibidos' => 2, 'nuevos' => 2, 'actualizados' => 0, 'cancelados' => 0], $resultado);
        $p = ProductoFitosanitario::firstWhere('mapa_id', 106612);
        $this->assertSame('AFROCOBRE M', $p->nombre);
        $this->assertSame('12507', $p->numero_registro);
        $this->assertSame('OXICLORURO DE COBRE 50% [WP] P/P', $p->ingrediente_activo);
        $this->assertSame('2028-10-15', $p->fecha_caducidad->toDateString());
        $this->assertSame(['vid', 'olivo'], $p->cultivos);
        $this->assertNull($p->user_id);
        $this->assertSame(['vid'], ProductoFitosanitario::firstWhere('mapa_id', 114534)->cultivos);

        Http::assertSent(fn (Request $r) => $r->url() === ImportadorFitosanitarios::URL_EXPORTACION
            && $r->data()['dataDto']['idEstado'] == 1);
    }

    public function test_reimportar_actualiza_y_da_de_baja_los_que_dejan_de_estar_vigentes(): void
    {
        $azufre = $this->productoMapa(114534, 'ACOIDAL WG', '25904', 'AZUFRE 80% [WG] P/P');
        $cobre  = $this->productoMapa(106612, 'AFROCOBRE M', '12507', 'OXICLORURO DE COBRE 50% [WP] P/P');
        $this->fakeRegistro([$azufre, $cobre]);
        app(ImportadorFitosanitarios::class)->importar();

        $this->fakeRegistro([['Nombre' => 'ACOIDAL WG NUEVO'] + $azufre]);
        $resultado = app(ImportadorFitosanitarios::class)->importar();

        $this->assertSame(['recibidos' => 1, 'nuevos' => 0, 'actualizados' => 1, 'cancelados' => 1], $resultado);
        $this->assertSame('ACOIDAL WG NUEVO', ProductoFitosanitario::firstWhere('mapa_id', 114534)->nombre);
        $this->assertFalse(ProductoFitosanitario::firstWhere('mapa_id', 106612)->vigente);
        $this->assertSame(2, ProductoFitosanitario::count());
    }

    public function test_enlaza_por_numero_de_registro_los_productos_dados_de_alta_a_mano(): void
    {
        $anterior = $this->producto(['nombre' => 'Azufre', 'numero_registro' => '25904', 'plazo_seguridad_dias' => 5]);
        $this->fakeRegistro([$this->productoMapa(114534, 'ACOIDAL WG', '25904', 'AZUFRE 80% [WG] P/P')]);

        app(ImportadorFitosanitarios::class)->importar();

        $anterior->refresh();
        $this->assertSame(1, ProductoFitosanitario::count());
        $this->assertSame(114534, $anterior->mapa_id);
        $this->assertSame('ACOIDAL WG', $anterior->nombre);
        $this->assertSame(5, $anterior->plazo_seguridad_dias);
    }

    public function test_una_respuesta_vacia_no_da_de_baja_el_catalogo(): void
    {
        $existente = $this->producto(['nombre' => 'ACOIDAL WG', 'mapa_id' => 114534]);
        $this->fakeRegistro([]);

        try {
            app(ImportadorFitosanitarios::class)->importar();
            $this->fail('Debería haber fallado');
        } catch (RuntimeException) {
        }

        $this->assertTrue($existente->fresh()->vigente);
        $this->artisan('fitosanitarios:importar')->assertFailed();
    }

    // ── catálogo ─────────────────────────────────────────────────────────────

    public function test_el_catalogo_muestra_el_registro_y_los_productos_propios_pero_no_los_ajenos(): void
    {
        $this->producto(['nombre' => 'ACOIDAL WG', 'mapa_id' => 1, 'numero_registro' => '25904', 'cultivos' => ['vid']]);
        $this->producto(['nombre' => 'Caldo bordelés casero', 'user_id' => $this->user->id]);
        $this->producto(['nombre' => 'Producto del vecino', 'user_id' => User::factory()->create()->id]);
        $this->producto(['nombre' => 'CANCELADO SC', 'mapa_id' => 2, 'vigente' => false]);

        $this->actingAs($this->user)->get(route('tratamientos.productos.index'))
            ->assertOk()
            ->assertSee('ACOIDAL WG')->assertSee('Caldo bordelés casero')
            ->assertDontSee('Producto del vecino')->assertDontSee('CANCELADO SC');

        $this->actingAs($this->user)->get(route('tratamientos.productos.index', ['q' => '25904']))
            ->assertSee('ACOIDAL WG')->assertDontSee('Caldo bordelés casero');

        $this->actingAs($this->user)->get(route('tratamientos.productos.index', ['cancelados' => 1]))
            ->assertSee('CANCELADO SC');
    }

    public function test_alta_y_edicion_de_un_producto_propio(): void
    {
        $this->actingAs($this->user)->post(route('tratamientos.productos.store'), [
            'nombre' => 'Caldo bordelés', 'numero_registro' => 'ES-00999', 'plazo_seguridad_dias' => 15,
        ])->assertRedirect(route('tratamientos.productos.index', ['origen' => 'propios']));

        $producto = ProductoFitosanitario::sole();
        $this->assertSame($this->user->id, $producto->user_id);
        $this->assertNull($producto->cultivos);

        $this->actingAs($this->user)->put(route('tratamientos.productos.update', $producto), [
            'nombre' => 'Caldo bordelés 20%', 'plazo_seguridad_dias' => 21,
        ])->assertRedirect();
        $this->assertSame(21, $producto->fresh()->plazo_seguridad_dias);
    }

    public function test_alta_desde_un_tratamiento_vuelve_al_formulario_con_el_producto_elegido(): void
    {
        $this->actingAs($this->user)->get(route('tratamientos.productos.create', ['parcela_id' => $this->vina->id]))
            ->assertOk()->assertSee('name="parcela_id"', false);

        $respuesta = $this->actingAs($this->user)->post(route('tratamientos.productos.store'), [
            'nombre' => 'Caldo bordelés', 'parcela_id' => $this->vina->id,
        ]);

        $respuesta->assertRedirect(route('tratamientos.create', ['parcela' => $this->vina, 'producto' => ProductoFitosanitario::sole()->id]));
    }

    public function test_no_se_pueden_modificar_productos_del_registro_ni_ajenos(): void
    {
        $registro = $this->producto(['nombre' => 'ACOIDAL WG', 'mapa_id' => 1]);
        $ajeno = $this->producto(['nombre' => 'Del vecino', 'user_id' => User::factory()->create()->id]);

        foreach ([$registro, $ajeno] as $p) {
            $this->actingAs($this->user)->get(route('tratamientos.productos.edit', $p))->assertForbidden();
            $this->actingAs($this->user)->put(route('tratamientos.productos.update', $p), ['nombre' => 'X'])->assertForbidden();
            $this->actingAs($this->user)->delete(route('tratamientos.productos.destroy', $p))->assertForbidden();
        }
        $this->assertSame(2, ProductoFitosanitario::count());
    }

    public function test_no_se_elimina_un_producto_propio_con_tratamientos(): void
    {
        $propio = $this->producto(['nombre' => 'Caldo bordelés', 'user_id' => $this->user->id]);
        Tratamiento::create([
            'parcela_id' => $this->vina->id, 'producto_id' => $propio->id, 'user_id' => $this->user->id,
            'fecha' => '2026-06-01', 'dosis_l_ha' => 2,
        ]);

        $this->actingAs($this->user)->delete(route('tratamientos.productos.destroy', $propio))
            ->assertSessionHas('error');
        $this->assertModelExists($propio);
    }

    // ── tratamientos ─────────────────────────────────────────────────────────

    public function test_desde_la_lista_de_tratamientos_se_elige_la_parcela_para_uno_nuevo(): void
    {
        $this->actingAs($this->user)->get(route('tratamientos.index'))
            ->assertOk()
            ->assertSee('+ Nuevo tratamiento en…')
            ->assertSee(route('tratamientos.create', $this->vina), false);

        $this->actingAs($this->user)->get(route('vinedo.fincas.show', $this->finca))
            ->assertOk()
            ->assertSee('+ Tratamiento')
            ->assertSee(route('tratamientos.create', $this->vina), false);
    }

    public function test_el_formulario_de_tratamiento_ofrece_los_productos_autorizados_para_el_cultivo(): void
    {
        $this->producto(['nombre' => 'PARA VIÑA', 'mapa_id' => 1, 'cultivos' => ['vid']]);
        $this->producto(['nombre' => 'SOLO OLIVO', 'mapa_id' => 2, 'cultivos' => ['olivo']]);
        $this->producto(['nombre' => 'VIÑA CANCELADO', 'mapa_id' => 3, 'cultivos' => ['vid'], 'vigente' => false]);
        $this->producto(['nombre' => 'MI PRODUCTO', 'user_id' => $this->user->id]);
        $this->producto(['nombre' => 'DEL VECINO', 'user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)->get(route('tratamientos.create', $this->vina))
            ->assertOk()
            ->assertSee('PARA VIÑA')->assertSee('MI PRODUCTO')
            ->assertDontSee('SOLO OLIVO')->assertDontSee('VIÑA CANCELADO')->assertDontSee('DEL VECINO');
    }

    public function test_no_se_puede_registrar_un_tratamiento_con_un_producto_ajeno(): void
    {
        $ajeno = $this->producto(['nombre' => 'Del vecino', 'user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $ajeno->id, 'fecha' => '2026-06-01', 'dosis_l_ha' => 2,
        ])->assertSessionHasErrors('producto_id');

        $this->assertSame(0, Tratamiento::count());
    }

    public function test_el_plazo_de_seguridad_anotado_en_el_tratamiento_prevalece_sobre_el_del_producto(): void
    {
        $producto = $this->producto(['nombre' => 'ACOIDAL WG', 'mapa_id' => 1, 'cultivos' => ['vid']]);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $producto->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2, 'plazo_seguridad_dias' => 5,
        ])->assertRedirect();

        $tratamiento = Tratamiento::sole();
        $this->assertSame('2026-10-06', $tratamiento->fechaFinalPlazoSeguridad()->toDateString());
        $this->assertDatabaseHas('alertas', ['parcela_id' => $this->vina->id, 'tipo' => 'plazo_seguridad']);

        // Se propone de nuevo en el siguiente tratamiento de la finca con ese producto
        $this->actingAs($this->user)->get(route('tratamientos.create', $this->vina))
            ->assertSee('"plazosAnteriores":{"' . $producto->id . '":5}', false);
    }

    // ── tratamiento de la finca entera ───────────────────────────────────────

    private function olivar(): Parcela
    {
        return Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Olivar', 'uso' => 'Olivar', 'superficie_ha' => 3, 'agregado' => 0,
        ]);
    }

    public function test_la_ficha_de_la_finca_ofrece_tratar_toda_la_finca_o_elegir_parcelas(): void
    {
        $this->actingAs($this->user)->get(route('vinedo.fincas.show', $this->finca))
            ->assertOk()
            ->assertSeeInOrder(['Tratar toda la finca', 'Elegir parcelas'])
            ->assertSee(route('tratamientos.finca.create', $this->finca), false)
            ->assertSee(route('tratamientos.finca.create', [$this->finca, 'elegir' => 1]), false);
    }

    public function test_el_formulario_de_la_finca_marca_todas_las_parcelas_salvo_al_elegir(): void
    {
        $this->olivar();

        $todas = $this->actingAs($this->user)->get(route('tratamientos.finca.create', $this->finca))
            ->assertOk()->assertSee('Todas las parcelas de la finca')->getContent();
        $this->assertSame(2, substr_count($todas, 'checked>'));
        $this->assertStringContainsString('id="parcelas-lista" class="hidden', $todas);

        $elegir = $this->actingAs($this->user)->get(route('tratamientos.finca.create', [$this->finca, 'elegir' => 1]))
            ->assertOk()->getContent();
        $this->assertSame(0, substr_count($elegir, 'checked>'));
        $this->assertStringContainsString('id="parcelas-resumen" class="hidden', $elegir);
    }

    public function test_tratar_la_finca_registra_un_tratamiento_en_cada_parcela(): void
    {
        $olivar = $this->olivar();
        $cobre = $this->producto(['nombre' => 'COBRE', 'mapa_id' => 1, 'cultivos' => ['vid', 'olivo']]);

        $this->actingAs($this->user)->post(route('tratamientos.finca.store', $this->finca), [
            'parcelas' => [$this->vina->id, $olivar->id], 'producto_id' => $cobre->id,
            'fecha' => '2026-10-01', 'dosis_l_ha' => 2, 'plazo_seguridad_dias' => 15, 'aplicador_ropo' => '0145-B-1234',
        ])->assertRedirect(route('vinedo.fincas.show', $this->finca))
            ->assertSessionHas('success', 'Tratamiento registrado en 2 parcelas.');

        $tratamientos = Tratamiento::orderBy('parcela_id')->get();
        $this->assertEquals([$this->vina->id, $olivar->id], $tratamientos->pluck('parcela_id')->all());
        $this->assertTrue($tratamientos->every(fn ($t) => $t->superficie_tratada_ha === null
            && $t->plazo_seguridad_dias === 15 && $t->aplicador_ropo === '0145-B-1234'));
        // Una alerta de plazo de seguridad por parcela
        $this->assertDatabaseCount('alertas', 2);
    }

    public function test_no_se_trata_una_parcela_con_un_producto_no_autorizado_para_su_cultivo(): void
    {
        $olivar = $this->olivar();
        $azufre = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);

        $this->actingAs($this->user)->post(route('tratamientos.finca.store', $this->finca), [
            'parcelas' => [$this->vina->id, $olivar->id], 'producto_id' => $azufre->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2,
        ])->assertSessionHasErrors(['parcelas' => 'AZUFRE no está autorizado en el Registro del MAPA para: Olivar (Olivar).']);

        $this->assertSame(0, Tratamiento::count());
    }

    public function test_no_se_pueden_tratar_parcelas_de_otra_finca(): void
    {
        $otra = User::factory()->create();
        $fincaAjena = Finca::create(['user_id' => $otra->id, 'provincia_cod' => 28, 'municipio_cod' => 79]);
        $ajena = Parcela::create(['finca_id' => $fincaAjena->id, 'nombre' => 'Ajena', 'uso' => 'Viña en vaso', 'superficie_ha' => 1, 'agregado' => 0]);
        $producto = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);
        $datos = ['producto_id' => $producto->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2];

        $this->actingAs($this->user)->post(route('tratamientos.finca.store', $this->finca), $datos + ['parcelas' => [$ajena->id]])
            ->assertSessionHasErrors('parcelas.0');
        $this->actingAs($this->user)->get(route('tratamientos.finca.create', $fincaAjena))->assertForbidden();
        $this->actingAs($this->user)->post(route('tratamientos.finca.store', $fincaAjena), $datos + ['parcelas' => [$ajena->id]])
            ->assertForbidden();

        $this->assertSame(0, Tratamiento::count());
    }

    public function test_alta_de_producto_desde_el_tratamiento_de_la_finca_vuelve_a_el(): void
    {
        $this->actingAs($this->user)->post(route('tratamientos.productos.store'), [
            'nombre' => 'Caldo bordelés', 'finca_id' => $this->finca->id,
        ])->assertRedirect(route('tratamientos.finca.create', [$this->finca, 'producto' => ProductoFitosanitario::sole()->id]));
    }

    // ── precio y coste ───────────────────────────────────────────────────────

    public function test_un_tratamiento_con_precio_anota_el_coste_y_recuerda_el_precio(): void
    {
        $azufre = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $azufre->id, 'fecha' => '2026-06-01', 'dosis_l_ha' => 2, 'precio_unitario' => 3.5,
        ])->assertRedirect();

        $coste = Coste::sole();
        $this->assertSame('14.00', $coste->importe);   // 2 l/ha × 2 ha × 3,50 €/l
        $this->assertSame(Tratamiento::sole()->id, $coste->tratamiento_id);
        $this->assertSame($this->vina->id, $coste->parcela_id);
        $this->assertSame('Productos fitosanitarios', $coste->categoria->nombre);
        $this->assertSame('2026-06-01', $coste->fecha->toDateString());
        $this->assertSame('AZUFRE: 2 l/ha × 2 ha × 3,50 €/l', $coste->descripcion);
        $this->assertSame('3.50', Tratamiento::sole()->precio_unitario);

        // El siguiente tratamiento propone ese precio
        $this->actingAs($this->user)->get(route('tratamientos.create', $this->vina))
            ->assertSee('"precio":"3.50"', false);
    }

    public function test_el_coste_usa_la_superficie_tratada(): void
    {
        $azufre = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $azufre->id, 'fecha' => '2026-06-01', 'dosis_l_ha' => 2,
            'superficie_tratada_ha' => 0.5, 'precio_unitario' => 10,
        ]);

        $this->assertSame('10.00', Coste::sole()->importe);
    }

    public function test_tratar_la_finca_anota_el_coste_de_cada_parcela(): void
    {
        $olivar = $this->olivar();
        $cobre = $this->producto(['nombre' => 'COBRE', 'mapa_id' => 1, 'cultivos' => ['vid', 'olivo']]);

        $this->actingAs($this->user)->post(route('tratamientos.finca.store', $this->finca), [
            'parcelas' => [$this->vina->id, $olivar->id], 'producto_id' => $cobre->id,
            'fecha' => '2026-06-01', 'dosis_l_ha' => 2, 'precio_unitario' => 5,
        ]);

        $this->assertEquals(
            [$this->vina->id => '20.00', $olivar->id => '30.00'],
            Coste::orderBy('parcela_id')->pluck('importe', 'parcela_id')->all()
        );
    }

    public function test_sin_precio_no_se_anota_coste_y_borrar_el_tratamiento_borra_su_coste(): void
    {
        $azufre = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);
        $datos = ['producto_id' => $azufre->id, 'fecha' => '2026-06-01', 'dosis_l_ha' => 2];

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), $datos);
        $this->assertSame(0, Coste::count());

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), $datos + ['precio_unitario' => 4]);
        $conCoste = Tratamiento::whereNotNull('precio_unitario')->sole();
        $this->assertSame(1, Coste::count());

        $this->actingAs($this->user)->delete(route('tratamientos.destroy', $conCoste))->assertRedirect();
        $this->assertSame(0, Coste::count());
    }

    public function test_cada_usuario_tiene_su_precio_de_los_productos_del_registro(): void
    {
        $azufre = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);
        $otro = User::factory()->create();

        $this->actingAs($this->user)->put(route('tratamientos.productos.precio', $azufre), ['precio' => '4.25'])
            ->assertSessionHas('success');
        $this->actingAs($otro)->put(route('tratamientos.productos.precio', $azufre), ['precio' => '6']);

        $this->assertSame('4.25', $azufre->precios()->where('user_id', $this->user->id)->value('precio'));
        $this->actingAs($this->user)->get(route('tratamientos.productos.index'))->assertSee('value="4.25"', false);

        // Vacío lo borra
        $this->actingAs($this->user)->put(route('tratamientos.productos.precio', $azufre), ['precio' => '']);
        $this->assertNull($azufre->precios()->where('user_id', $this->user->id)->value('precio'));
        $this->assertSame('6.00', $azufre->precios()->where('user_id', $otro->id)->value('precio'));
    }

    public function test_no_se_pone_precio_a_productos_propios_de_otro_usuario(): void
    {
        $ajeno = $this->producto(['nombre' => 'Del vecino', 'user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)->put(route('tratamientos.productos.precio', $ajeno), ['precio' => 3])
            ->assertForbidden();
    }

    public function test_el_producto_propio_guarda_su_precio(): void
    {
        $this->actingAs($this->user)->post(route('tratamientos.productos.store'), ['nombre' => 'Caldo bordelés', 'precio' => '7.5']);
        $producto = ProductoFitosanitario::sole();

        $this->assertSame('7.50', $producto->precios()->value('precio'));
        $this->actingAs($this->user)->get(route('tratamientos.productos.edit', $producto))->assertSee('value="7.50"', false);
    }

    // ── corregir un tratamiento ──────────────────────────────────────────────

    private function tratamientoRegistrado(array $extra = []): Tratamiento
    {
        $producto = $this->producto(['nombre' => 'AZUFRE', 'mapa_id' => 1, 'cultivos' => ['vid']]);
        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), $extra + [
            'producto_id' => $producto->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2,
            'plazo_seguridad_dias' => 5, 'precio_unitario' => 3, 'motivo' => 'Oídio',
        ]);

        return Tratamiento::sole();
    }

    public function test_el_formulario_de_edicion_trae_los_datos_del_tratamiento(): void
    {
        $t = $this->tratamientoRegistrado(['eficacia' => 'buena']);

        $this->actingAs($this->user)->get(route('tratamientos.edit', $t))
            ->assertOk()
            ->assertSee('Editar tratamiento')
            ->assertSee('value="2026-10-01"', false)
            ->assertSee('Oídio')
            ->assertSee('value="buena" selected', false)
            ->assertSee(route('tratamientos.update', $t), false);
    }

    public function test_corregir_un_tratamiento_recalcula_coste_y_alertas(): void
    {
        $t = $this->tratamientoRegistrado();
        $this->assertSame('12.00', Coste::sole()->importe);     // 2 × 2 ha × 3
        $this->assertDatabaseHas('alertas', ['clave' => "plazo_seguridad:{$t->id}"]);

        $this->actingAs($this->user)->put(route('tratamientos.update', $t), [
            'producto_id' => $t->producto_id, 'fecha' => '2026-10-02', 'dosis_l_ha' => 3,
            'plazo_seguridad_dias' => 21, 'precio_unitario' => 4, 'motivo' => 'Mildiu', 'eficacia' => 'regular',
        ])->assertRedirect(route('vinedo.parcelas.show', $this->vina));

        $t->refresh();
        $this->assertSame('Mildiu', $t->motivo);
        $this->assertSame('regular', $t->eficacia);
        $this->assertSame('2026-10-23', $t->fechaFinalPlazoSeguridad()->toDateString());
        $this->assertSame('24.00', Coste::sole()->importe);     // 3 × 2 ha × 4
        $this->assertSame(1, Tratamiento::count());
        $this->assertStringContainsString('23/10/2026', \App\Modules\Alertas\Models\Alerta::where('clave', "plazo_seguridad:{$t->id}")->sole()->mensaje);
    }

    public function test_quitar_el_precio_al_corregir_borra_el_coste(): void
    {
        $t = $this->tratamientoRegistrado();

        $this->actingAs($this->user)->put(route('tratamientos.update', $t), [
            'producto_id' => $t->producto_id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2, 'precio_unitario' => '',
        ]);

        $this->assertSame(0, Coste::count());
        $this->assertNull($t->fresh()->precio_unitario);
    }

    public function test_no_se_corrigen_tratamientos_ajenos(): void
    {
        $t = $this->tratamientoRegistrado();
        $otro = User::factory()->create();

        $this->actingAs($otro)->get(route('tratamientos.edit', $t))->assertForbidden();
        $this->actingAs($otro)->put(route('tratamientos.update', $t), [
            'producto_id' => $t->producto_id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 9,
        ])->assertForbidden();
        $this->assertSame('2.0000', $t->fresh()->dosis_l_ha);
    }

    // ── herbáceos de secano ──────────────────────────────────────────────────

    private function secano(?string $variedad): Parcela
    {
        $variedadId = $variedad ? Variedad::create(['nombre' => $variedad, 'cultivo' => 'herbaceo'])->id : null;

        return Parcela::create([
            'finca_id' => $this->finca->id, 'nombre' => 'Secano', 'uso' => 'Secano', 'superficie_ha' => 5,
            'agregado' => 0, 'variedad_id' => $variedadId,
        ]);
    }

    public function test_el_importador_clasifica_los_herbaceos(): void
    {
        $herbicida = $this->productoMapa(1, 'HERBICIDA CEREAL', 'ES-1', 'X 10% [SC] P/V');
        $this->fakeRegistro([$herbicida], [2035 => [$herbicida], 2034 => [$herbicida]]);

        app(ImportadorFitosanitarios::class)->importar();

        $this->assertSame(['trigo', 'cebada'], ProductoFitosanitario::sole()->cultivos);
    }

    public function test_en_secano_se_filtra_por_la_variedad(): void
    {
        $cebada = $this->secano('Cebada');
        $this->producto(['nombre' => 'PARA CEBADA', 'mapa_id' => 1, 'cultivos' => ['cebada', 'trigo']]);
        $this->producto(['nombre' => 'SOLO GIRASOL', 'mapa_id' => 2, 'cultivos' => ['girasol']]);

        $this->actingAs($this->user)->get(route('tratamientos.create', $cebada))
            ->assertOk()
            ->assertSee('PARA CEBADA')->assertDontSee('SOLO GIRASOL')
            ->assertSee('autoriza para cebada');
    }

    public function test_un_secano_sin_variedad_no_se_filtra(): void
    {
        $secano = $this->secano(null);
        $this->producto(['nombre' => 'PARA CEBADA', 'mapa_id' => 1, 'cultivos' => ['cebada']]);
        $this->producto(['nombre' => 'SOLO GIRASOL', 'mapa_id' => 2, 'cultivos' => ['girasol']]);

        $this->actingAs($this->user)->get(route('tratamientos.create', $secano))
            ->assertSee('PARA CEBADA')->assertSee('SOLO GIRASOL')
            ->assertSee('Comprueba en la etiqueta');
    }

    public function test_no_se_trata_un_secano_con_un_producto_de_otro_cultivo(): void
    {
        $cebada = $this->secano('Cebada');
        $girasol = $this->producto(['nombre' => 'SOLO GIRASOL', 'mapa_id' => 2, 'cultivos' => ['girasol']]);

        $this->actingAs($this->user)->post(route('tratamientos.finca.store', $this->finca), [
            'parcelas' => [$cebada->id], 'producto_id' => $girasol->id, 'fecha' => '2026-10-01', 'dosis_l_ha' => 2,
        ])->assertSessionHasErrors('parcelas');
    }

    // ── unidad (l o kg) ──────────────────────────────────────────────────────

    public function test_la_unidad_se_deduce_del_formulado(): void
    {
        $this->assertSame('kg', ImportadorFitosanitarios::unidadDeFormulado('AZUFRE 80% [WG] P/P'));
        $this->assertSame('kg', ImportadorFitosanitarios::unidadDeFormulado('OXICLORURO DE COBRE 50% [WP] P/P'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado('AZUFRE 80% [SC] P/V'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado('LAMBDA CIHALOTRIN 10% [CS] P/P'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado('GLIFOSATO 36% [SL] P/V'));
        $this->assertSame('l', ImportadorFitosanitarios::unidadDeFormulado(null));
    }

    public function test_el_importador_guarda_la_unidad(): void
    {
        $this->fakeRegistro([
            $this->productoMapa(1, 'ACOIDAL WG', '25904', 'AZUFRE 80% [WG] P/P'),
            $this->productoMapa(2, 'ACOIDAL 800 SC', 'ES-00489', 'AZUFRE 80% [SC] P/V'),
        ]);

        app(ImportadorFitosanitarios::class)->importar();

        $this->assertSame('kg', ProductoFitosanitario::firstWhere('mapa_id', 1)->unidad);
        $this->assertSame('l', ProductoFitosanitario::firstWhere('mapa_id', 2)->unidad);
    }

    public function test_un_producto_solido_se_dosifica_y_cuesta_por_kilo(): void
    {
        $azufre = $this->producto(['nombre' => 'ACOIDAL WG', 'mapa_id' => 1, 'cultivos' => ['vid'], 'unidad' => 'kg']);

        $this->actingAs($this->user)->post(route('tratamientos.store', $this->vina), [
            'producto_id' => $azufre->id, 'fecha' => '2026-06-01', 'dosis_l_ha' => 3, 'precio_unitario' => 2,
        ]);

        $tratamiento = Tratamiento::sole();
        $this->assertSame('kg', $tratamiento->unidad);
        $this->assertSame('kg/ha', $tratamiento->unidadDosis());
        $this->assertSame('ACOIDAL WG: 3 kg/ha × 2 ha × 2,00 €/kg', Coste::sole()->descripcion);

        // Si el producto se reclasifica, el tratamiento conserva su unidad
        $azufre->update(['unidad' => 'l']);
        $this->assertSame('kg/ha', $tratamiento->fresh()->unidadDosis());

        $this->actingAs($this->user)->get(route('tratamientos.index'))->assertSee('kg/ha');
    }

    public function test_el_producto_propio_lleva_su_unidad(): void
    {
        $this->actingAs($this->user)->post(route('tratamientos.productos.store'), ['nombre' => 'Azufre en polvo', 'unidad' => 'kg']);
        $this->assertSame('kg', ProductoFitosanitario::sole()->unidad);

        $this->actingAs($this->user)->post(route('tratamientos.productos.store'), ['nombre' => 'Otro', 'unidad' => 'toneladas'])
            ->assertSessionHasErrors('unidad');
    }

    public function test_el_cuaderno_avisa_de_productos_no_autorizados_para_el_cultivo(): void
    {
        $soloOlivo = $this->producto(['nombre' => 'SOLO OLIVO', 'mapa_id' => 2, 'numero_registro' => 'ES-1', 'cultivos' => ['olivo']]);
        Tratamiento::create([
            'parcela_id' => $this->vina->id, 'producto_id' => $soloOlivo->id, 'user_id' => $this->user->id,
            'fecha' => '2026-06-01', 'dosis_l_ha' => 2, 'aplicador_ropo' => '0145-B-1234',
        ]);

        $this->actingAs($this->user)->get(route('cuaderno.index'))
            ->assertSee('1 tratamiento usa un producto que el Registro de Productos Fitosanitarios no autoriza para el cultivo de la parcela.');
    }
}
