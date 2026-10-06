<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Services\MetricasUso;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Usuarios\Services\BorradoCuenta;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de cuentas. Se ven los datos de la cuenta y cifras de uso (fincas, hectáreas,
 * tratamientos), no el contenido de las explotaciones.
 */
class UsuarioAdminController extends Controller
{
    public const FILTROS = [
        'todos'      => 'Todos',
        'activos'    => 'Activos',
        'inactivos'  => 'Inactivos',
        'bloqueados' => 'Bloqueados',
        'admins'     => 'Administradores',
    ];

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('q', ''));
        $filtro = array_key_exists($request->query('filtro'), self::FILTROS) ? $request->query('filtro') : 'todos';
        $activoDesde = now()->subDays(MetricasUso::DIAS_ACTIVO);

        $usuarios = User::query()
            ->withCount('fincas')
            ->addSelect([
                'hectareas' => Parcela::selectRaw('coalesce(sum(parcelas.superficie_ha), 0)')
                    ->join('fincas', 'fincas.id', '=', 'parcelas.finca_id')
                    ->whereColumn('fincas.user_id', 'users.id'),
                'tratamientos' => Tratamiento::selectRaw('count(*)')->whereColumn('tratamientos.user_id', 'users.id'),
            ])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('name ilike ?', ["%{$buscar}%"])->orWhereRaw('email ilike ?', ["%{$buscar}%"])))
            ->when($filtro === 'activos', fn ($q) => $q->where('ultimo_acceso_at', '>=', $activoDesde))
            ->when($filtro === 'inactivos', fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('ultimo_acceso_at')->orWhere('ultimo_acceso_at', '<', $activoDesde)))
            ->when($filtro === 'bloqueados', fn ($q) => $q->whereNotNull('bloqueado_at'))
            ->when($filtro === 'admins', fn ($q) => $q->where('is_admin', true))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.usuarios.index', compact('usuarios', 'buscar', 'filtro'));
    }

    public function bloquear(Request $request, User $usuario)
    {
        if ($error = $this->noSePuedeTocar($request->user(), $usuario)) {
            return back()->with('error', $error);
        }

        $usuario->forceFill(['bloqueado_at' => now()])->save();
        $this->registrar($request->user(), 'bloqueó', $usuario);

        return back()->with('success', "{$usuario->name} está bloqueado: no podrá entrar hasta que lo desbloquees.");
    }

    public function desbloquear(Request $request, User $usuario)
    {
        $usuario->forceFill(['bloqueado_at' => null])->save();
        $this->registrar($request->user(), 'desbloqueó', $usuario);

        return back()->with('success', "{$usuario->name} vuelve a tener acceso.");
    }

    public function destroy(Request $request, User $usuario, BorradoCuenta $borrado)
    {
        if ($error = $this->noSePuedeTocar($request->user(), $usuario)) {
            return back()->with('error', $error);
        }

        $request->validate(['confirmacion' => 'required|in:' . $usuario->email], [
            'confirmacion.in' => 'Escribe el email del usuario para confirmar el borrado.',
        ]);

        $this->registrar($request->user(), 'borró la cuenta de', $usuario);
        $borrado->borrar($usuario);

        return redirect()->route('admin.usuarios.index')->with('success', "Cuenta de {$usuario->email} borrada con todos sus datos.");
    }

    /** Un administrador no se bloquea ni se borra a sí mismo ni a otro administrador (antes se le quita el rol). */
    private function noSePuedeTocar(User $admin, User $usuario): ?string
    {
        return match (true) {
            $admin->is($usuario) => 'No puedes hacer eso con tu propia cuenta.',
            $usuario->is_admin   => 'Es administrador: quítale antes el acceso con «php artisan usuarios:admin email --quitar».',
            default              => null,
        };
    }

    /** Queda constancia en el log de lo que hace cada administrador con las cuentas. */
    private function registrar(User $admin, string $accion, User $usuario): void
    {
        Log::notice("Backoffice: {$admin->email} (#{$admin->id}) {$accion} {$usuario->email} (#{$usuario->id})");
    }
}
