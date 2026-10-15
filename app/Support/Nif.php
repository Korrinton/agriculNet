<?php

namespace App\Support;

class Nif
{
    /** Mayúsculas y sin espacios ni guiones, como se compara y se imprime en el cuaderno. */
    public static function normalizar(?string $valor): ?string
    {
        return $valor !== null ? strtoupper(str_replace([' ', '-'], '', trim($valor))) : null;
    }
}
