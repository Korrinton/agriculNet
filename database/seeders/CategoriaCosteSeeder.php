<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaCosteSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Poda', 'tipo' => 'mano_obra'],
            ['nombre' => 'Atado y despampanado', 'tipo' => 'mano_obra'],
            ['nombre' => 'Escardas manuales', 'tipo' => 'mano_obra'],
            ['nombre' => 'Vendimia manual', 'tipo' => 'mano_obra'],
            ['nombre' => 'Otros trabajos manuales', 'tipo' => 'mano_obra'],
            ['nombre' => 'Tractor y laboreo', 'tipo' => 'maquinaria'],
            ['nombre' => 'Vendimia mecánica', 'tipo' => 'maquinaria'],
            ['nombre' => 'Pulverización fitosanitaria', 'tipo' => 'maquinaria'],
            ['nombre' => 'Otras labores mecanizadas', 'tipo' => 'maquinaria'],
            ['nombre' => 'Fertilizantes y abonos', 'tipo' => 'insumos'],
            ['nombre' => 'Productos fitosanitarios', 'tipo' => 'insumos'],
            ['nombre' => 'Material de poda y espaldera', 'tipo' => 'insumos'],
            ['nombre' => 'Riego', 'tipo' => 'insumos'],
            ['nombre' => 'Otros insumos', 'tipo' => 'insumos'],
            ['nombre' => 'Seguros agrarios', 'tipo' => 'otros'],
            ['nombre' => 'Arrendamientos', 'tipo' => 'otros'],
            ['nombre' => 'Análisis y laboratorio', 'tipo' => 'otros'],
            ['nombre' => 'Gastos administrativos', 'tipo' => 'otros'],
            ['nombre' => 'Otros gastos', 'tipo' => 'otros'],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categoria_costes')->updateOrInsert(
                ['nombre' => $categoria['nombre'], 'tipo' => $categoria['tipo']],
                array_merge($categoria, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
