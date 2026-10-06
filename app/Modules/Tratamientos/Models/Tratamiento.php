<?php

namespace App\Modules\Tratamientos\Models;

use App\Models\User;
use App\Modules\Costes\Models\Coste;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tratamiento extends Model
{
    public const EFICACIAS = ['buena' => 'Buena', 'regular' => 'Regular', 'mala' => 'Mala'];

    protected $fillable = [
        'parcela_id',
        'producto_id',
        'user_id',
        'fecha',
        'hora_inicio',
        'dosis_l_ha',
        'unidad',
        'plazo_seguridad_dias',
        'precio_unitario',
        'motivo',
        'justificacion',
        'superficie_tratada_ha',
        'cultivo_eppo',
        'bbch',
        'aplicador_nombre',
        'aplicador_nif',
        'aplicador_ropo',
        'equipo_roma',
        'equipo_inspeccion_fecha',
        'asesor_nombre',
        'asesor_nif',
        'asesor_ropo',
        'asesor_fecha_validacion',
        'eficacia',
    ];

    /** Justificaciones habituales que se proponen en el formulario (se puede escribir otra). */
    public const JUSTIFICACIONES = [
        'Umbral de intervención superado',
        'Síntomas o daños observados en la parcela',
        'Riesgo según modelo o estación de avisos',
        'Recomendación del asesor',
        'Tratamiento preventivo por condiciones favorables',
    ];

    /** Las inspecciones ITEAF de los equipos de aplicación valen 3 años (RD 1702/2011). */
    public const VIGENCIA_INSPECCION_EQUIPO_ANIOS = 3;

    protected $casts = [
        'fecha'      => 'date',
        'dosis_l_ha' => 'decimal:4',
        'plazo_seguridad_dias' => 'integer',
        'precio_unitario' => 'decimal:2',
        'superficie_tratada_ha' => 'decimal:4',
        'equipo_inspeccion_fecha' => 'date',
        'asesor_fecha_validacion' => 'date',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoFitosanitario::class, 'producto_id');
    }

    /** Coste del producto generado al registrar el tratamiento. */
    public function coste(): HasOne
    {
        return $this->hasOne(Coste::class);
    }

    /** Unidad de la dosis: dosis_l_ha va en litros o kilos por hectárea según el producto. */
    public function unidadDosis(): string
    {
        return ($this->unidad ?? 'l') . '/ha';
    }

    public function unidadPrecio(): string
    {
        return '€/' . ($this->unidad ?? 'l');
    }

    public function superficieTratadaHa(): float
    {
        return (float) ($this->superficie_tratada_ha ?? $this->parcela->superficie_ha);
    }

    /** Coste del producto: dosis × superficie × precio, o null si no se conoce el precio. */
    public function importeProducto(): ?float
    {
        if ($this->precio_unitario === null) {
            return null;
        }

        return round((float) $this->dosis_l_ha * $this->superficieTratadaHa() * (float) $this->precio_unitario, 2);
    }

    public function setAplicadorNifAttribute(?string $valor): void
    {
        $this->attributes['aplicador_nif'] = self::normalizarNif($valor);
    }

    public function setAsesorNifAttribute(?string $valor): void
    {
        $this->attributes['asesor_nif'] = self::normalizarNif($valor);
    }

    private static function normalizarNif(?string $valor): ?string
    {
        return $valor !== null ? strtoupper(str_replace([' ', '-'], '', trim($valor))) : null;
    }

    /** Hora de inicio como HH:MM (la base de datos la devuelve con segundos). */
    public function horaInicio(): ?string
    {
        return $this->hora_inicio ? substr($this->hora_inicio, 0, 5) : null;
    }

    /** Código EPPO del cultivo: el anotado al registrarlo o, en tratamientos anteriores, el de la parcela. */
    public function codigoEppo(): ?string
    {
        return $this->cultivo_eppo ?? ($this->parcela ? ImportadorFitosanitarios::codigoEppoDe($this->parcela) : null);
    }

    /** La inspección del equipo había caducado (o no constaba) el día del tratamiento. */
    public function inspeccionEquipoCaducada(): bool
    {
        return !$this->equipo_inspeccion_fecha
            || $this->equipo_inspeccion_fecha->copy()->addYears(self::VIGENCIA_INSPECCION_EQUIPO_ANIOS)->lt($this->fecha);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** El anotado en el tratamiento (según la etiqueta para ese cultivo y plaga) o, si no, el genérico del producto. */
    public function plazoSeguridadDias(): ?int
    {
        return $this->plazo_seguridad_dias ?? $this->producto?->plazo_seguridad_dias;
    }

    public function fechaFinalPlazoSeguridad(): ?\Carbon\Carbon
    {
        $plazo = $this->plazoSeguridadDias();
        if (!$plazo) {
            return null;
        }
        return $this->fecha->copy()->addDays($plazo);
    }
}
