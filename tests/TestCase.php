<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Se ejecuta antes que RefreshDatabase: si la configuración no apunta a una
     * base de datos de tests, se aborta en vez de borrar la de desarrollo.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $conexion = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$conexion}.database");

        if (!str_ends_with((string) $database, '_test')) {
            throw new RuntimeException(
                "Tests abortados: la base de datos configurada es '{$database}', no una de tests (*_test). Revisa phpunit.xml."
            );
        }

        // Caché, colas y sesiones compartidas con desarrollo (Redis) mezclarían datos
        // y los jobs de los tests los ejecutaría el queue-worker contra la BD real
        foreach (['cache.default' => 'array', 'queue.default' => 'sync', 'session.driver' => 'array', 'mail.default' => 'array'] as $clave => $esperado) {
            if ($app['config']->get($clave) !== $esperado) {
                throw new RuntimeException(
                    "Tests abortados: {$clave} es '{$app['config']->get($clave)}' y debería ser '{$esperado}'. Revisa phpunit.xml."
                );
            }
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ningún test debe llamar a AEMET o SIGPAC de verdad: hay que usar Http::fake()
        Http::preventStrayRequests();
    }
}
