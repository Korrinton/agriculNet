<?php

namespace App\Modules\Vinedo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finca extends Model
{
    protected $fillable = [
        'user_id',
        'provincia_cod',
        'municipio_cod',
        'paraje',
    ];

    protected $casts = [
        'provincia_cod' => 'integer',
        'municipio_cod' => 'integer',
    ];

    private const PROVINCIAS = [
        1  => 'Álava',         2  => 'Albacete',    3  => 'Alicante',
        4  => 'Almería',       5  => 'Ávila',        6  => 'Badajoz',
        7  => 'Baleares',      8  => 'Barcelona',    9  => 'Burgos',
        10 => 'Cáceres',       11 => 'Cádiz',        12 => 'Castellón',
        13 => 'Ciudad Real',   14 => 'Córdoba',      15 => 'La Coruña',
        16 => 'Cuenca',        17 => 'Girona',       18 => 'Granada',
        19 => 'Guadalajara',   20 => 'Guipúzcoa',    21 => 'Huelva',
        22 => 'Huesca',        23 => 'Jaén',         24 => 'León',
        25 => 'Lleida',        26 => 'La Rioja',     27 => 'Lugo',
        28 => 'Madrid',        29 => 'Málaga',       30 => 'Murcia',
        31 => 'Navarra',       32 => 'Ourense',      33 => 'Asturias',
        34 => 'Palencia',      35 => 'Las Palmas',   36 => 'Pontevedra',
        37 => 'Salamanca',     38 => 'S.C. Tenerife',39 => 'Cantabria',
        40 => 'Segovia',       41 => 'Sevilla',      42 => 'Soria',
        43 => 'Tarragona',     44 => 'Teruel',       45 => 'Toledo',
        46 => 'Valencia',      47 => 'Valladolid',   48 => 'Vizcaya',
        49 => 'Zamora',        50 => 'Zaragoza',
    ];

    public function getProvinciaNombreAttribute(): string
    {
        return self::PROVINCIAS[$this->provincia_cod] ?? "Provincia {$this->provincia_cod}";
    }

    public static function getProvincias(): array
    {
        return self::PROVINCIAS;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }
}
