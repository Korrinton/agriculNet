<?php

namespace Tests\Feature\Meteorologia;

use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportarEstacionesAemetTest extends TestCase
{
    use RefreshDatabase;

    public function test_asigna_la_provincia_aunque_lleve_tildes_o_enie(): void
    {
        config(['aemet.api_key' => 'clave-de-prueba']);

        $inventario = [
            ['indicativo' => '1387',  'nombre' => 'A CORUÑA',    'provincia' => 'A CORUÑA',    'latitud' => '432158N', 'longitud' => '082517W'],
            ['indicativo' => '3260B', 'nombre' => 'TOLEDO',      'provincia' => 'TOLEDO',      'latitud' => '395302N', 'longitud' => '040254W'],
            ['indicativo' => '2867',  'nombre' => 'SALAMANCA',   'provincia' => 'SALAMANCA',   'latitud' => '405734N', 'longitud' => '053005W'],
            ['indicativo' => '9091R', 'nombre' => 'VITORIA',     'provincia' => 'ARABA/ALAVA', 'latitud' => '425255N', 'longitud' => '024408W'],
            ['indicativo' => '3469A', 'nombre' => 'CÁCERES',     'provincia' => 'CÁCERES',     'latitud' => '392818N', 'longitud' => '062020W'],
        ];

        Http::fake([
            'opendata.aemet.es/opendata/api/*' => Http::response(['estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/inv']),
            'opendata.aemet.es/opendata/sh/inv' => Http::response(mb_convert_encoding(json_encode($inventario), 'ISO-8859-1', 'UTF-8')),
        ]);

        $this->artisan('aemet:importar-estaciones')->assertSuccessful();

        $this->assertSame(
            ['1387' => 15, '2867' => 37, '3260B' => 45, '3469A' => 10, '9091R' => 1],
            EstacionMeteorologica::orderBy('codigo_externo')->pluck('provincia_cod', 'codigo_externo')->all(),
        );
    }
}
