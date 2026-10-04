<?php

namespace App\Modules\Alertas\Policies;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;

class AlertaPolicy
{
    public function view(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }

    public function update(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }

    public function delete(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }
}
