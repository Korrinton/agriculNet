<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

/**
 * Revisa la configuración antes de producción. En producción lo ejecuta el arranque del
 * contenedor (docker/php/entrypoint.sh) y, si hay errores, la aplicación no arranca.
 */
class ComprobarSeguridad extends Command
{
    protected $signature   = 'seguridad:comprobar';
    protected $description = 'Comprueba que la configuración es segura para producción (HTTPS, depuración, copias cifradas, usuario de pruebas…)';

    /** Cuentas de desarrollo que no pueden existir en producción. */
    public const USUARIOS_DE_PRUEBAS = ['test@example.com'];

    private const CONTRASENAS_DE_EJEMPLO = ['', 'secret', 'password', 'agrario'];

    public function handle(): int
    {
        $errores = [];
        $avisos = [];
        $comprobar = function (bool $ok, string $problema, bool $grave = true) use (&$errores, &$avisos) {
            if (! $ok) {
                $grave ? $errores[] = $problema : $avisos[] = $problema;
            }
        };

        $comprobar(config('app.env') === 'production', 'APP_ENV no es «production».', false);
        $comprobar(! config('app.debug'), 'APP_DEBUG=true: un error mostraría código, consultas y datos internos.');
        $comprobar(filled(config('app.key')), 'Falta APP_KEY (php artisan key:generate).');
        $comprobar(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL no usa https://.');
        $comprobar((bool) config('session.secure'), 'SESSION_SECURE_COOKIE no está a true: la cookie de sesión podría viajar sin cifrar.');
        $comprobar(! in_array((string) config('database.connections.pgsql.password'), self::CONTRASENAS_DE_EJEMPLO, true),
            'La contraseña de la base de datos (DB_PASSWORD) es la de ejemplo o está vacía.');
        $comprobar((bool) config('app.copias_cifradas'), 'Falta BACKUP_PASSPHRASE: las copias de seguridad, con los NIF de todos los usuarios, irían sin cifrar.');
        $comprobar(! in_array(config('logging.channels.single.level'), ['debug', 'info'], true),
            'LOG_LEVEL es debug o info: los logs crecen y pueden recoger datos de más (usa notice para conservar las acciones del backoffice).', false);
        $comprobar(filled(config('aemet.api_key')), 'Falta AEMET_API_KEY: no habrá datos meteorológicos ni alertas de helada.', false);
        $comprobar(! in_array(config('mail.mailers.smtp.host'), ['mailpit', '127.0.0.1', 'localhost'], true) || config('mail.default') !== 'smtp',
            'El correo apunta a un servidor de pruebas: no llegarán los emails de recuperación de contraseña.', false);

        try {
            $pruebas = User::whereIn('email', self::USUARIOS_DE_PRUEBAS)->pluck('email');
            $comprobar($pruebas->isEmpty(), 'Existe el usuario de pruebas ' . $pruebas->join(', ') . ': bórralo (es administrador y su contraseña es conocida).');
            $comprobar(User::where('is_admin', true)->exists(), 'No hay ningún administrador (php artisan usuarios:admin email).', false);
        } catch (Throwable $e) {
            $comprobar(false, "No se puede consultar la base de datos: {$e->getMessage()}");
        }

        foreach ($errores as $error) {
            $this->line("  <fg=red>✘</> {$error}");
        }
        foreach ($avisos as $aviso) {
            $this->line("  <fg=yellow>!</> {$aviso}");
        }

        if ($errores) {
            $this->error(count($errores) . ' ' . (count($errores) === 1 ? 'problema impide' : 'problemas impiden') . ' pasar a producción.');

            return self::FAILURE;
        }

        $this->info($avisos ? 'Sin problemas graves (revisa los avisos).' : 'Configuración lista para producción.');

        return self::SUCCESS;
    }
}
