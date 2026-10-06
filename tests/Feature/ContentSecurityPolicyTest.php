<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_paginas_llevan_la_politica(): void
    {
        $politica = $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $politica);
        $this->assertStringContainsString("object-src 'none'", $politica);
        $this->assertStringContainsString("frame-ancestors 'self'", $politica);
        // Lo que de verdad frena un XSS: no se ejecuta ningún script en línea
        $this->assertMatchesRegularExpression("/script-src [^;]*'self'/", $politica);
        $this->assertDoesNotMatchRegularExpression("/script-src [^;]*'unsafe-inline'/", $politica);

        $this->get(route('login'))->assertHeader('Content-Security-Policy', $politica);
    }

    /**
     * Con la CSP, un <script> en línea o un onclick="…" en una vista no se ejecutaría: el código va
     * en resources/js (data-* o data-pagina) y los datos en <script type="application/json">.
     */
    public function test_las_vistas_no_llevan_scripts_ni_manejadores_en_linea(): void
    {
        $problemas = [];
        foreach (File::allFiles(resource_path('views')) as $fichero) {
            $contenido = $fichero->getContents();
            $ruta = $fichero->getRelativePathname();

            preg_match_all('/<script\b(?![^>]*type="application\/json")[^>]*>/i', $contenido, $scripts);
            preg_match_all('/\son(click|change|submit|input|load|error|key\w+|focus|blur|mouse\w+)\s*=/i', $contenido, $manejadores);
            preg_match_all('/(src|href)="https?:\/\/(?!fonts\.bunny\.net)[^"]*\.(js|css)\b/i', $contenido, $externos);

            foreach ([...$scripts[0], ...$manejadores[0], ...$externos[0]] as $encontrado) {
                $problemas[] = "{$ruta}: " . trim($encontrado);
            }
        }

        $this->assertSame([], $problemas, 'Mueve este código a resources/js (ver resources/js/app.js)');
    }
}
