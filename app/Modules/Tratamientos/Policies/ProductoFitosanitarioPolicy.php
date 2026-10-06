<?php

namespace App\Modules\Tratamientos\Policies;

use App\Models\User;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;

/** Los productos del registro oficial son de solo lectura; cada usuario gestiona los suyos. */
class ProductoFitosanitarioPolicy
{
    /** Cualquier producto que el usuario vea (del registro o suyo) puede llevar su precio. */
    public function fijarPrecio(User $user, ProductoFitosanitario $producto): bool
    {
        return $producto->user_id === null || $user->id === $producto->user_id;
    }

    public function update(User $user, ProductoFitosanitario $producto): bool
    {
        return $user->id === $producto->user_id;
    }

    public function delete(User $user, ProductoFitosanitario $producto): bool
    {
        return $user->id === $producto->user_id;
    }
}
