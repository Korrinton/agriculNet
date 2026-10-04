<?php

namespace App\Console\Commands;

use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Services\AemetClient;
use Illuminate\Console\Command;

class ImportarEstacionesAemet extends Command
{
    protected $signature   = 'aemet:importar-estaciones';
    protected $description = 'Descarga el inventario completo de estaciones AEMET y las sincroniza en la BD';

    /**
     * Mapeo de nombre de provincia AEMET (normalizado) → provincia_cod.
     * Se compara contra el campo "provincia" de cada estación tras normalizar
     * (mayúsculas, sin tildes, sin artículos al final como ", LA").
     */
    private const PROVINCIA_MAP = [
        'ALAVA'           => 1,  'ARABA'           => 1,
        'ALBACETE'        => 2,
        'ALICANTE'        => 3,  'ALACANT'         => 3,
        'ALMERIA'         => 4,
        'AVILA'           => 5,
        'BADAJOZ'         => 6,
        'BALEARS'         => 7,  'BALEARES'        => 7,
        'BARCELONA'       => 8,
        'BURGOS'          => 9,
        'CACERES'         => 10,
        'CADIZ'           => 11,
        'CASTELLON'       => 12, 'CASTELLO'        => 12,
        'CIUDAD REAL'     => 13,
        'CORDOBA'         => 14,
        // Claves ya normalizadas (sin tildes ni Ñ), igual que el texto con el que se comparan
        'CORUNA'          => 15, 'A CORUNA'        => 15,
        'CUENCA'          => 16,
        'GIRONA'          => 17, 'GERONA'          => 17,
        'GRANADA'         => 18,
        'GUADALAJARA'     => 19,
        'GIPUZKOA'        => 20, 'GUIPUZCOA'       => 20,
        'HUELVA'          => 21,
        'HUESCA'          => 22,
        'JAEN'            => 23,
        'LEON'            => 24,
        'LLEIDA'          => 25, 'LERIDA'          => 25,
        'RIOJA'           => 26,
        'LUGO'            => 27,
        'MADRID'          => 28,
        'MALAGA'          => 29,
        'MURCIA'          => 30,
        'NAVARRA'         => 31,
        'OURENSE'         => 32, 'ORENSE'          => 32,
        'ASTURIAS'        => 33,
        'PALENCIA'        => 34,
        'PALMAS'          => 35, 'LAS PALMAS'      => 35,
        'PONTEVEDRA'      => 36,
        'SALAMANCA'       => 37,
        'SANTA CRUZ DE TENERIFE' => 38, 'TENERIFE' => 38,
        'CANTABRIA'       => 39, 'SANTANDER'       => 39,
        'SEGOVIA'         => 40,
        'SEVILLA'         => 41,
        'SORIA'           => 42,
        'TARRAGONA'       => 43,
        'TERUEL'          => 44,
        'TOLEDO'          => 45,
        'VALENCIA'        => 46,
        'VALLADOLID'      => 47,
        'BIZKAIA'         => 48, 'VIZCAYA'         => 48,
        'ZAMORA'          => 49,
        'ZARAGOZA'        => 50,
        'CEUTA'           => 51,
        'MELILLA'         => 52,
    ];

    public function handle(AemetClient $aemet): int
    {
        if (! config('aemet.api_key')) {
            $this->error('AEMET_API_KEY no configurada en .env');
            return self::FAILURE;
        }

        $this->info('Descargando inventario de estaciones AEMET…');

        try {
            $estaciones = $aemet->get('/valores/climatologicos/inventarioestaciones/todasestaciones/');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (empty($estaciones)) {
            $this->warn('AEMET devolvió un inventario vacío.');
            return self::FAILURE;
        }

        $this->info('Estaciones recibidas: ' . count($estaciones));

        $bar = $this->output->createProgressBar(count($estaciones));
        $bar->start();

        $creadas    = 0;
        $actualizadas = 0;
        $sinProvincia = 0;

        foreach ($estaciones as $e) {
            $indicativo = $e['indicativo'] ?? null;
            if (! $indicativo) {
                $bar->advance();
                continue;
            }

            $provinciaCod = $this->resolverProvincia($e['provincia'] ?? '');
            if ($provinciaCod === null) {
                $sinProvincia++;
            }

            $latitud  = isset($e['latitud'])  ? AemetClient::dmsToDecimal($e['latitud'])  : 0;
            $longitud = isset($e['longitud']) ? AemetClient::dmsToDecimal($e['longitud']) : 0;

            $existing = EstacionMeteorologica::where('codigo_externo', $indicativo)->first();

            if ($existing) {
                $existing->update([
                    'nombre'        => $e['nombre'] ?? $indicativo,
                    'latitud'       => $latitud,
                    'longitud'      => $longitud,
                    'provincia_cod' => $provinciaCod,
                ]);
                $actualizadas++;
            } else {
                EstacionMeteorologica::create([
                    'nombre'         => $e['nombre'] ?? $indicativo,
                    'latitud'        => $latitud,
                    'longitud'       => $longitud,
                    'fuente'         => 'aemet',
                    'codigo_externo' => $indicativo,
                    'provincia_cod'  => $provinciaCod,
                ]);
                $creadas++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->table(
            ['Creadas', 'Actualizadas', 'Sin provincia'],
            [[$creadas, $actualizadas, $sinProvincia]]
        );

        return self::SUCCESS;
    }

    private function resolverProvincia(string $rawProvincia): ?int
    {
        $normalizado = $this->normalizar($rawProvincia);

        // Búsqueda exacta
        if (isset(self::PROVINCIA_MAP[$normalizado])) {
            return self::PROVINCIA_MAP[$normalizado];
        }

        // Búsqueda parcial: cualquier clave que esté contenida en el nombre
        foreach (self::PROVINCIA_MAP as $clave => $cod) {
            if (str_contains($normalizado, $clave)) {
                return $cod;
            }
        }

        return null;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtoupper(trim($texto));

        // Eliminar artículos al final: ", LA", ", EL", "/ARABA", etc.
        $texto = preg_replace('/[,\/]\s*.+$/', '', $texto);

        // Transliterar tildes
        $mapa = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
        return strtr($texto, $mapa);
    }
}
