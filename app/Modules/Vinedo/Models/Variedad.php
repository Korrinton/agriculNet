<?php

namespace App\Modules\Vinedo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variedad extends Model
{
    protected $table = 'variedades';

    public const PRECOCIDADES = [
        'temprana'   => 'Temprana',
        'media'      => 'Media',
        'tardia'     => 'Tardía',
        'muy_tardia' => 'Muy tardía',
    ];

    /** Cultivo => [etiqueta del campo en los formularios, nombre del cultivo] */
    public const CULTIVOS = [
        'vid'      => ['campo' => 'Variedad', 'nombre' => 'Vid'],
        'olivo'    => ['campo' => 'Variedad', 'nombre' => 'Olivo'],
        'pistacho' => ['campo' => 'Variedad', 'nombre' => 'Pistacho'],
        'herbaceo' => ['campo' => 'Cultivo',  'nombre' => 'Herbáceos de secano'],
    ];

    protected $fillable = ['nombre', 'cultivo', 'tipo', 'precocidad', 'descripcion'];

    /** Texto de la opción en los selectores: nombre y, en la vid, época de maduración. */
    public function getEtiquetaAttribute(): string
    {
        return $this->precocidad && isset(self::PRECOCIDADES[$this->precocidad])
            ? "{$this->nombre} · maduración " . mb_strtolower(self::PRECOCIDADES[$this->precocidad])
            : $this->nombre;
    }

    protected $casts = [
        'tipo' => 'string',
    ];

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }
}
