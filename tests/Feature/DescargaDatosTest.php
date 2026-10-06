<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Usuarios\Services\ExportadorDatosUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\CreaUsuarioConDatos;
use Tests\TestCase;
use ZipArchive;

class DescargaDatosTest extends TestCase
{
    use CreaUsuarioConDatos, RefreshDatabase;

    /** Descarga el .zip y devuelve [datos.json decodificado, ZipArchive abierto]. */
    private function descargar(User $user): array
    {
        $respuesta = $this->actingAs($user)->get(route('profile.datos'))->assertOk();
        $this->assertStringContainsString('attachment; filename=mis-datos-ramon-garcia-', $respuesta->headers->get('Content-Disposition'));

        $fichero = tempnam(sys_get_temp_dir(), 'zip');
        copy($respuesta->baseResponse->getFile()->getPathname(), $fichero);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($fichero));

        return [json_decode($zip->getFromName('datos.json'), true), $zip];
    }

    public function test_descarga_todos_sus_datos_y_solo_los_suyos(): void
    {
        $user = $this->usuarioConDatos(['name' => 'Ramón García', 'email' => 'ramon@example.com']);
        $this->usuarioConDatos(['email' => 'otro@example.com']);

        [$datos, $zip] = $this->descargar($user);
        $tablas = $datos['tablas'];

        $this->assertSame('ramon@example.com', $tablas['cuenta'][0]['email']);
        $this->assertArrayNotHasKey('password', $tablas['cuenta'][0]);
        $this->assertArrayNotHasKey('remember_token', $tablas['cuenta'][0]);

        // Dos de cada (una por finca); de la estación manual, la suya; la de AEMET no es suya
        foreach (['fincas', 'parcelas', 'tratamientos', 'fertilizaciones', 'cosechas', 'riegos', 'observaciones_fenologicas',
            'grados_dia', 'alertas', 'productos_propios', 'precios_productos'] as $tabla) {
            $this->assertCount(2, $tablas[$tabla], $tabla);
        }
        $this->assertCount(4, $tablas['costes'], 'el seguro de cada finca y el coste de cada tratamiento');
        $this->assertCount(1, $tablas['estaciones_manuales']);
        $this->assertCount(1, $tablas['datos_meteorologicos_manuales']);

        // Con los nombres que dan sentido a los ids
        $this->assertSame('Mi caldo', $tablas['tratamientos'][0]['producto']);
        $this->assertSame('12345678Z', $tablas['tratamientos'][0]['aplicador_nif']);
        $this->assertSame('57', $tablas['observaciones_fenologicas'][0]['codigo_bbch']);
        $this->assertNotEmpty($tablas['costes'][0]['categoria']);

        // CSV para hojas de cálculo en español
        $csv = $zip->getFromName('csv/tratamientos.csv');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('aplicador_nif', strtok(substr($csv, 3), "\n"));
        $this->assertStringContainsString(';', $csv);
        $this->assertNotFalse($zip->getFromName('LEEME.txt'));
    }

    public function test_un_usuario_sin_datos_tambien_puede_descargar(): void
    {
        [$datos, $zip] = $this->descargar(User::factory()->create(['name' => 'Ramón García']));

        $this->assertSame([], $datos['tablas']['fincas']);
        $this->assertSame("\xEF\xBB\xBF", $zip->getFromName('csv/fincas.csv'));
    }

    public function test_hace_falta_iniciar_sesion(): void
    {
        $this->get(route('profile.datos'))->assertRedirect(route('login'));
    }

    /**
     * Toda tabla con parcela_id, finca_id o user_id es de un usuario: tiene que salir en la descarga
     * (o estar en esta lista de excepciones, explicando por qué).
     */
    public function test_ninguna_tabla_de_datos_de_usuario_se_queda_fuera(): void
    {
        $exportadas = [
            'users', 'fincas', 'parcelas', 'tratamientos', 'fertilizaciones', 'cosechas', 'riegos', 'registro_fenologico',
            'costes', 'grados_dia', 'alertas', 'productos_fitosanitarios', 'precios_productos_fitosanitarios',
        ];
        $excepciones = [
            'cuaderno_entradas',     // en desuso, siempre vacía
            'ejecuciones_tareas',    // registro técnico del backoffice
            'sessions',              // sesiones (las de la app van en Redis)
            'personal_access_tokens', // tokens de la API
        ];

        $conDuenio = collect(Schema::getTables())->pluck('name')->filter(fn ($tabla) => collect(['parcela_id', 'finca_id', 'user_id'])
            ->contains(fn ($columna) => Schema::hasColumn($tabla, $columna)));

        $this->assertSame([], $conDuenio->diff([...$exportadas, ...$excepciones])->values()->all(),
            'Tablas con datos de usuario que no están en ExportadorDatosUsuario');
        $this->assertCount(15, app(ExportadorDatosUsuario::class)->tablas(User::factory()->create()));
    }
}
