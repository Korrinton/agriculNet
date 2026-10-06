<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content-Security-Policy: el navegador solo ejecuta JavaScript servido por la propia aplicación.
 * Un <script> o un onerror="…" inyectados (XSS) no se ejecutan, porque no se permite 'unsafe-inline'.
 *
 * - 'unsafe-eval': Livewire 3 y Alpine evalúan sus expresiones (x-data, wire:click…) con
 *   new Function; sin modo «CSP seguro» en esta versión de Livewire, no hay alternativa.
 * - style-src 'unsafe-inline': Livewire, Alpine (x-show) y las vistas usan estilos en línea.
 * - img-src: teselas de los mapas (ortofoto del IGN y OpenStreetMap).
 * - Con `npm run dev`, el servidor de Vite (scripts, estilos y su websocket).
 *
 * Las vistas no pueden llevar <script> ni manejadores en línea: ver resources/js/app.js.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (! $respuesta->headers->has('Content-Security-Policy')) {
            $respuesta->headers->set('Content-Security-Policy', self::politica());
        }

        return $respuesta;
    }

    public static function politica(): string
    {
        $vite = self::servidorVite();
        $viteWs = $vite ? preg_replace('#^http#', 'ws', $vite) : null;

        $directivas = [
            'default-src'     => ["'self'"],
            'script-src'      => ["'self'", "'unsafe-eval'", $vite],
            'style-src'       => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net', $vite],
            'font-src'        => ["'self'", 'data:', 'https://fonts.bunny.net', $vite],
            'img-src'         => ["'self'", 'data:', 'blob:', 'https://www.ign.es', 'https://*.tile.openstreetmap.org'],
            'connect-src'     => ["'self'", $vite, $viteWs],
            'object-src'      => ["'none'"],
            'base-uri'        => ["'self'"],
            'form-action'     => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        return collect($directivas)
            ->map(fn (array $fuentes, string $directiva) => $directiva . ' ' . implode(' ', array_filter($fuentes)))
            ->join('; ');
    }

    /** Origen del servidor de desarrollo de Vite si está en marcha (fichero public/hot). */
    private static function servidorVite(): ?string
    {
        $hot = Vite::hotFile();
        if (! is_file($hot)) {
            return null;
        }
        $partes = parse_url(trim((string) file_get_contents($hot)));

        return isset($partes['scheme'], $partes['host'])
            ? $partes['scheme'] . '://' . $partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : '')
            : null;
    }
}
