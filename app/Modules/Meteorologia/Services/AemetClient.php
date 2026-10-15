<?php

namespace App\Modules\Meteorologia\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $this->apiKey = (string) config('aemet.api_key');
        $this->baseUrl = config('aemet.base_url', 'https://opendata.aemet.es/opendata/api');
    }

    /**
     * Ejecuta una petición y devuelve el array de datos final.
     *
     * @throws RuntimeException si AEMET devuelve un error o no hay datos.
     */
    public function get(string $endpoint): array
    {
        // La clave va en cabecera y no en la URL: así no acaba en los logs con el mensaje
        // de una excepción. AEMET limita peticiones por minuto, de ahí el reintento ante 429.
        $meta = Http::timeout(30)
            ->connectTimeout(10)
            ->withHeaders(['api_key' => $this->apiKey])
            ->accept('application/json')
            ->retry(2, 5000, fn (\Exception $e) => $this->reintentable($e), throw: false)
            ->get($this->baseUrl.$endpoint);

        if ($meta->failed()) {
            Log::warning("AEMET: error HTTP {$meta->status()} en {$endpoint}");
            throw new RuntimeException("AEMET: error HTTP {$meta->status()} en {$endpoint}");
        }

        $estado = $meta->json('estado');

        if ($estado === 404) {
            return [];
        }

        if ($estado !== 200) {
            $desc = $meta->json('descripcion') ?? $meta->body();
            Log::warning("AEMET: estado {$estado} en {$endpoint} – {$desc}");
            throw new RuntimeException("AEMET: estado {$estado} – {$desc}");
        }

        $datosUrl = $meta->json('datos');

        if (! $datosUrl) {
            throw new RuntimeException('AEMET: respuesta sin URL de datos.');
        }

        $datos = Http::timeout(60)
            ->connectTimeout(10)
            ->accept('application/json')
            ->retry(2, 5000, fn (\Exception $e) => $this->reintentable($e), throw: false)
            ->get($datosUrl);

        if ($datos->failed()) {
            Log::warning("AEMET: error HTTP {$datos->status()} descargando datos de {$endpoint}");
            throw new RuntimeException("AEMET: error HTTP {$datos->status()} descargando datos.");
        }

        // AEMET devuelve ISO-8859-1; convertimos a UTF-8 antes de decodificar.
        $body = mb_convert_encoding($datos->body(), 'UTF-8', 'ISO-8859-1');

        $json = json_decode($body, true);
        if ($json === null && trim($body) !== '' && strtolower(trim($body)) !== 'null') {
            Log::warning("AEMET: JSON inválido en los datos de {$endpoint}");
            throw new RuntimeException("AEMET: JSON inválido en los datos de {$endpoint}");
        }

        return $json ?? [];
    }

    /** Reintenta los fallos de conexión y el 429 de cuota por minuto, no los demás errores. */
    private function reintentable(\Exception $e): bool
    {
        if (! $e instanceof RequestException) {
            return true; // ConnectionException y similares
        }

        return $e->response->status() === 429;
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
