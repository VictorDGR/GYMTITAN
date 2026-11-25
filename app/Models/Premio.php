<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Premio extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'puntos', 'stock', 'activo'];

    protected function casts(): array
    {
        return ['puntos' => 'integer', 'stock' => 'integer', 'activo' => 'boolean'];
    }

    public function canjes(): HasMany
    {
        return $this->hasMany(Canje::class);
    }

    public function disponible(): bool
    {
        return $this->activo && ($this->stock === null || $this->stock > 0);
    }
}
