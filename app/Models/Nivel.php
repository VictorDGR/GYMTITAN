<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nivel de antigüedad: se alcanza con meses acumulados de suscripción. */
class Nivel extends Model
{
    protected $table = 'niveles';

    protected $fillable = ['nombre', 'meses_minimos', 'descuento_porcentaje', 'bono_puntos', 'beneficios'];

    protected function casts(): array
    {
        return ['meses_minimos' => 'integer', 'descuento_porcentaje' => 'decimal:2', 'bono_puntos' => 'integer'];
    }
}
