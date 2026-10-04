<?php

namespace App\Modules\CalendarioFenologico\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\CalendarioFenologico\Services\CalendarioCampana;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;

class RegistroFenologicoWebController extends Controller
{
    public function index(Request $request, CalendarioCampana $calendario)
    {
        // Estado actual (último registro) de cada parcela del usuario
        $fincas = Finca::where('user_id', auth()->id())
            ->with([
                // Escala BBCH de la vid: el calendario es solo para parcelas de viña
                'parcelas' => function ($q) {
                    $q->vina()->with([
                        'variedad',
                        'registrosFenologicos' => fn($r) => $r->with('estado')->latest('fecha_observacion')->limit(1),
                    ]);
                },
            ])
            ->orderBy('provincia_cod')
            ->get();

        $parcelaIds = $fincas->flatMap->parcelas->pluck('id');
        $otrasParcelas = Parcela::whereHas('finca', fn ($q) => $q->where('user_id', auth()->id()))
            ->whereNotIn('id', $parcelaIds)->count();
        $hoy = now('Europe/Madrid');

        // Campañas navegables: desde la primera observación hasta la actual
        $primerAnio = RegistroFenologico::whereIn('parcela_id', $parcelaIds)->min('fecha_observacion');
        $primerAnio = $primerAnio ? min((int) substr($primerAnio, 0, 4), $hoy->year) : $hoy->year;
        $anio = (int) $request->query('anio', $hoy->year);
        $anio = max($primerAnio, min($hoy->year, $anio));

        $campana = $calendario->construir($parcelaIds, $anio, $hoy);
        $campana['anterior'] = $anio > $primerAnio ? $anio - 1 : null;
        $campana['siguiente'] = $anio < $hoy->year ? $anio + 1 : null;
        $fases = CalendarioCampana::FASES;

        // Historial reciente global
        $registros = RegistroFenologico::whereHas(
            'parcela.finca',
            fn($q) => $q->where('user_id', auth()->id())
        )
            ->with(['parcela.finca', 'estado'])
            ->latest('fecha_observacion')
            ->paginate(30);

        return view('fenologia.index', compact('fincas', 'registros', 'campana', 'fases', 'otrasParcelas'));
    }

    public function create(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        if (! $parcela->esVina()) {
            return redirect()->route('vinedo.parcelas.show', $parcela)
                ->with('error', 'El calendario fenológico usa la escala BBCH de la vid: solo está disponible en parcelas de viña.');
        }

        // El seeder deja 'orden' a 0: el código BBCH (dos dígitos) da el orden natural
        $estados = EstadoFenologico::orderBy('orden')->orderBy('codigo_bbch')->get();

        $ultimoRegistro = $parcela->registrosFenologicos()
            ->with('estado')
            ->latest('fecha_observacion')
            ->first();

        return view('fenologia.create', compact('parcela', 'estados', 'ultimoRegistro'));
    }

    public function store(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        if (! $parcela->esVina()) {
            return redirect()->route('vinedo.parcelas.show', $parcela)
                ->with('error', 'El calendario fenológico usa la escala BBCH de la vid: solo está disponible en parcelas de viña.');
        }

        $validated = $request->validate([
            'estado_fenologico_id' => 'required|exists:estados_fenologicos,id',
            'fecha_observacion'    => 'required|date|before_or_equal:today',
            'observaciones'        => 'nullable|string|max:1000',
        ]);

        $parcela->registrosFenologicos()->create(
            array_merge($validated, ['user_id' => $request->user()->id])
        );

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Observación fenológica registrada.');
    }

    public function destroy(RegistroFenologico $registro)
    {
        $this->authorize('update', $registro->parcela->finca);

        $parcela = $registro->parcela;
        $registro->delete();

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Registro eliminado.');
    }
}
