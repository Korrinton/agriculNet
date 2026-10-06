<?php

namespace Tests\Feature;

use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\GradoDia;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\Coste;
use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\Feature\Concerns\CreaUsuarioConDatos;
use Tests\TestCase;

class BorradoCuentaTest extends TestCase
{
    use CreaUsuarioConDatos, RefreshDatabase;

    public function test_borrar_la_cuenta_borra_todos_sus_datos(): void
    {
        $user = $this->usuarioConDatos();
        $otro = $this->usuarioConDatos();
        $aemet = $this->estacionAemet();

        $this->actingAs($user);
        Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')
            ->assertHasNoErrors()->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertSame(2, Parcela::withTrashed()->count(), 'solo quedan las parcelas del otro usuario');
        $this->assertSame(2, GradoDia::count());
        foreach ([Finca::class, Tratamiento::class, Coste::class, RegistroFenologico::class, Fertilizacion::class,
            Cosecha::class, Riego::class, Alerta::class, ProductoFitosanitario::class] as $modelo) {
            $this->assertSame(0, $modelo::withoutGlobalScopes()->where('user_id', $user->id)->count(), $modelo);
        }

        // La estación manual de su finca desaparece; la de AEMET es compartida y se queda con sus datos
        $this->assertSame(1, EstacionMeteorologica::where('fuente', 'manual')->count());
        $this->assertModelExists($aemet);
        $this->assertSame(1, DatoMeteorologico::where('estacion_id', $aemet->id)->count());

        // Los datos del otro usuario siguen intactos
        $this->assertSame(2, Finca::where('user_id', $otro->id)->count());
        $this->assertSame(2, Tratamiento::where('user_id', $otro->id)->count());
    }
}
