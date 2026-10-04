<?php

namespace App\Modules\CuadernoDigital\Services;

use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\Tratamiento;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta el cuaderno de explotación de una campaña a Excel, una hoja por sección.
 *
 * El envío electrónico a SIEX solo lo pueden hacer entidades habilitadas por el MAPA;
 * este fichero sirve para tener el cuaderno al día, imprimirlo o entregarlo a quien
 * gestione la presentación.
 */
class ExportadorCUE
{
    /** Genera el .xlsx y devuelve su contenido binario. */
    public function generar(array $datos): string
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setTitle("Cuaderno de explotación {$datos['anio']}")->setCreator('agriculNet');

        $this->hojaGeneral($libro->getActiveSheet(), $datos);
        $this->hoja($libro->createSheet(), 'Tratamientos', [
            'Fecha', 'Parcela (ref. SIGPAC)', 'Cultivo', 'Variedad', 'Sup. tratada (ha)', 'Problema / motivo',
            'Producto', 'Nº registro', 'Dosis (l/ha)', 'Aplicador', 'Nº ROPO', 'Equipo (nº ROMA)', 'Eficacia',
        ], $datos['tratamientos']->map(fn (Tratamiento $t) => [
            $t->fecha->format('d/m/Y'),
            $t->parcela->referencia_sigpac ?? $t->parcela->nombre,
            CuadernoCampana::cultivoDe($t->parcela),
            $t->parcela->variedad?->nombre,
            (float) ($t->superficie_tratada_ha ?? $t->parcela->superficie_ha),
            $t->motivo,
            $t->producto?->nombre,
            $t->producto?->numero_registro,
            (float) $t->dosis_l_ha,
            $t->aplicador_nombre,
            $t->aplicador_ropo,
            $t->equipo_roma,
            Tratamiento::EFICACIAS[$t->eficacia] ?? null,
        ])->all());

        $this->hoja($libro->createSheet(), 'Fertilización', [
            'Fecha', 'Parcela (ref. SIGPAC)', 'Cultivo', 'Superficie (ha)', 'Tipo', 'Producto', 'Riqueza N-P-K (%)',
            'Dosis', 'Unidad', 'Método', 'Observaciones',
        ], $datos['fertilizaciones']->map(fn (Fertilizacion $f) => [
            $f->fecha->format('d/m/Y'),
            $f->parcela->referencia_sigpac ?? $f->parcela->nombre,
            CuadernoCampana::cultivoDe($f->parcela),
            (float) $f->superficie_ha,
            Fertilizacion::TIPOS[$f->tipo] ?? $f->tipo,
            $f->producto,
            $f->npk,
            (float) $f->dosis,
            $f->unidad,
            Fertilizacion::METODOS[$f->metodo] ?? $f->metodo,
            $f->observaciones,
        ])->all());

        $this->hoja($libro->createSheet(), 'Cosecha', [
            'Fecha', 'Parcela (ref. SIGPAC)', 'Cultivo', 'Variedad', 'Producto', 'Cantidad (kg)', 'Superficie (ha)',
            'Rendimiento (kg/ha)', 'Destino', 'NIF destinatario', 'Nº albarán', 'Observaciones',
        ], $datos['cosechas']->map(fn ($c) => [
            $c->fecha->format('d/m/Y'),
            $c->parcela->referencia_sigpac ?? $c->parcela->nombre,
            CuadernoCampana::cultivoDe($c->parcela),
            $c->parcela->variedad?->nombre,
            $c->producto,
            (float) $c->cantidad_kg,
            (float) ($c->superficie_ha ?? $c->parcela->superficie_ha),
            $c->rendimiento_kg_ha !== null ? round($c->rendimiento_kg_ha, 1) : null,
            $c->destino,
            $c->destinatario_nif,
            $c->albaran,
            $c->observaciones,
        ])->all());

        $this->hoja($libro->createSheet(), 'Riego', [
            'Fecha', 'Parcela (ref. SIGPAC)', 'Cultivo', 'Volumen (m³)', 'Superficie (ha)', 'Dosis (m³/ha)',
            'Duración (h)', 'Sistema', 'Origen del agua', 'Observaciones',
        ], $datos['riegos']->map(fn (Riego $r) => [
            $r->fecha->format('d/m/Y'),
            $r->parcela->referencia_sigpac ?? $r->parcela->nombre,
            CuadernoCampana::cultivoDe($r->parcela),
            (float) $r->volumen_m3,
            (float) $r->superficie_ha,
            $r->dosis_m3_ha !== null ? round($r->dosis_m3_ha, 1) : null,
            $r->duracion_horas !== null ? (float) $r->duracion_horas : null,
            Riego::SISTEMAS[$r->sistema] ?? $r->sistema,
            Riego::ORIGENES[$r->origen] ?? null,
            $r->observaciones,
        ])->all());

        $libro->setActiveSheetIndex(0);

        ob_start();
        (new Xlsx($libro))->save('php://output');

        return (string) ob_get_clean();
    }

    private function hojaGeneral(Worksheet $hoja, array $datos): void
    {
        $finca = $datos['finca'];
        $hoja->setTitle('Datos generales');

        $cabecera = [
            ['Cuaderno de explotación — campaña ' . $datos['anio']],
            [],
            ['Titular', $finca->titular_nombre],
            ['NIF', $finca->titular_nif],
            ['Nº REA', $finca->rea_numero],
            ['Provincia', $finca->provincia_nombre . " ({$finca->provincia_cod})"],
            ['Municipio (código INE)', $finca->codigo_ine],
            ['Paraje', $finca->paraje],
            [],
        ];
        $hoja->fromArray($cabecera, null, 'A1', true);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->getStyle('A3:A8')->getFont()->setBold(true);

        $fila = count($cabecera) + 1;
        $this->tabla($hoja, $fila, [
            'Parcela (ref. SIGPAC)', 'Polígono', 'Parcela', 'Recinto', 'Uso', 'Cultivo', 'Variedad', 'Superficie (ha)', 'Año plantación',
        ], $datos['parcelas']->map(fn ($p) => [
            $p->referencia_sigpac ?? $p->nombre, $p->poligono, $p->parcela_sigpac, $p->recinto, $p->uso,
            CuadernoCampana::cultivoDe($p), $p->variedad?->nombre, (float) $p->superficie_ha, $p->año_plantacion,
        ])->all());

        if ($datos['avisos']) {
            $fila = $hoja->getHighestRow() + 2;
            $hoja->setCellValue("A{$fila}", 'Pendiente para completar el cuaderno:')->getStyle("A{$fila}")->getFont()->setBold(true);
            foreach ($datos['avisos'] as $aviso) {
                $hoja->setCellValue('A' . ++$fila, '• ' . $aviso);
            }
        }
    }

    private function hoja(Worksheet $hoja, string $titulo, array $columnas, array $filas): void
    {
        $hoja->setTitle($titulo);
        $this->tabla($hoja, 1, $columnas, $filas ?: [['Sin registros en esta campaña']]);
        $hoja->freezePane('A2');
    }

    private function tabla(Worksheet $hoja, int $fila, array $columnas, array $filas): void
    {
        $hoja->fromArray($columnas, null, "A{$fila}");
        $ultima = $hoja->getHighestColumn($fila);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7F3EA');

        $hoja->fromArray($filas, null, 'A' . ($fila + 1), true);

        foreach (range(1, count($columnas)) as $i) {
            $hoja->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }
}
