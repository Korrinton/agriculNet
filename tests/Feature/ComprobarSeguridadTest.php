<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprobarSeguridadTest extends TestCase
{
    use RefreshDatabase;

    /** Configuración de producción correcta; cada test estropea una cosa. */
    private function configuracionSegura(): void
    {
        config([
            'app.env' => 'production', 'app.debug' => false, 'app.key' => 'base64:' . base64_encode(random_bytes(32)),
            'app.url' => 'https://agriculnet.es', 'session.secure' => true, 'app.copias_cifradas' => true,
            'database.connections.pgsql.password' => 'una-contraseña-larga', 'logging.channels.single.level' => 'notice',
            'aemet.api_key' => 'clave', 'mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.proveedor.es',
        ]);
        if (! User::where('is_admin', true)->exists()) {
            User::factory()->create(['email' => 'admin@agriculnet.es'])->forceFill(['is_admin' => true])->save();
        }
    }

    public function test_con_la_configuracion_de_produccion_pasa(): void
    {
        $this->configuracionSegura();

        $this->artisan('seguridad:comprobar')->expectsOutputToContain('Configuración lista para producción')->assertSuccessful();
    }

    public function test_el_usuario_de_pruebas_impide_pasar_a_produccion(): void
    {
        $this->configuracionSegura();
        User::factory()->create(['email' => 'test@example.com']);

        $this->artisan('seguridad:comprobar')->expectsOutputToContain('Existe el usuario de pruebas test@example.com')->assertFailed();
    }

    public function test_detecta_cada_problema_grave(): void
    {
        $problemas = [
            'app.debug'                           => [true, 'APP_DEBUG=true'],
            'app.url'                             => ['http://agriculnet.es', 'APP_URL no usa https://'],
            'session.secure'                      => [null, 'SESSION_SECURE_COOKIE'],
            'app.copias_cifradas'                 => [false, 'Falta BACKUP_PASSPHRASE'],
            'database.connections.pgsql.password' => ['secret', 'DB_PASSWORD'],
            'app.key'                             => [null, 'Falta APP_KEY'],
        ];

        foreach ($problemas as $clave => [$valor, $mensaje]) {
            $this->configuracionSegura();
            config([$clave => $valor]);

            $this->artisan('seguridad:comprobar')->expectsOutputToContain($mensaje)->assertFailed();
        }
    }

    public function test_los_avisos_no_impiden_arrancar(): void
    {
        $this->configuracionSegura();
        config(['aemet.api_key' => null, 'logging.channels.single.level' => 'debug']);

        $this->artisan('seguridad:comprobar')
            ->expectsOutputToContain('Falta AEMET_API_KEY')
            ->expectsOutputToContain('LOG_LEVEL')
            ->assertSuccessful();
    }
}
