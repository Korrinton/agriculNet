<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Vinedo\Models\Finca;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'bloqueado_at' => 'datetime',
            'ultimo_acceso_at' => 'datetime',
        ];
    }

    public function fincas(): HasMany
    {
        return $this->hasMany(Finca::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_at !== null;
    }

    /** Niveles de alerta que el usuario quiere recibir por correo (vacío = ninguno). */
    public function nivelesAlertasPorCorreo(): array
    {
        return Alerta::PREFERENCIAS_CORREO[$this->alertas_por_correo ?? 'todas']['niveles'] ?? [];
    }
}
