<?php

namespace App\Modules\Alertas\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Alertas\Services\AlertaService;
use Illuminate\Http\Request;

class AlertaWebController extends Controller
{
    public const NIVELES = [
        'critical' => 'Crítica',
        'warning'  => 'Aviso',
        'info'     => 'Información',
    ];

    public function __construct(private readonly AlertaService $service) {}

    public function index(Request $request)
    {
        $soloNoLeidas = $request->query('estado', 'no_leidas') !== 'todas';
        $nivel = in_array($request->query('nivel'), array_keys(self::NIVELES), true) ? $request->query('nivel') : null;

        $alertas  = $this->service->alertasDeUsuario($request->user(), $soloNoLeidas, $nivel);
        $noLeidas = $this->service->contarNoLeidas($request->user());
        $niveles  = self::NIVELES;

        return view('alertas.index', compact('alertas', 'noLeidas', 'soloNoLeidas', 'nivel', 'niveles'));
    }

    public function marcarLeida(Alerta $alerta)
    {
        $this->authorize('update', $alerta);

        $alerta->marcarLeida();

        return back()->with('success', 'Alerta marcada como leída.');
    }

    public function marcarTodasLeidas(Request $request)
    {
        $total = $this->service->marcarTodasLeidas($request->user());

        return back()->with('success', $total === 1
            ? '1 alerta marcada como leída.'
            : "{$total} alertas marcadas como leídas.");
    }

    public function destroy(Alerta $alerta)
    {
        $this->authorize('delete', $alerta);

        $alerta->delete();

        return back()->with('success', 'Alerta eliminada.');
    }
}
