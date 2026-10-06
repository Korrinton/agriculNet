<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\GradoDia;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\Coste;
use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;

/**
 * Un usuario con datos en todas las tablas que dependen de él, para comprobar que el borrado
 * de la cuenta y la descarga de datos no se dejan ninguna. Si se añade una tabla nueva, va aquí.
 */
trait CreaUsuarioConDatos
{
    protected function estacionAemet(): EstacionMeteorologica
    {
        $aemet = EstacionMeteorologica::firstOrCreate(['codigo_externo' => '3260B'],
            ['nombre' => 'TOLEDO', 'latitud' => 39.8, 'longitud' => -4, 'fuente' => 'aemet']);
        DatoMeteorologico::firstOrCreate(['estacion_id' => $aemet->id, 'fecha' => '2026-05-01'], ['temp_max' => 22, 'temp_min' => 9]);

        return $aemet;
    }

    /** Dos fincas: una con estación manual propia y otra con la estación de AEMET compartida. */
    protected function usuarioConDatos(array $atributos = []): User
    {
        $user = User::factory()->create($atributos);
        $manual = EstacionMeteorologica::create(['nombre' => 'Manual', 'latitud' => 0, 'longitud' => 0, 'fuente' => 'manual']);
        DatoMeteorologico::create(['estacion_id' => $manual->id, 'fecha' => '2026-05-01', 'temp_max' => 20, 'temp_min' => 8]);

        foreach ([$manual, $this->estacionAemet()] as $estacion) {
            $finca = Finca::create(['user_id' => $user->id, 'provincia_cod' => 45, 'municipio_cod' => 54, 'estacion_meteorologica_id' => $estacion->id]);
            $parcela = Parcela::create([
                'finca_id' => $finca->id, 'nombre' => 'Viña', 'uso' => 'Viña en espaldera', 'superficie_ha' => 2,
                'agregado' => 0, 'poligono' => 1, 'parcela_sigpac' => 1, 'recinto' => 1,
            ]);
            $base = ['parcela_id' => $parcela->id, 'user_id' => $user->id];

            // Producto propio con precio: el tratamiento genera además su coste
            $producto = ProductoFitosanitario::create(['nombre' => 'Mi caldo', 'user_id' => $user->id]);
            app(TratamientoService::class)->registrar($parcela, $user, [
                'producto_id' => $producto->id, 'fecha' => '2026-05-10', 'dosis_l_ha' => 2, 'precio_unitario' => 10,
                'aplicador_nombre' => 'Ramón García', 'aplicador_nif' => '12345678Z',
            ]);
            Coste::create(['finca_id' => $finca->id, 'user_id' => $user->id, 'fecha' => '2026-01-10', 'importe' => 300,
                'categoria_id' => Coste::first()->categoria_id, 'descripcion' => 'Seguro']);
            RegistroFenologico::create($base + ['fecha_observacion' => '2026-05-01',
                'estado_fenologico_id' => EstadoFenologico::firstOrCreate(['codigo_bbch' => '57'], ['nombre' => 'Flores', 'orden' => 57])->id]);
            Fertilizacion::create($base + ['fecha' => '2026-03-01', 'tipo' => 'mineral', 'producto' => 'NPK', 'dosis' => 300, 'unidad' => 'kg/ha', 'superficie_ha' => 2]);
            Cosecha::create($base + ['fecha' => '2026-09-15', 'producto' => 'Uva', 'cantidad_kg' => 9000]);
            Riego::create($base + ['fecha' => '2026-07-01', 'volumen_m3' => 100, 'superficie_ha' => 2, 'sistema' => 'goteo']);
            Alerta::create($base + ['tipo' => 'helada', 'nivel' => 'warning', 'mensaje' => 'Helada', 'clave' => "helada:{$parcela->id}"]);
            GradoDia::create(['parcela_id' => $parcela->id, 'fecha' => '2026-05-01', 'acumulado' => 120.5]);
        }

        return $user;
    }
}
