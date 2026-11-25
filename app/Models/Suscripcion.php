<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends Model
{
    public const METODOS = ['Efectivo', 'QR', 'Tarjeta', 'Transferencia'];

    protected $table = 'suscripciones';

    protected $fillable = ['cliente_id', 'plan_id', 'user_id', 'promocion_id', 'grupo_id', 'lote', 'fecha_inicio', 'fecha_fin', 'precio_base', 'descuento_porcentaje', 'descuento_motivo', 'total', 'metodo_pago', 'puntos_ganados'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'precio_base' => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'total' => 'decimal:2',
            'puntos_ganados' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class);
    }

    public function estado(): string
    {
        $hoy = today();
        if ($this->fecha_inicio->gt($hoy)) {
            return 'Programada';
        }

        return $this->fecha_fin->lt($hoy) ? 'Finalizada' : 'Vigente';
    }

    public function claseEstado(): string
    {
        return match ($this->estado()) {
            'Vigente' => 'ok',
            'Programada' => 'info',
            default => 'neutral',
        };
    }
}
