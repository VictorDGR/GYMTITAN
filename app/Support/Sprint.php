<?php

namespace App\Support;

/** Indica si un módulo ya está habilitado según el avance del tablero (config/sprint.php). */
class Sprint
{
    public static function activo(string $modulo): bool
    {
        $modulos = config('sprint.modulos');

        return $modulos === null || in_array($modulo, $modulos, true);
    }
}
