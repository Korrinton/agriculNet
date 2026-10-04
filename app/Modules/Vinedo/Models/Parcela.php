<?php

namespace App\Modules\Vinedo\Models;

use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\Coste;
use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\Tratamiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcela extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'finca_id',
        'variedad_id',
        'nombre',
        'uso',
        'agregado',
        'poligono',
        'parcela_sigpac',
        'recinto',
        'superficie_ha',
        'año_plantacion',
        'sistema_conduccion',
    ];

    protected $casts = [
        'superficie_ha'  => 'decimal:4',
        'año_plantacion' => 'integer',
        'agregado'       => 'integer',
        'poligono'       => 'integer',
        'parcela_sigpac' => 'integer',
        'recinto'        => 'integer',
    ];

    /** Usos de la parcela y el cultivo de las variedades que admite cada uno. */
    public const CULTIVO_POR_USO = [
        'Secano'            => 'herbaceo',
        'Viña en espaldera' => 'vid',
        'Viña en vaso'      => 'vid',
        'Olivar'            => 'olivo',
        'Pistachos'         => 'pistacho',
    ];

    public static function usos(): array
    {
        return array_keys(self::CULTIVO_POR_USO);
    }

    public static function cultivoDeUso(?string $uso): ?string
    {
        return self::CULTIVO_POR_USO[$uso] ?? null;
    }

    /** Referencia SIGPAC completa: provincia:municipio:agregado:zona:polígono:parcela:recinto. */
    public function getReferenciaSigpacAttribute(): ?string
    {
        if (!$this->poligono || !$this->parcela_sigpac || !$this->finca) {
            return null;
        }

        return sprintf(
            '%d:%d:%d:0:%d:%d:%s',
            $this->finca->provincia_cod, $this->finca->municipio_cod, $this->agregado ?? 0,
            $this->poligono, $this->parcela_sigpac, $this->recinto ?? '—',
        );
    }

    /** Nombre corto para listados: «Pol. 70 · Par. 126» o el nombre si no hay referencia. */
    public function getEtiquetaAttribute(): string
    {
        return $this->poligono && $this->parcela_sigpac
            ? "Pol. {$this->poligono} · Par. {$this->parcela_sigpac}" . ($this->recinto ? " · Rec. {$this->recinto}" : '')
            : $this->nombre;
    }

    public function cultivo(): ?string
    {
        return self::cultivoDeUso($this->uso);
    }

    /** El calendario fenológico (escala BBCH de la vid) y las alertas de mildiu y helada son solo de viña. */
    public function esVina(): bool
    {
        return $this->cultivo() === 'vid';
    }

    /** Las parcelas de secano no se riegan. */
    public function esRegable(): bool
    {
        return $this->uso !== 'Secano';
    }

    public function scopeRegable(Builder $query): Builder
    {
        return $query->where('uso', '!=', 'Secano');
    }

    public function scopeVina(Builder $query): Builder
    {
        return $query->whereIn('uso', array_keys(array_filter(self::CULTIVO_POR_USO, fn ($c) => $c === 'vid')));
    }

    protected static function booted(): void
    {
        // La ubicación de la finca sale de la geometría SIGPAC de sus parcelas
        static::saved(function (Parcela $parcela) {
            if ($parcela->wasRecentlyCreated || $parcela->wasChanged(['agregado', 'poligono', 'parcela_sigpac', 'recinto'])) {
                $parcela->finca?->olvidarCoordenadas();
            }
        });

        static::deleted(fn (Parcela $parcela) => $parcela->finca?->olvidarCoordenadas());
        static::restored(fn (Parcela $parcela) => $parcela->finca?->olvidarCoordenadas());
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    public function variedad(): BelongsTo
    {
        return $this->belongsTo(Variedad::class);
    }

    public function registrosFenologicos(): HasMany
    {
        return $this->hasMany(RegistroFenologico::class);
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class);
    }

    public function costes(): HasMany
    {
        return $this->hasMany(Coste::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function fertilizaciones(): HasMany
    {
        return $this->hasMany(Fertilizacion::class);
    }

    public function cosechas(): HasMany
    {
        return $this->hasMany(Cosecha::class);
    }

    public function riegos(): HasMany
    {
        return $this->hasMany(Riego::class);
    }
}
