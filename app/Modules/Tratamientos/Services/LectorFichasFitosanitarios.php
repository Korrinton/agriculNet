<?php

namespace App\Modules\Tratamientos\Services;

use App\Modules\Tratamientos\Models\PlazoSeguridadProducto;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Smalot\PdfParser\Parser;

/**
 * Lee los plazos de seguridad oficiales de la ficha PDF de cada producto en el registro del MAPA.
 *
 * La tabla «Plazos de Seguridad» de la ficha da, para cada grupo de cultivos, los días (o «N HORAS»,
 * o «NP», no procede). Las dosis no se leen: la tabla de usos sale desordenada del PDF y mezcla
 * unidades (l/ha, kg/ha, %, ml/m²) que no se pueden comparar con la dosis por hectárea.
 */
class LectorFichasFitosanitarios
{
    /**
     * Nombres con los que aparece cada cultivo de la app en las fichas (sin tildes ni mayúsculas).
     * Si ninguno de los propios figura, se usan los genéricos (p. ej. «cereales» para el trigo).
     */
    private const NOMBRES = [
        'vid'       => [['vid', 'vid de vinificacion', 'vinedo', 'uva de vinificacion'], []],
        'olivo'     => [['olivo', 'olivo de almazara', 'olivo de verdeo', 'olivar'], []],
        'pistacho'  => [['pistachero', 'pistacho'], ['frutales de cascara', 'frutos de cascara']],
        'trigo'     => [['trigo', 'trigo blando', 'trigo duro'], self::CEREALES],
        'cebada'    => [['cebada'], self::CEREALES],
        'avena'     => [['avena'], self::CEREALES],
        'centeno'   => [['centeno'], self::CEREALES],
        'triticale' => [['triticale'], self::CEREALES],
        'veza'      => [['veza'], ['leguminosas forrajeras', 'leguminosas']],
        'yero'      => [['yero', 'yeros'], ['leguminosas forrajeras', 'leguminosas']],
        'guisante'  => [['guisante', 'guisante proteaginoso', 'guisante para grano', 'guisante proteaginoso /forrajero', 'guisante proteaginoso / forrajero'], ['leguminosas de grano', 'leguminosas']],
        'garbanzo'  => [['garbanzo'], ['leguminosas de grano', 'leguminosas']],
        'lenteja'   => [['lenteja'], ['leguminosas de grano', 'leguminosas']],
        'girasol'   => [['girasol'], ['oleaginosas']],
        'colza'     => [['colza'], ['oleaginosas', 'cruciferas oleaginosas']],
        'barbecho'  => [['barbecho', 'barbechos'], []],
    ];

    private const CEREALES = ['cereales', 'cereales de invierno', 'cereales de invierno / primavera', 'cereales de invierno/primavera'];

    /** Descarga la ficha y guarda sus plazos de seguridad; devuelve cuántos cultivos de la app trae. */
    public function leer(ProductoFitosanitario $producto): int
    {
        if (!$producto->mapa_id) {
            throw new RuntimeException("{$producto->nombre} no es un producto del registro.");
        }

        $respuesta = Http::timeout(60)->retry(2, 3000)->get($producto->urlFichaMapa());
        if (!str_starts_with($respuesta->body(), '%PDF')) {
            throw new RuntimeException("La ficha de {$producto->nombre} no es un PDF.");
        }

        $plazos = self::plazosPorCultivo((new Parser())->parseContent($respuesta->body())->getText());

        DB::transaction(function () use ($producto, $plazos) {
            PlazoSeguridadProducto::where('producto_id', $producto->id)->delete();
            foreach ($plazos as $cultivo => $dias) {
                PlazoSeguridadProducto::create(['producto_id' => $producto->id, 'cultivo' => $cultivo, 'dias' => $dias]);
            }
            $producto->forceFill(['ficha_leida_at' => now()])->save();
        });

        return count($plazos);
    }

    /**
     * Plazo de seguridad de cada cultivo de la app que aparece en la ficha: días, o null si «no procede».
     * Si un cultivo figura en varias filas se toma el plazo más largo.
     *
     * @return array<string, int|null>
     */
    public static function plazosPorCultivo(string $texto): array
    {
        $filas = self::filasPlazos($texto);
        $plazos = [];

        foreach (self::NOMBRES as $cultivo => [$propios, $genericos]) {
            // «Especies vegetales»: productos para cualquier planta
            foreach ([$propios, $genericos, ['especies vegetales']] as $nombres) {
                $coinciden = array_filter($filas, fn ($fila) => array_intersect($fila['cultivos'], $nombres));
                if ($coinciden) {
                    $dias = array_filter(array_column($coinciden, 'dias'), fn ($d) => $d !== null);
                    $plazos[$cultivo] = $dias ? max($dias) : null;
                    break;
                }
            }
        }

        return $plazos;
    }

    /**
     * Filas de la tabla «USO / P.S. (días)». Una fila puede ocupar varias líneas: la lista de
     * cultivos continúa hasta la línea que acaba en el plazo.
     *
     * @return list<array{cultivos: list<string>, dias: int|null}>
     */
    public static function filasPlazos(string $texto): array
    {
        if (!preg_match('/USO\s+P\.S\.\s*\(d[ií]as\)(.*?)Plazos de Seguridad/su', $texto, $m)) {
            return [];
        }

        $filas = [];
        $acumulado = '';

        foreach (preg_split('/\R/u', $m[1]) as $linea) {
            if (str_contains($linea, "\t")) {
                // «cultivos<TAB>plazo»: el plazo va tras el último tabulador
                $corte = strrpos($linea, "\t");
                $filas[] = self::fila($acumulado . ' ' . substr($linea, 0, $corte), substr($linea, $corte + 1));
                $acumulado = '';
            } elseif (preg_match('/^\s*(\d.*|N\.?\s*[PA]\.?|NO PROCEDE|NO APLICA|-)\s*$/iu', $linea) && trim($acumulado) !== '') {
                // La lista de cultivos ocupó varias líneas y el plazo va solo en la última
                $filas[] = self::fila($acumulado, $linea);
                $acumulado = '';
            } else {
                $acumulado .= ' ' . $linea;
            }
        }

        return $filas;
    }

    private static function fila(string $cultivos, string $valor): array
    {
        $nombres = array_map(fn ($c) => self::normalizar($c), explode(',', $cultivos));

        return ['cultivos' => array_values(array_filter($nombres)), 'dias' => self::dias($valor)];
    }

    /**
     * «7», «4 HORAS», «NP», «N.P.», «NA», «NO PROCEDE» o texto con varios plazos («7 DÍAS UVAS DE MESA;
     * 14 DÍAS UVAS DE VINIFICACIÓN»): se toma el más largo. Las horas se redondean al día siguiente.
     */
    private static function dias(string $valor): ?int
    {
        if (!preg_match_all('/(\d+)\s*(HORAS?)?/iu', $valor, $m, PREG_SET_ORDER)) {
            return null;   // no procede
        }

        return max(array_map(fn ($n) => empty($n[2]) ? (int) $n[1] : (int) ceil((int) $n[1] / 24), $m));
    }

    private static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $texto)));

        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
