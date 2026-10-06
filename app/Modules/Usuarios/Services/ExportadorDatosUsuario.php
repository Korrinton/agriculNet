<?php

namespace App\Modules\Usuarios\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Derechos de acceso y portabilidad (arts. 15 y 20 RGPD): todos los datos de un usuario en un
 * .zip con un JSON (legible por máquina, con los ids que relacionan las tablas) y un CSV por tabla
 * para abrirlos en una hoja de cálculo.
 *
 * Una tabla por cada cosa que guarda la aplicación: si se añade un registro nuevo vinculado al
 * usuario, a sus fincas o a sus parcelas, hay que añadirlo aquí (y a BorradoCuenta).
 */
class ExportadorDatosUsuario
{
    /** Columnas internas que no son datos del usuario o no deben salir nunca. */
    private const OCULTAS = ['password', 'remember_token'];

    /** @return string ruta del .zip temporal (se borra al enviarlo) */
    public function generar(User $user): string
    {
        $tablas = $this->tablas($user);

        $ruta = tempnam(sys_get_temp_dir(), 'datos') . '.zip';
        $zip = new ZipArchive;
        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de exportación.');
        }

        $zip->addFromString('datos.json', json_encode([
            'exportado_el' => now()->toIso8601String(),
            'aplicacion'   => config('app.name'),
            'tablas'       => $tablas,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));

        foreach ($tablas as $nombre => $filas) {
            $zip->addFromString("csv/{$nombre}.csv", $this->csv($filas));
        }
        $zip->addFromString('LEEME.txt', $this->leeme($tablas));
        $zip->close();

        return $ruta;
    }

    /** @return array<string, array<int, array>> nombre de la tabla => filas */
    public function tablas(User $user): array
    {
        $fincas = DB::table('fincas')->where('user_id', $user->id)->pluck('id');
        $parcelas = DB::table('parcelas')->whereIn('finca_id', $fincas)->pluck('id');
        // Solo las estaciones manuales: son de la finca; las de AEMET son públicas y compartidas
        $estaciones = DB::table('estaciones_meteorologicas')->where('fuente', 'manual')
            ->whereIn('id', DB::table('fincas')->whereIn('id', $fincas)->whereNotNull('estacion_meteorologica_id')->select('estacion_meteorologica_id'))
            ->pluck('id');

        $deParcelas = fn (string $tabla) => DB::table($tabla)->whereIn("{$tabla}.parcela_id", $parcelas);

        return array_map(fn (Builder $consulta) => $this->filas($consulta), [
            'cuenta'   => DB::table('users')->where('id', $user->id),
            'fincas'   => DB::table('fincas')->whereIn('id', $fincas)->orderBy('id'),
            // Incluidas las parcelas dadas de baja (deleted_at), que conservan sus registros
            'parcelas' => DB::table('parcelas')->whereIn('parcelas.id', $parcelas)
                ->leftJoin('variedades', 'variedades.id', '=', 'parcelas.variedad_id')
                ->select('parcelas.*', 'variedades.nombre as variedad')->orderBy('parcelas.id'),
            'tratamientos' => $deParcelas('tratamientos')
                ->leftJoin('productos_fitosanitarios', 'productos_fitosanitarios.id', '=', 'tratamientos.producto_id')
                ->select('tratamientos.*', 'productos_fitosanitarios.nombre as producto', 'productos_fitosanitarios.numero_registro as producto_numero_registro')
                ->orderBy('tratamientos.fecha')->orderBy('tratamientos.id'),
            'fertilizaciones' => $deParcelas('fertilizaciones')->orderBy('fecha')->orderBy('id'),
            'cosechas'        => $deParcelas('cosechas')->orderBy('fecha')->orderBy('id'),
            'riegos'          => $deParcelas('riegos')->orderBy('fecha')->orderBy('id'),
            'observaciones_fenologicas' => $deParcelas('registro_fenologico')
                ->leftJoin('estados_fenologicos', 'estados_fenologicos.id', '=', 'registro_fenologico.estado_fenologico_id')
                ->select('registro_fenologico.*', 'estados_fenologicos.codigo_bbch', 'estados_fenologicos.nombre as estado')
                ->orderBy('fecha_observacion')->orderBy('registro_fenologico.id'),
            'costes' => DB::table('costes')->whereIn('costes.finca_id', $fincas)
                ->leftJoin('categoria_costes', 'categoria_costes.id', '=', 'costes.categoria_id')
                ->select('costes.*', 'categoria_costes.nombre as categoria')
                ->orderBy('costes.fecha')->orderBy('costes.id'),
            'grados_dia' => $deParcelas('grados_dia')->orderBy('parcela_id')->orderBy('fecha'),
            'alertas'    => DB::table('alertas')->where('user_id', $user->id)->orderBy('created_at')->orderBy('id'),
            'productos_propios' => DB::table('productos_fitosanitarios')->where('user_id', $user->id)->orderBy('id'),
            'precios_productos' => DB::table('precios_productos_fitosanitarios')->where('precios_productos_fitosanitarios.user_id', $user->id)
                ->join('productos_fitosanitarios', 'productos_fitosanitarios.id', '=', 'precios_productos_fitosanitarios.producto_id')
                ->select('precios_productos_fitosanitarios.*', 'productos_fitosanitarios.nombre as producto')
                ->orderBy('producto'),
            'estaciones_manuales' => DB::table('estaciones_meteorologicas')->whereIn('id', $estaciones)->orderBy('id'),
            'datos_meteorologicos_manuales' => DB::table('datos_meteorologicos')->whereIn('estacion_id', $estaciones)->orderBy('fecha'),
        ]);
    }

    private function filas(Builder $consulta): array
    {
        return $consulta->get()
            ->map(fn ($fila) => array_diff_key((array) $fila, array_flip(self::OCULTAS)))
            ->all();
    }

    /** CSV para Excel en español: UTF-8 con BOM y punto y coma. */
    private function csv(array $filas): string
    {
        $f = fopen('php://temp', 'r+');
        fwrite($f, "\xEF\xBB\xBF");
        if ($filas) {
            fputcsv($f, array_keys($filas[0]), ';', '"', '');
            foreach ($filas as $fila) {
                fputcsv($f, array_map(fn ($v) => is_bool($v) ? (int) $v : $v, $fila), ';', '"', '');
            }
        }
        rewind($f);
        $contenido = stream_get_contents($f);
        fclose($f);

        return $contenido;
    }

    private function leeme(array $tablas): string
    {
        $resumen = collect($tablas)->map(fn ($filas, $nombre) => sprintf('  %-32s %d', $nombre, count($filas)))->join("\n");

        return <<<TXT
        Tus datos en {$this->nombreApp()} — exportados el {$this->ahora()}

        datos.json   Todos los datos, legibles por máquina. Cada tabla es una lista de registros;
                     los campos *_id relacionan unas tablas con otras (p. ej. parcela_id → parcelas.id).
        csv/         Las mismas tablas, una por fichero, para abrirlas con una hoja de cálculo
                     (UTF-8, separadas por punto y coma).

        Registros por tabla:
        {$resumen}

        Las parcelas con deleted_at son parcelas dadas de baja que conservan sus registros.
        El cuaderno de explotación oficial (por finca y campaña) se descarga aparte desde «Cuaderno».
        TXT;
    }

    private function nombreApp(): string
    {
        return (string) config('app.name');
    }

    private function ahora(): string
    {
        return now('Europe/Madrid')->format('d/m/Y H:i');
    }
}
