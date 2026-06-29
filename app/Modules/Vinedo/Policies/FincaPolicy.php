<?php

namespace App\Modules\Vinedo\Policies;

use App\Models\User;
use App\Modules\Vinedo\Models\Finca;

class FincaPolicy
{
    public function view(User $user, Finca $finca): bool
    {
        return $user->id === $finca->user_id;
    }

    public function update(User $user, Finca $finca): bool
    {
        return $user->id === $finca->user_id;
    }

    public function delete(User $user, Finca $finca): bool
    {
        return $user->id === $finca->user_id;
    }
}
