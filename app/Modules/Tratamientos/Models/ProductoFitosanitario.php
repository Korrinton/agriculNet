<?php

namespace App\Modules\Tratamientos\Models;

use App\Models\User;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ProductoFitosanitario extends Model
{
    /** Ficha oficial del producto (usos autorizados, dosis, plazos de seguridad) en el Registro del MAPA. */
    public const URL_FICHA_MAPA = 'https://servicio.mapa.gob.es/regfiweb/Productos/ExportFichaProductoPdfGet?idProducto=';

    /** Unidad en la que se dosifica (por ha) y se compra: líquidos en litros, sólidos en kilos. */
    public const UNIDADES = ['l' => 'Litros (líquido)', 'kg' => 'Kilos (sólido)'];

    protected $table = 'productos_fitosanitarios';

    protected $fillable = [
        'user_id',
        'mapa_id',
        'nombre',
        'numero_registro',
        'ingrediente_activo',
        'titular',
        'vigente',
        'fecha_caducidad',
        'cultivos',
        'plazo_seguridad_dias',
        'dosis_max_l_ha',
        'unidad',
    ];

    protected $casts = [
        'plazo_seguridad_dias' => 'integer',
        'dosis_max_l_ha'       => 'decimal:3',
        'vigente'              => 'boolean',
        'fecha_caducidad'      => 'date',
        'cultivos'             => 'array',
        'ficha_leida_at'       => 'datetime',
    ];

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'producto_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Plazos de seguridad oficiales por cultivo, leídos de la ficha del registro. */
    public function plazosSeguridad(): HasMany
    {
        return $this->hasMany(PlazoSeguridadProducto::class, 'producto_id');
    }

    /** Plazo oficial en un cultivo: días, 0 si no procede, null si la ficha no lo da (o no se ha leído). */
    public function plazoOficialPara(?string $cultivo): ?int
    {
        $plazo = $cultivo ? $this->plazosSeguridad->firstWhere('cultivo', $cultivo) : null;

        return $plazo ? ($plazo->dias ?? 0) : null;
    }

    public function precios(): HasMany
    {
        return $this->hasMany(PrecioProductoFitosanitario::class, 'producto_id');
    }

    /** Añade la columna «precio»: lo que paga el usuario por el producto (null si no lo ha anotado). */
    public function scopeConPrecioDe(Builder $query, User $user): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('productos_fitosanitarios.*');
        }

        return $query->addSelect(['precio' => PrecioProductoFitosanitario::select('precio')
            ->whereColumn('producto_id', 'productos_fitosanitarios.id')
            ->where('user_id', $user->id)]);
    }

    /** Guarda (o borra, con null) el precio que paga el usuario por el producto. */
    public function fijarPrecio(User $user, int|float|string|null $precio): void
    {
        if ($precio === null || $precio === '') {
            $this->precios()->where('user_id', $user->id)->delete();
            return;
        }

        $this->precios()->updateOrCreate(['user_id' => $user->id], ['precio' => $precio]);
    }

    public function esPropio(): bool
    {
        return $this->user_id !== null;
    }

    public function urlFichaMapa(): ?string
    {
        return $this->mapa_id ? self::URL_FICHA_MAPA . $this->mapa_id : null;
    }

    /** Productos del registro oficial más los propios del usuario. */
    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id));
    }

    /** Regla de validación: el producto existe y es del registro o del propio usuario. */
    public static function reglaVisiblePara(User $user): Exists
    {
        return Rule::exists('productos_fitosanitarios', 'id')
            ->where(fn ($q) => $q->where(fn ($w) => $w->whereNull('user_id')->orWhere('user_id', $user->id)));
    }

    /**
     * Productos que se pueden aplicar a un cultivo de la app: los del registro autorizados para él
     * y los propios del usuario (de los que no hay datos de autorización). Los cultivos que el
     * importador no clasifica (herbáceos) no se filtran.
     */
    public function scopeParaCultivo(Builder $query, ?string $cultivo): Builder
    {
        return $query->paraCultivos([$cultivo]);
    }

    /** Productos que se pueden aplicar al menos a uno de los cultivos (tratamiento de una finca entera). */
    public function scopeParaCultivos(Builder $query, array $cultivos): Builder
    {
        $cultivos = array_unique($cultivos);
        foreach ($cultivos as $cultivo) {
            if (!$cultivo || !array_key_exists($cultivo, ImportadorFitosanitarios::CULTIVOS_REGISTRO)) {
                return $query;
            }
        }

        return $query->where(function ($q) use ($cultivos) {
            $q->whereNull('cultivos');
            foreach ($cultivos as $cultivo) {
                $q->orWhereJsonContains('cultivos', $cultivo);
            }
        });
    }

    public function autorizadoPara(?string $cultivo): ?bool
    {
        if ($this->cultivos === null || !$cultivo
            || !array_key_exists($cultivo, ImportadorFitosanitarios::CULTIVOS_REGISTRO)) {
            return null;
        }

        return in_array($cultivo, $this->cultivos, true);
    }
}
