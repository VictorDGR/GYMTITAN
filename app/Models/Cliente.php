<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Cliente extends Model
{
    protected $fillable = ['user_id', 'grupo_id', 'referido_por_id', 'nombre', 'apellido', 'ci', 'email', 'telefono', 'fecha_nacimiento', 'puntos'];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date', 'puntos' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function referidoPor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'referido_por_id');
    }

    public function referidos(): HasMany
    {
        return $this->hasMany(Cliente::class, 'referido_por_id');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoPunto::class);
    }

    public function canjes(): HasMany
    {
        return $this->hasMany(Canje::class);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => "{$this->nombre} {$this->apellido}");
    }

    public function iniciales(): string
    {
        return mb_strtoupper(mb_substr($this->nombre, 0, 1).mb_substr($this->apellido, 0, 1));
    }

    /** Suscripción que cubre el día de hoy. */
    public function suscripcionVigente(): ?Suscripcion
    {
        $hoy = today();

        return $this->suscripciones
            ->filter(fn (Suscripcion $s) => $s->fecha_inicio->lte($hoy) && $s->fecha_fin->gte($hoy))
            ->sortByDesc('fecha_fin')
            ->first();
    }

    /** Último día pagado (incluye renovaciones hechas por adelantado). */
    public function pagadoHasta(): ?Carbon
    {
        return $this->suscripciones->sortByDesc('fecha_fin')->first()?->fecha_fin;
    }

    public function diasRestantes(): int
    {
        $hasta = $this->pagadoHasta();

        return $hasta && $hasta->gte(today()) ? (int) today()->diffInDays($hasta) + 1 : 0;
    }

    public function estado(): string
    {
        if ($this->suscripciones->isEmpty()) {
            return 'Sin suscripción';
        }
        if (! $this->suscripcionVigente()) {
            return 'Vencido';
        }

        return $this->diasRestantes() <= Configuracion::entero('dias_aviso_vencimiento') ? 'Por vencer' : 'Activo';
    }

    public function claseEstado(): string
    {
        return match ($this->estado()) {
            'Activo' => 'ok',
            'Por vencer' => 'warn',
            'Vencido' => 'bad',
            default => 'neutral',
        };
    }

    /** Antigüedad: días realmente cubiertos por suscripciones hasta hoy, en meses de 30 días. */
    public function mesesAntiguedad(): int
    {
        $hoy = today();
        $dias = 0;

        foreach ($this->suscripciones as $s) {
            if ($s->fecha_inicio->gt($hoy)) {
                continue;
            }
            $fin = $s->fecha_fin->lt($hoy) ? $s->fecha_fin : $hoy;
            $dias += (int) $s->fecha_inicio->diffInDays($fin) + 1;
        }

        return intdiv($dias, 30);
    }

    public function fechaIngreso(): ?Carbon
    {
        return $this->suscripciones->sortBy('fecha_inicio')->first()?->fecha_inicio;
    }

    public function nivel(): ?Nivel
    {
        return Nivel::where('meses_minimos', '<=', $this->mesesAntiguedad())->orderByDesc('meses_minimos')->first();
    }

    public function siguienteNivel(): ?Nivel
    {
        return Nivel::where('meses_minimos', '>', $this->mesesAntiguedad())->orderBy('meses_minimos')->first();
    }

    public function cumpleEsteMes(): bool
    {
        return $this->fecha_nacimiento !== null && $this->fecha_nacimiento->month === today()->month;
    }
}
