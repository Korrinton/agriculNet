@php
    use App\Modules\CuadernoDigital\Models\Fertilizacion;
    use App\Modules\CuadernoDigital\Services\CuadernoCampana;
    use App\Modules\Tratamientos\Models\Tratamiento;

    $finca = $datos['finca'];
    $num = fn ($v, $d = 2) => $v === null ? '—' : number_format((float) $v, $d, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuaderno de explotación {{ $datos['anio'] }} — {{ $finca->paraje ?: $finca->provincia_nombre }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
    {{-- Solo por el botón de imprimir (data-imprimir): la CSP no deja usar onclick --}}
    @vite('resources/js/app.js')
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #1c1917; margin: 0; padding: 16px; background: #fff; }
        h1 { font-size: 16pt; margin: 0 0 2px; }
        h2 { font-size: 11.5pt; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #15803d; break-after: avoid; }
        .sub { color: #57534e; margin: 0 0 12px; }
        .datos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px 16px; margin-bottom: 8px; }
        .datos div span { display: block; font-size: 8pt; color: #78716c; text-transform: uppercase; letter-spacing: .03em; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        th, td { border: 1px solid #d6d3d1; padding: 3px 5px; text-align: left; vertical-align: top; }
        th { background: #e7f3ea; font-weight: bold; }
        tr { break-inside: avoid; }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .mono { font-family: "Courier New", monospace; }
        .vacio { color: #78716c; font-style: italic; text-align: center; }
        .avisos { margin: 10px 0; padding: 8px 10px; border: 1px solid #f59e0b; background: #fffbeb; font-size: 9pt; }
        .avisos ul { margin: 4px 0 0 18px; padding: 0; }
        .pie { margin-top: 16px; font-size: 8pt; color: #78716c; }
        .firma { margin-top: 28px; display: flex; gap: 60px; font-size: 9pt; }
        .firma div { border-top: 1px solid #57534e; padding-top: 4px; width: 220px; }
        .barra { position: sticky; top: 0; background: #f5f5f4; padding: 8px 12px; margin: -16px -16px 16px; display: flex; gap: 8px; align-items: center; }
        .barra button { padding: 6px 14px; border: 0; border-radius: 6px; background: #15803d; color: #fff; font-size: 10pt; cursor: pointer; }
        @media print { .barra { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="barra">
        <button type="button" data-imprimir>Imprimir / Guardar como PDF</button>
        <span style="font-size:9pt;color:#57534e">En el diálogo de impresión elige «Guardar como PDF» para obtener el fichero.</span>
    </div>

    <h1>Cuaderno de explotación — campaña {{ $datos['anio'] }}</h1>
    <p class="sub">{{ $finca->paraje ?: $finca->provincia_nombre }} · generado el {{ now('Europe/Madrid')->format('d/m/Y H:i') }}</p>

    <h2>1. Datos generales</h2>
    <div class="datos">
        <div><span>Titular</span>{{ $finca->titular_nombre ?: '—' }}</div>
        <div><span>NIF</span><span class="mono" style="display:inline;font-size:10pt;color:inherit;text-transform:none">{{ $finca->titular_nif ?: '—' }}</span></div>
        <div><span>Nº REA</span>{{ $finca->rea_numero ?: '—' }}</div>
        <div><span>Ubicación</span>{{ $finca->provincia_nombre }} ({{ $finca->provincia_cod }}) · municipio {{ $finca->codigo_ine }}{{ $finca->paraje ? ' · ' . $finca->paraje : '' }}</div>
    </div>

    @if($datos['avisos'])
        <div class="avisos"><strong>Pendiente para completar el cuaderno:</strong>
            <ul>@foreach($datos['avisos'] as $aviso)<li>{{ $aviso }}</li>@endforeach</ul>
        </div>
    @endif

    <h2>Parcelas</h2>
    <table>
        <thead><tr><th>Ref. SIGPAC (prov:mun:agr:zona:pol:par:rec)</th><th>Uso</th><th>Cultivo</th><th>Variedad</th><th class="num">Superficie (ha)</th><th>Año plantación</th></tr></thead>
        <tbody>
            @forelse($datos['parcelas'] as $p)
                <tr>
                    <td class="mono">{{ $p->referencia_sigpac ?? $p->nombre }}</td>
                    <td>{{ $p->uso }}</td>
                    <td>{{ CuadernoCampana::cultivoDe($p) }}</td>
                    <td>{{ $p->variedad?->nombre ?? '—' }}</td>
                    <td class="num">{{ $num($p->superficie_ha, 4) }}</td>
                    <td>{{ $p->año_plantacion ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">Sin parcelas</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Tratamientos fitosanitarios</h2>
    <table>
        <thead><tr><th>Fecha y hora</th><th>Parcela</th><th>Cultivo (EPPO) · BBCH</th><th class="num">Sup. tratada (ha)</th><th>Problema · justificación</th><th>Producto</th><th>Nº registro</th><th class="num">Dosis (por ha)</th><th>Aplicador (NIF · ROPO)</th><th>Equipo · inspección ITEAF</th><th>Asesor (NIF · ROPO) · validación</th><th>Eficacia</th></tr></thead>
        <tbody>
            @forelse($datos['tratamientos'] as $t)
                <tr>
                    <td>{{ $t->fecha->format('d/m/Y') }}@if($t->horaInicio())<br>{{ $t->horaInicio() }}@endif</td>
                    <td class="mono">{{ $t->parcela->referencia_sigpac ?? $t->parcela->nombre }}</td>
                    <td>{{ CuadernoCampana::cultivoDe($t->parcela) }} @if($t->codigoEppo())<span class="mono">({{ $t->codigoEppo() }})</span>@endif<br>BBCH {{ $t->bbch ?? '—' }}</td>
                    <td class="num">{{ $num($t->superficie_tratada_ha ?? $t->parcela->superficie_ha, 2) }}</td>
                    <td>{{ $t->motivo ?: '—' }}@if($t->justificacion)<br><em>{{ $t->justificacion }}</em>@endif</td>
                    <td>{{ $t->producto?->nombre ?? '—' }}</td>
                    <td class="mono">{{ $t->producto?->numero_registro ?? '—' }}</td>
                    <td class="num">{{ $num($t->dosis_l_ha, 3) }} {{ $t->unidad ?? 'l' }}</td>
                    <td>{{ $t->aplicador_nombre ?: '—' }}<br><span class="mono">{{ $t->aplicador_nif ?: '—' }} · {{ $t->aplicador_ropo ?: '—' }}</span></td>
                    <td><span class="mono">{{ $t->equipo_roma ?: '—' }}</span><br>{{ $t->equipo_inspeccion_fecha?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $t->asesor_nombre ?: '—' }}@if($t->asesor_nombre)<br><span class="mono">{{ $t->asesor_nif ?: '—' }} · {{ $t->asesor_ropo ?: '—' }}</span><br>{{ $t->asesor_fecha_validacion?->format('d/m/Y') ?? '—' }}@endif</td>
                    <td>{{ Tratamiento::EFICACIAS[$t->eficacia] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="vacio">Sin tratamientos en la campaña</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Fertilización</h2>
    <table>
        <thead><tr><th>Fecha</th><th>Parcela</th><th>Cultivo</th><th class="num">Superficie (ha)</th><th>Tipo</th><th>Producto</th><th>N-P-K (%)</th><th class="num">Dosis</th><th>Método</th><th>Observaciones</th></tr></thead>
        <tbody>
            @forelse($datos['fertilizaciones'] as $f)
                <tr>
                    <td>{{ $f->fecha->format('d/m/Y') }}</td>
                    <td class="mono">{{ $f->parcela->referencia_sigpac ?? $f->parcela->nombre }}</td>
                    <td>{{ CuadernoCampana::cultivoDe($f->parcela) }}</td>
                    <td class="num">{{ $num($f->superficie_ha, 2) }}</td>
                    <td>{{ Fertilizacion::TIPOS[$f->tipo] ?? $f->tipo }}</td>
                    <td>{{ $f->producto }}</td>
                    <td class="mono">{{ $f->npk ?? '—' }}</td>
                    <td class="num">{{ $num($f->dosis, 1) }} {{ $f->unidad }}</td>
                    <td>{{ Fertilizacion::METODOS[$f->metodo] ?? '—' }}</td>
                    <td>{{ $f->observaciones ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="vacio">Sin fertilizaciones en la campaña</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>4. Cosecha</h2>
    <table>
        <thead><tr><th>Fecha</th><th>Parcela</th><th>Cultivo</th><th>Producto</th><th class="num">Cantidad (kg)</th><th class="num">Superficie (ha)</th><th class="num">kg/ha</th><th>Destino</th><th>NIF destinatario</th><th>Nº albarán</th></tr></thead>
        <tbody>
            @forelse($datos['cosechas'] as $c)
                <tr>
                    <td>{{ $c->fecha->format('d/m/Y') }}</td>
                    <td class="mono">{{ $c->parcela->referencia_sigpac ?? $c->parcela->nombre }}</td>
                    <td>{{ CuadernoCampana::cultivoDe($c->parcela) }}</td>
                    <td>{{ $c->producto }}</td>
                    <td class="num">{{ $num($c->cantidad_kg, 0) }}</td>
                    <td class="num">{{ $num($c->superficie_ha ?? $c->parcela->superficie_ha, 2) }}</td>
                    <td class="num">{{ $num($c->rendimiento_kg_ha, 0) }}</td>
                    <td>{{ $c->destino ?: '—' }}</td>
                    <td class="mono">{{ $c->destinatario_nif ?: '—' }}</td>
                    <td class="mono">{{ $c->albaran ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="vacio">Sin cosechas en la campaña</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>5. Riego</h2>
    <table>
        <thead><tr><th>Fecha</th><th>Parcela</th><th>Cultivo</th><th class="num">Volumen (m³)</th><th class="num">Superficie (ha)</th><th class="num">m³/ha</th><th class="num">Duración (h)</th><th>Sistema</th><th>Origen del agua</th></tr></thead>
        <tbody>
            @forelse($datos['riegos'] as $r)
                <tr>
                    <td>{{ $r->fecha->format('d/m/Y') }}</td>
                    <td class="mono">{{ $r->parcela->referencia_sigpac ?? $r->parcela->nombre }}</td>
                    <td>{{ CuadernoCampana::cultivoDe($r->parcela) }}</td>
                    <td class="num">{{ $num($r->volumen_m3, 0) }}</td>
                    <td class="num">{{ $num($r->superficie_ha, 2) }}</td>
                    <td class="num">{{ $num($r->dosis_m3_ha, 0) }}</td>
                    <td class="num">{{ $num($r->duracion_horas, 1) }}</td>
                    <td>{{ \App\Modules\Riegos\Models\Riego::SISTEMAS[$r->sistema] ?? $r->sistema }}</td>
                    <td>{{ \App\Modules\Riegos\Models\Riego::ORIGENES[$r->origen] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="vacio">Sin riegos en la campaña</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="firma">
        <div>Firma del titular</div>
        <div>Fecha</div>
    </div>

    <p class="pie">Cuaderno de explotación según la Orden APA/204/2023.</p>
</body>
</html>
