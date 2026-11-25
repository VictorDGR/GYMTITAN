<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Promocion extends Model
{
    public const TIPOS = [
        'fecha_especial' => 'Fecha especial',
        'cumpleanos' => 'Cumpleaños',
        'pareja' => 'Inscripción en pareja',
        'familiar' => 'Inscripción familiar',
    ];

    protected $table = 'promociones';

    protected $fillable = ['nombre', 'tipo', 'porcentaje', 'fecha_inicio', 'fecha_fin', 'min_personas', 'plan_id', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['porcentaje' => 'decimal:2', 'fecha_inicio' => 'date', 'fecha_fin' => 'date', 'activo' => 'boolean', 'min_personas' => 'integer'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Activas y, si son de fecha especial, dentro de su rango de fechas. */
    public function scopeVigentes(Builder $query, ?Carbon $fecha = null): Builder
    {
        $dia = ($fecha ?? today())->toDateString();

        return $query->where('activo', true)->where(fn (Builder $q) => $q
            ->where('tipo', '!=', 'fecha_especial')
            ->orWhere(fn (Builder $q) => $q->whereDate('fecha_inicio', '<=', $dia)->whereDate('fecha_fin', '>=', $dia)));
    }

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function minimoPersonas(): int
    {
        return $this->tipo === 'pareja' ? 2 : ($this->min_personas ?: 3);
    }

    /** Explica en una línea cuándo se aplica. */
    public function condicion(): string
    {
        $plan = $this->plan ? ' · solo plan '.$this->plan->nombre : '';

        return match ($this->tipo) {
            'fecha_especial' => 'Del '.$this->fecha_inicio?->format('d/m/Y').' al '.$this->fecha_fin?->format('d/m/Y').$plan,
            'cumpleanos' => 'En el mes de cumpleaños del cliente'.$plan,
            'pareja' => 'Pareja registrada como grupo (2 personas)'.$plan,
            'familiar' => 'Grupo familiar de '.$this->minimoPersonas().' o más personas'.$plan,
            default => '',
        };
    }
}
