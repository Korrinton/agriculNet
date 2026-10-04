<?php

namespace App\Modules\Meteorologia\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente para AEMET OpenData.
 *
 * El API responde en dos pasos:
 *  1. GET /api/endpoint?api_key=X  →  { "datos": "https://...", "estado": 200 }
 *  2. GET <datos_url>              →  [ array de resultados ]
 */
class AemetClient
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        // env() devuelve null si AEMET_API_KEY está vacía: el default de config() no aplica
        $this->apiKey  = (string) config('aemet.api_key');
        $this->baseUrl = config('aemet.base_url', 'https://opendata.aemet.es/opendata/api');
    }

    /**
     * Ejecuta una petición y devuelve el array de datos final.
     *
     * @throws RuntimeException si AEMET devuelve un error o no hay datos.
     */
    public function get(string $endpoint): array
    {
        $meta = Http::timeout(30)
            ->withQueryParameters(['api_key' => $this->apiKey])
            ->accept('application/json')
            ->get($this->baseUrl . $endpoint);

        if ($meta->failed()) {
            throw new RuntimeException("AEMET: error HTTP {$meta->status()} en {$endpoint}");
        }

        $estado = $meta->json('estado');

        if ($estado === 404) {
            return [];
        }

        if ($estado !== 200) {
            $desc = $meta->json('descripcion') ?? $meta->body();
            throw new RuntimeException("AEMET: estado {$estado} – {$desc}");
        }

        $datosUrl = $meta->json('datos');

        if (! $datosUrl) {
            throw new RuntimeException('AEMET: respuesta sin URL de datos.');
        }

        $datos = Http::timeout(60)
            ->accept('application/json')
            ->get($datosUrl);

        if ($datos->failed()) {
            throw new RuntimeException("AEMET: error HTTP {$datos->status()} descargando datos.");
        }

        // AEMET devuelve ISO-8859-1; convertimos a UTF-8 antes de decodificar.
        $body = mb_convert_encoding($datos->body(), 'UTF-8', 'ISO-8859-1');

        return json_decode($body, true) ?? [];
    }

    /** Convierte un decimal AEMET (coma española) a float. */
    public static function parseDecimal(?string $value): ?float
    {
        if ($value === null || $value === '' || in_array($value, ['Ip', 'Acum', '-'], true)) {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    /**
     * Convierte coordenadas AEMET en formato GMS a decimal.
     * Ejemplos: "410234N" → 41.0428   "0034512W" → -3.7533
     */
    public static function dmsToDecimal(string $dms): float
    {
        $dir = strtoupper(substr($dms, -1));
        $num = substr($dms, 0, -1);

        $len = strlen($num);
        if ($len <= 6) {
            $deg = (int) substr($num, 0, 2);
            $min = (int) substr($num, 2, 2);
            $sec = (int) substr($num, 4, 2);
        } else {
            $deg = (int) substr($num, 0, 3);
            $min = (int) substr($num, 3, 2);
            $sec = (int) substr($num, 5, 2);
        }

        $decimal = $deg + $min / 60 + $sec / 3600;

        return in_array($dir, ['S', 'W'], true) ? -$decimal : $decimal;
    }
}
