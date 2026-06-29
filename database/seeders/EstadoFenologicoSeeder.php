<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoFenologicoSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['codigo_bbch' => '00', 'nombre' => 'Yema de invierno', 'descripcion' => 'Yema dura, cubierta por escamas pardas'],
            ['codigo_bbch' => '01', 'nombre' => 'Inicio del hinchado de yemas', 'descripcion' => 'Las yemas empiezan a hincharse'],
            ['codigo_bbch' => '03', 'nombre' => 'Yemas hinchadas', 'descripcion' => 'Yemas claramente hinchadas, escamas marrones separadas'],
            ['codigo_bbch' => '05', 'nombre' => 'Puntas lanosas', 'descripcion' => 'Puntas lanosas o algodonosas visibles'],
            ['codigo_bbch' => '07', 'nombre' => 'Hojas verdes visibles', 'descripcion' => 'Primeras hojas verdes emergiendo de la lana'],
            ['codigo_bbch' => '09', 'nombre' => 'Inicio del desborre', 'descripcion' => 'Primera hoja verde extendida fuera de la lana'],
            ['codigo_bbch' => '11', 'nombre' => '1ª hoja desplegada', 'descripcion' => 'Primera hoja claramente desplegada'],
            ['codigo_bbch' => '12', 'nombre' => '2 hojas desplegadas', 'descripcion' => 'Segunda hoja claramente desplegada'],
            ['codigo_bbch' => '13', 'nombre' => '3 hojas desplegadas', 'descripcion' => 'Tercera hoja claramente desplegada'],
            ['codigo_bbch' => '14', 'nombre' => '4 hojas desplegadas', 'descripcion' => 'Cuarta hoja claramente desplegada'],
            ['codigo_bbch' => '15', 'nombre' => '5 hojas desplegadas', 'descripcion' => 'Quinta hoja claramente desplegada'],
            ['codigo_bbch' => '16', 'nombre' => '6 hojas desplegadas', 'descripcion' => 'Sexta hoja claramente desplegada'],
            ['codigo_bbch' => '17', 'nombre' => '7 hojas desplegadas', 'descripcion' => 'Séptima hoja claramente desplegada'],
            ['codigo_bbch' => '18', 'nombre' => '8 hojas desplegadas', 'descripcion' => 'Octava hoja claramente desplegada'],
            ['codigo_bbch' => '19', 'nombre' => '9 o más hojas desplegadas', 'descripcion' => 'Nueve o más hojas desplegadas'],
            ['codigo_bbch' => '53', 'nombre' => 'Racimos visibles', 'descripcion' => 'Racimos florales claramente visibles'],
            ['codigo_bbch' => '55', 'nombre' => 'Racimos separados', 'descripcion' => 'Racimos florales separados entre sí'],
            ['codigo_bbch' => '57', 'nombre' => 'Flores individuales visibles', 'descripcion' => 'Flores individuales claramente visibles'],
            ['codigo_bbch' => '60', 'nombre' => 'Inicio de floración', 'descripcion' => 'Primeras flores abiertas (menos del 10%)'],
            ['codigo_bbch' => '61', 'nombre' => 'Inicio de floración 10%', 'descripcion' => 'Aproximadamente el 10% de flores abiertas'],
            ['codigo_bbch' => '65', 'nombre' => 'Plena floración', 'descripcion' => 'Aproximadamente el 50% de flores abiertas'],
            ['codigo_bbch' => '67', 'nombre' => 'Fin de floración', 'descripcion' => 'Últimas flores; frutos claramente visibles'],
            ['codigo_bbch' => '69', 'nombre' => 'Cuajado', 'descripcion' => 'Frutos alcanzando el tamaño final; flores caídas'],
            ['codigo_bbch' => '71', 'nombre' => 'Inicio de engrosamiento del fruto', 'descripcion' => 'Pequeñas bayas verdes. Caída de granos de forma normal'],
            ['codigo_bbch' => '73', 'nombre' => 'Bayas de tamaño de guisante', 'descripcion' => 'Bayas alcanzando tamaño de guisante'],
            ['codigo_bbch' => '75', 'nombre' => 'Racimos colgantes', 'descripcion' => 'Los racimos comienzan a colgar por su peso'],
            ['codigo_bbch' => '77', 'nombre' => 'Bayas de tamaño definitivo', 'descripcion' => 'Bayas alcanzando tamaño definitivo de la variedad'],
            ['codigo_bbch' => '81', 'nombre' => 'Inicio del envero', 'descripcion' => 'Inicio de la coloración y/o reblandecimiento de las bayas'],
            ['codigo_bbch' => '83', 'nombre' => 'Envero 30%', 'descripcion' => 'Aproximadamente el 30% de las bayas coloreadas/blandas'],
            ['codigo_bbch' => '85', 'nombre' => 'Envero 50%', 'descripcion' => 'Aproximadamente el 50% de las bayas coloreadas/blandas'],
            ['codigo_bbch' => '89', 'nombre' => 'Maduración completa', 'descripcion' => 'Bayas con contenido de azúcar máximo para la variedad'],
            ['codigo_bbch' => '91', 'nombre' => 'Fin de la maduración', 'descripcion' => 'Inicio de la senescencia foliar'],
            ['codigo_bbch' => '93', 'nombre' => 'Inicio de la caída foliar', 'descripcion' => 'Inicio de la decoloración y caída de hojas'],
            ['codigo_bbch' => '95', 'nombre' => 'Caída foliar 50%', 'descripcion' => 'Aproximadamente el 50% de hojas caídas'],
            ['codigo_bbch' => '97', 'nombre' => 'Fin de la caída foliar', 'descripcion' => 'Todas las hojas caídas; inicio de reposo vegetativo'],
        ];

        foreach ($estados as $estado) {
            DB::table('estados_fenologicos')->updateOrInsert(
                ['codigo_bbch' => $estado['codigo_bbch']],
                array_merge($estado, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
