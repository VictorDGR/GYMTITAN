<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoPunto extends Model
{
    public const TIPOS = [
        'suscripcion' => 'Suscripción',
        'referido' => 'Referido',
        'antiguedad' => 'Antigüedad',
        'canje' => 'Canje',
        'ajuste' => 'Ajuste',
    ];

    protected $table = 'movimientos_puntos';

    protected $fillable = ['cliente_id', 'tipo', 'puntos', 'descripcion', 'suscripcion_id', 'canje_id', 'nivel_id', 'user_id'];

    protected function casts(): array
    {
        return ['puntos' => 'integer'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
