<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class HacerAdministrador extends Command
{
    protected $signature   = 'usuarios:admin {email : Email del usuario} {--quitar : Quitarle el acceso al backoffice}';
    protected $description = 'Da (o quita) acceso al backoffice a un usuario';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No hay ningún usuario con ese email.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('quitar')])->save();

        $this->info($user->is_admin
            ? "{$user->name} ya puede entrar en el backoffice (/admin)."
            : "{$user->name} ya no tiene acceso al backoffice.");

        return self::SUCCESS;
    }
}
