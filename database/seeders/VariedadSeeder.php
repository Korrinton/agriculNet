<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariedadSeeder extends Seeder
{
    public function run(): void
    {
        $variedades = [
            // Tintas
            ['nombre' => 'Tempranillo', 'tipo' => 'tinta', 'descripcion' => 'Variedad tinta principal de España. Vinos con buena estructura y potencial de crianza.'],
            ['nombre' => 'Garnacha Tinta', 'tipo' => 'tinta', 'descripcion' => 'Variedad de ciclo tardío y alto rendimiento. Vinos con mucho color y cuerpo.'],
            ['nombre' => 'Monastrell', 'tipo' => 'tinta', 'descripcion' => 'Variedad resistente a la sequía. Vinos tintos robustos con alta graduación.'],
            ['nombre' => 'Bobal', 'tipo' => 'tinta', 'descripcion' => 'Variedad autóctona de Levante. Vinos con mucho color y notas frutales.'],
            ['nombre' => 'Mencía', 'tipo' => 'tinta', 'descripcion' => 'Variedad del noroeste peninsular. Vinos elegantes y afrutados.'],
            ['nombre' => 'Prieto Picudo', 'tipo' => 'tinta', 'descripcion' => 'Variedad autóctona de Castilla y León. Vinos de gran personalidad.'],
            ['nombre' => 'Graciano', 'tipo' => 'tinta', 'descripcion' => 'Variedad aromática del Rioja. Aporta acidez y finura a los ensamblajes.'],
            ['nombre' => 'Mazuelo (Cariñena)', 'tipo' => 'tinta', 'descripcion' => 'Variedad de alta acidez, usada en ensamblajes.'],
            ['nombre' => 'Cabernet Sauvignon', 'tipo' => 'tinta', 'descripcion' => 'Variedad internacional de alta expresión tánica y capacidad de crianza.'],
            ['nombre' => 'Merlot', 'tipo' => 'tinta', 'descripcion' => 'Variedad internacional. Vinos suaves y afrutados.'],
            ['nombre' => 'Syrah', 'tipo' => 'tinta', 'descripcion' => 'Variedad internacional de origen francés. Vinos especiados y con gran color.'],
            ['nombre' => 'Pinot Noir', 'tipo' => 'tinta', 'descripcion' => 'Variedad de Borgoña. Vinos elegantes, delicados y de color rubí.'],
            // Blancas
            ['nombre' => 'Albariño', 'tipo' => 'blanca', 'descripcion' => 'Variedad gallega por excelencia. Vinos frescos, aromáticos y de buena acidez.'],
            ['nombre' => 'Verdejo', 'tipo' => 'blanca', 'descripcion' => 'Variedad de Rueda. Vinos frescos con notas herbáceas y cítricas.'],
            ['nombre' => 'Airén', 'tipo' => 'blanca', 'descripcion' => 'La variedad más plantada de España. Vinos ligeros y de fácil consumo.'],
            ['nombre' => 'Macabeo (Viura)', 'tipo' => 'blanca', 'descripcion' => 'Variedad ampliamente extendida. Base de los cavas y vinos blancos del Rioja.'],
            ['nombre' => 'Xarel·lo', 'tipo' => 'blanca', 'descripcion' => 'Variedad catalana. Junto con Macabeo y Parellada forma la trilogía del Cava.'],
            ['nombre' => 'Parellada', 'tipo' => 'blanca', 'descripcion' => 'Variedad aromática catalana. Aporta finura y acidez al Cava.'],
            ['nombre' => 'Palomino Fino', 'tipo' => 'blanca', 'descripcion' => 'Variedad base del Jerez. Vinos secos y aptos para crianza biológica.'],
            ['nombre' => 'Pedro Ximénez', 'tipo' => 'blanca', 'descripcion' => 'Variedad para vinos dulces y licorosos en Montilla-Moriles y Jerez.'],
            ['nombre' => 'Gewürztraminer', 'tipo' => 'blanca', 'descripcion' => 'Variedad muy aromática. Notas de rosa, lychee y especias.'],
            ['nombre' => 'Chardonnay', 'tipo' => 'blanca', 'descripcion' => 'Variedad internacional versátil. Vinos desde frescos hasta con crianza en madera.'],
            ['nombre' => 'Sauvignon Blanc', 'tipo' => 'blanca', 'descripcion' => 'Variedad aromática internacional. Vinos frescos con notas cítricas y herbáceas.'],
            ['nombre' => 'Riesling', 'tipo' => 'blanca', 'descripcion' => 'Variedad alemana. Amplio espectro desde secos a dulces con alta acidez.'],
            // Rosadas/Grises
            ['nombre' => 'Garnacha Gris', 'tipo' => 'rosada', 'descripcion' => 'Mutación de la Garnacha Tinta. Vinos rosados de gran expresión.'],
            ['nombre' => 'Pinot Gris', 'tipo' => 'rosada', 'descripcion' => 'Mutación del Pinot Noir. Vinos blancos con estructura y cuerpo.'],
        ];

        foreach ($variedades as $variedad) {
            DB::table('variedades')->updateOrInsert(
                ['nombre' => $variedad['nombre']],
                array_merge($variedad, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
