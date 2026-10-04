<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariedadSeeder extends Seeder
{
    public function run(): void
    {
        // precocidad = época de maduración orientativa (temprana, media, tardia, muy_tardia)
        $variedades = [
            // Tintas
            ['nombre' => 'Tempranillo', 'tipo' => 'tinta', 'precocidad' => 'temprana', 'descripcion' => 'Variedad tinta principal de España. Vinos con buena estructura y potencial de crianza.'],
            ['nombre' => 'Garnacha Tinta', 'tipo' => 'tinta', 'precocidad' => 'tardia', 'descripcion' => 'Variedad de ciclo tardío y alto rendimiento. Vinos con mucho color y cuerpo.'],
            ['nombre' => 'Monastrell', 'tipo' => 'tinta', 'precocidad' => 'muy_tardia', 'descripcion' => 'Variedad resistente a la sequía. Vinos tintos robustos con alta graduación.'],
            ['nombre' => 'Bobal', 'tipo' => 'tinta', 'precocidad' => 'tardia', 'descripcion' => 'Variedad autóctona de Levante. Vinos con mucho color y notas frutales.'],
            ['nombre' => 'Mencía', 'tipo' => 'tinta', 'precocidad' => 'temprana', 'descripcion' => 'Variedad del noroeste peninsular. Vinos elegantes y afrutados.'],
            ['nombre' => 'Prieto Picudo', 'tipo' => 'tinta', 'precocidad' => 'media', 'descripcion' => 'Variedad autóctona de Castilla y León. Vinos de gran personalidad.'],
            ['nombre' => 'Graciano', 'tipo' => 'tinta', 'precocidad' => 'tardia', 'descripcion' => 'Variedad aromática del Rioja. Aporta acidez y finura a los ensamblajes.'],
            ['nombre' => 'Mazuelo (Cariñena)', 'tipo' => 'tinta', 'precocidad' => 'muy_tardia', 'descripcion' => 'Variedad de alta acidez, usada en ensamblajes.'],
            ['nombre' => 'Cabernet Sauvignon', 'tipo' => 'tinta', 'precocidad' => 'tardia', 'descripcion' => 'Variedad internacional de alta expresión tánica y capacidad de crianza.'],
            ['nombre' => 'Merlot', 'tipo' => 'tinta', 'precocidad' => 'media', 'descripcion' => 'Variedad internacional. Vinos suaves y afrutados.'],
            ['nombre' => 'Syrah', 'tipo' => 'tinta', 'precocidad' => 'media', 'descripcion' => 'Variedad internacional de origen francés. Vinos especiados y con gran color.'],
            ['nombre' => 'Pinot Noir', 'tipo' => 'tinta', 'precocidad' => 'temprana', 'descripcion' => 'Variedad de Borgoña. Vinos elegantes, delicados y de color rubí.'],
            // Blancas
            ['nombre' => 'Albariño', 'tipo' => 'blanca', 'precocidad' => 'media', 'descripcion' => 'Variedad gallega por excelencia. Vinos frescos, aromáticos y de buena acidez.'],
            ['nombre' => 'Verdejo', 'tipo' => 'blanca', 'precocidad' => 'media', 'descripcion' => 'Variedad de Rueda. Vinos frescos con notas herbáceas y cítricas.'],
            ['nombre' => 'Airén', 'tipo' => 'blanca', 'precocidad' => 'tardia', 'descripcion' => 'La variedad más plantada de España. Vinos ligeros y de fácil consumo.'],
            ['nombre' => 'Macabeo (Viura)', 'tipo' => 'blanca', 'precocidad' => 'media', 'descripcion' => 'Variedad ampliamente extendida. Base de los cavas y vinos blancos del Rioja.'],
            ['nombre' => 'Xarel·lo', 'tipo' => 'blanca', 'precocidad' => 'media', 'descripcion' => 'Variedad catalana. Junto con Macabeo y Parellada forma la trilogía del Cava.'],
            ['nombre' => 'Parellada', 'tipo' => 'blanca', 'precocidad' => 'tardia', 'descripcion' => 'Variedad aromática catalana. Aporta finura y acidez al Cava.'],
            ['nombre' => 'Palomino Fino', 'tipo' => 'blanca', 'precocidad' => 'media', 'descripcion' => 'Variedad base del Jerez. Vinos secos y aptos para crianza biológica.'],
            ['nombre' => 'Pedro Ximénez', 'tipo' => 'blanca', 'precocidad' => 'temprana', 'descripcion' => 'Variedad para vinos dulces y licorosos en Montilla-Moriles y Jerez.'],
            ['nombre' => 'Gewürztraminer', 'tipo' => 'blanca', 'precocidad' => 'temprana', 'descripcion' => 'Variedad muy aromática. Notas de rosa, lychee y especias.'],
            ['nombre' => 'Chardonnay', 'tipo' => 'blanca', 'precocidad' => 'temprana', 'descripcion' => 'Variedad internacional versátil. Vinos desde frescos hasta con crianza en madera.'],
            ['nombre' => 'Sauvignon Blanc', 'tipo' => 'blanca', 'precocidad' => 'temprana', 'descripcion' => 'Variedad aromática internacional. Vinos frescos con notas cítricas y herbáceas.'],
            ['nombre' => 'Riesling', 'tipo' => 'blanca', 'precocidad' => 'tardia', 'descripcion' => 'Variedad alemana. Amplio espectro desde secos a dulces con alta acidez.'],
            // Rosadas/Grises
            ['nombre' => 'Garnacha Gris', 'tipo' => 'rosada', 'precocidad' => 'tardia', 'descripcion' => 'Mutación de la Garnacha Tinta. Vinos rosados de gran expresión.'],
            ['nombre' => 'Pinot Gris', 'tipo' => 'rosada', 'precocidad' => 'temprana', 'descripcion' => 'Mutación del Pinot Noir. Vinos blancos con estructura y cuerpo.'],
        ];
        $variedades = array_map(fn ($v) => ['cultivo' => 'vid'] + $v, $variedades);

        $olivos = [
            ['nombre' => 'Cornicabra', 'descripcion' => 'Variedad principal de Toledo y Ciudad Real (DO Montes de Toledo). Aceite estable y afrutado.'],
            ['nombre' => 'Picual', 'descripcion' => 'La más cultivada de España. Aceite muy estable, frutado intenso y amargo.'],
            ['nombre' => 'Hojiblanca', 'descripcion' => 'Doble aptitud, aceite y mesa. Predominante en Córdoba, Málaga y Sevilla.'],
            ['nombre' => 'Arbequina', 'descripcion' => 'Muy productiva y precoz; base de las plantaciones en seto (superintensivas).'],
            ['nombre' => 'Arbosana', 'descripcion' => 'Variedad de bajo vigor para olivar en seto.'],
            ['nombre' => 'Koroneiki', 'descripcion' => 'De origen griego, para olivar en seto. Aceite con mucho frutado.'],
            ['nombre' => 'Manzanilla de Sevilla', 'descripcion' => 'Principalmente aceituna de mesa.'],
            ['nombre' => 'Picudo', 'descripcion' => 'Variedad cordobesa (Priego, Baena). Aceite dulce y aromático.'],
            ['nombre' => 'Empeltre', 'descripcion' => 'Variedad del valle del Ebro y Baleares. Aceite dulce y suave.'],
            ['nombre' => 'Verdial', 'descripcion' => 'Distintas variedades locales de Andalucía y Extremadura con este nombre.'],
        ];

        $pistachos = [
            ['nombre' => 'Kerman', 'descripcion' => 'Hembra. La más plantada en Castilla-La Mancha; fruto grande y de buena apertura.'],
            ['nombre' => 'Larnaka', 'descripcion' => 'Hembra, de origen chipriota. Más precoz que Kerman.'],
            ['nombre' => 'Sirora', 'descripcion' => 'Hembra, de origen australiano. Productiva y de apertura alta.'],
            ['nombre' => 'Avdat', 'descripcion' => 'Hembra, de origen israelí. Floración temprana.'],
            ['nombre' => 'Mateur', 'descripcion' => 'Hembra, de origen tunecino. Bajas necesidades de frío.'],
            ['nombre' => 'Peters', 'descripcion' => 'Macho polinizador habitual para Kerman.'],
            ['nombre' => 'C-Especial', 'descripcion' => 'Macho polinizador de floración tardía.'],
        ];

        $herbaceos = [
            ['nombre' => 'Trigo blando', 'descripcion' => 'Trigo panificable de invierno o de primavera.'],
            ['nombre' => 'Trigo duro', 'descripcion' => 'Trigo para sémola y pasta.'],
            ['nombre' => 'Cebada', 'descripcion' => 'Cereal de secano más extendido; caballar (6 carreras) o cervecera (2 carreras).'],
            ['nombre' => 'Avena', 'descripcion' => 'Cereal de invierno para grano o forraje.'],
            ['nombre' => 'Centeno', 'descripcion' => 'Cereal rústico para suelos pobres y climas fríos.'],
            ['nombre' => 'Triticale', 'descripcion' => 'Híbrido de trigo y centeno, para grano o forraje.'],
            ['nombre' => 'Veza', 'descripcion' => 'Leguminosa forrajera; mejora el suelo en la rotación.'],
            ['nombre' => 'Guisante proteaginoso', 'descripcion' => 'Leguminosa grano para pienso.'],
            ['nombre' => 'Garbanzo', 'descripcion' => 'Leguminosa grano de siembra en primavera o invierno.'],
            ['nombre' => 'Lenteja', 'descripcion' => 'Leguminosa grano de secano.'],
            ['nombre' => 'Yero', 'descripcion' => 'Leguminosa forrajera muy rústica.'],
            ['nombre' => 'Girasol', 'descripcion' => 'Oleaginosa de siembra primaveral.'],
            ['nombre' => 'Colza', 'descripcion' => 'Oleaginosa de invierno.'],
            ['nombre' => 'Barbecho', 'descripcion' => 'Parcela sin cultivo esta campaña (descanso de la rotación).'],
        ];

        foreach (['olivo' => $olivos, 'pistacho' => $pistachos, 'herbaceo' => $herbaceos] as $cultivo => $lista) {
            foreach ($lista as $v) {
                $variedades[] = ['cultivo' => $cultivo, 'tipo' => null, 'precocidad' => null] + $v;
            }
        }

        // Una migración antigua puede ejecutar este seeder antes de que existan algunas
        // columnas: solo se escriben las que hay, y sin 'cultivo' solo las variedades de vid.
        $columnas = Schema::getColumnListing('variedades');

        foreach ($variedades as $variedad) {
            if (!in_array('cultivo', $columnas, true) && $variedad['cultivo'] !== 'vid') {
                continue;
            }

            DB::table('variedades')->updateOrInsert(
                ['nombre' => $variedad['nombre']],
                array_intersect_key($variedad, array_flip($columnas)) + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
