<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Inscripción en pareja o familiar. */
class Grupo extends Model
{
    public const TIPOS = ['pareja' => 'Pareja', 'familiar' => 'Familiar'];

    protected $fillable = ['nombre', 'tipo'];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
