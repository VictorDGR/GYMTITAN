<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = ['nombre', 'duracion_dias', 'precio', 'puntos', 'activo'];

    protected function casts(): array
    {
        return ['precio' => 'decimal:2', 'activo' => 'boolean', 'duracion_dias' => 'integer', 'puntos' => 'integer'];
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }
}
