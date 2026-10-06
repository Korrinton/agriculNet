<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\EjecucionTarea;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Tareas programadas que se ven y se pueden lanzar desde el backoffice. Cada ejecución (la del
 * programador, la de la consola o la lanzada a mano) queda en ejecuciones_tareas a través de los
 * eventos de consola de Laravel, que se disparan en el proceso que ejecuta el comando.
 */
class Tareas
{
    /** comando => datos; los horarios son los de routes/console.php (hora de Madrid). */
    public const CATALOGO = [
        'aemet:sincronizar' => [
            'nombre'      => 'Datos de AEMET',
            'descripcion' => 'Predicción de 7 días de los municipios con fincas y datos observados de sus estaciones.',
            'horario'     => 'Cada día a las 6:30',
            'opciones'    => [],
        ],
        'grados-dia:recalcular' => [
            'nombre'      => 'Grados-día',
            'descripcion' => 'Acumulado de la campaña de todas las viñas con estación meteorológica.',
            'horario'     => 'Cada día a las 6:50',
            'opciones'    => [],
        ],
        'alertas:generar' => [
            'nombre'      => 'Alertas',
            'descripcion' => 'Fin de plazos de seguridad, heladas observadas y previstas y riesgo de mildiu.',
            'horario'     => 'Cada día a las 7:00',
            'opciones'    => [],
        ],
        'alertas:enviar' => [
            'nombre'      => 'Alertas por correo',
            'descripcion' => 'Correo a cada usuario con sus alertas nuevas, según lo que haya elegido en su perfil.',
            'horario'     => 'Cada día a las 7:10',
            'opciones'    => [],
        ],
        'fitosanitarios:importar' => [
            'nombre'      => 'Registro de fitosanitarios',
            'descripcion' => 'Sincroniza el catálogo con el Registro Oficial de Productos Fitosanitarios del MAPA.',
            'horario'     => 'Los lunes a las 5:30',
            'opciones'    => [],
        ],
        'fitosanitarios:fichas' => [
            'nombre'      => 'Fichas de fitosanitarios',
            'descripcion' => 'Lee los plazos de seguridad oficiales de las fichas PDF (150 por ejecución).',
            'horario'     => 'Cada día a las 5:45',
            'opciones'    => ['--limite' => 150],
        ],
        'aemet:importar-estaciones' => [
            'nombre'      => 'Estaciones AEMET',
            'descripcion' => 'Inventario de estaciones de AEMET para vincularlas a las fincas.',
            'horario'     => 'A mano, cuando AEMET cambie su red',
            'opciones'    => [],
        ],
    ];

    /** Para registrar quién lanzó una tarea desde el backoffice (el job corre en el mismo proceso). */
    private static ?int $lanzadaPor = null;

    /** Ejecución en curso de cada comando en este proceso, y la última que empezó. */
    private static array $enCurso = [];

    private static ?EjecucionTarea $ultima = null;

    public static function escucharEjecuciones(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $evento) {
            if (! isset(self::CATALOGO[$evento->command])) {
                return;
            }
            // Registrar nunca debe impedir que la tarea se ejecute
            try {
                self::$enCurso[$evento->command] = self::$ultima = EjecucionTarea::create([
                    'comando' => $evento->command, 'inicio' => now(), 'user_id' => self::$lanzadaPor,
                ]);
            } catch (Throwable $e) {
                Log::warning("No se pudo registrar el inicio de {$evento->command}: {$e->getMessage()}");
            }
        });

        Event::listen(CommandFinished::class, function (CommandFinished $evento) {
            $ejecucion = self::$enCurso[$evento->command] ?? null;
            unset(self::$enCurso[$evento->command]);
            try {
                $ejecucion?->update(['fin' => now(), 'codigo_salida' => $evento->exitCode]);
            } catch (Throwable $e) {
                Log::warning("No se pudo registrar el final de {$evento->command}: {$e->getMessage()}");
            }
        });
    }

    /**
     * Ejecuta una tarea del catálogo guardando su salida (la usa el job que lanza el backoffice).
     */
    public function ejecutar(string $comando, ?int $userId): EjecucionTarea
    {
        self::$lanzadaPor = $userId;
        self::$ultima = null;
        $salida = new BufferedOutput;

        try {
            $codigo = Artisan::call($comando, self::CATALOGO[$comando]['opciones'], $salida);
        } catch (Throwable $e) {
            $codigo = 1;
            $salida->writeln($e->getMessage());
        } finally {
            self::$lanzadaPor = null;
        }

        // La que registró el evento de inicio en este proceso (si no llegó a registrarse, se crea ahora)
        $ejecucion = self::$ultima
            ?? EjecucionTarea::create(['comando' => $comando, 'inicio' => now(), 'user_id' => $userId]);
        $ejecucion->update([
            'fin'           => $ejecucion->fin ?? now(),
            'codigo_salida' => $codigo,
            'salida'        => mb_substr(trim($salida->fetch()), 0, 20000) ?: null,
        ]);

        return $ejecucion;
    }

    /**
     * Las tareas del catálogo con su última ejecución y las últimas de cada una.
     *
     * @return Collection<string, array>
     */
    public function resumen(int $historial = 5): Collection
    {
        return collect(self::CATALOGO)->map(function (array $tarea, string $comando) use ($historial) {
            $ejecuciones = EjecucionTarea::with('user:id,name')->where('comando', $comando)
                ->latest('inicio')->latest('id')->limit($historial)->get();

            return $tarea + [
                'comando'     => $comando,
                'ultima'      => $ejecuciones->first(),
                'ejecuciones' => $ejecuciones,
                'ultimoExito' => EjecucionTarea::where('comando', $comando)->where('codigo_salida', 0)->max('inicio'),
            ];
        });
    }
}
