<?php

namespace App\Services;

use App\Models\Canje;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Grupo;
use App\Models\MovimientoPunto;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Premio;
use App\Models\Promocion;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Reglas del programa de fidelización: descuentos, puntos, referidos, antigüedad y canjes. */
class Fidelizacion
{
    /**
     * Todos los descuentos a los que el cliente tiene derecho para un plan.
     * Los descuentos no se acumulan: se aplica uno solo.
     * Las promociones de pareja y familiar solo valen al cobrar al grupo completo ($grupal).
     *
     * @return Collection<int, array{clave: string, motivo: string, porcentaje: float, promocion_id: ?int}>
     */
    public function descuentosDisponibles(Cliente $cliente, Plan $plan, bool $grupal = false): Collection
    {
        $cliente->loadMissing('grupo.clientes', 'suscripciones');
        $opciones = collect();

        foreach (Promocion::vigentes()->get() as $promo) {
            if ($promo->plan_id && $promo->plan_id !== $plan->id) {
                continue;
            }

            $esDeGrupo = in_array($promo->tipo, ['pareja', 'familiar'], true);
            if ($esDeGrupo !== $grupal) {
                continue;
            }

            $aplica = match ($promo->tipo) {
                'fecha_especial' => true,
                'cumpleanos' => $cliente->cumpleEsteMes(),
                'pareja', 'familiar' => $cliente->grupo?->tipo === $promo->tipo
                    && $cliente->grupo->clientes->count() >= $promo->minimoPersonas(),
                default => false,
            };

            if ($aplica) {
                $opciones->push(['clave' => 'promo-'.$promo->id, 'motivo' => $promo->nombre, 'porcentaje' => (float) $promo->porcentaje, 'promocion_id' => $promo->id]);
            }
        }

        // El descuento por nivel corresponde a HU-009 (niveles de antigüedad).
        $nivel = ! $grupal && \App\Support\Sprint::activo('niveles') ? $cliente->nivel() : null;
        if ($nivel && $nivel->descuento_porcentaje > 0) {
            $opciones->push(['clave' => 'nivel', 'motivo' => 'Nivel '.$nivel->nombre.' (antigüedad)', 'porcentaje' => (float) $nivel->descuento_porcentaje, 'promocion_id' => null]);
        }

        return $opciones->sortByDesc('porcentaje')->values();
    }

    /**
     * Precio del plan con un solo descuento. Por defecto se propone el mayor, pero la
     * recepción puede elegir otro de los disponibles ($clave) o 'ninguno'.
     *
     * @return array{precio_base: float, porcentaje: float, motivo: ?string, promocion_id: ?int, clave: ?string, total: float, otros: Collection, opciones: Collection}
     */
    public function cotizar(Cliente $cliente, Plan $plan, ?string $clave = null, bool $grupal = false): array
    {
        $opciones = $this->descuentosDisponibles($cliente, $plan, $grupal);
        $mejor = match (true) {
            $clave === 'ninguno' => null,
            $clave !== null && $opciones->contains('clave', $clave) => $opciones->firstWhere('clave', $clave),
            default => $opciones->first(),
        };
        $precio = (float) $plan->precio;
        $porcentaje = $mejor['porcentaje'] ?? 0.0;

        return [
            'precio_base' => $precio,
            'porcentaje' => $porcentaje,
            'motivo' => $mejor['motivo'] ?? null,
            'promocion_id' => $mejor['promocion_id'] ?? null,
            'clave' => $mejor['clave'] ?? null,
            'total' => round($precio * (100 - $porcentaje) / 100, 2),
            'otros' => $opciones->slice(1)->values(),
            'opciones' => $opciones->map(fn ($o) => $o + ['total' => round($precio * (100 - $o['porcentaje']) / 100, 2)]),
        ];
    }

    /** Si el cliente aún tiene días pagados, la renovación empieza al día siguiente del vencimiento. */
    public function fechaInicio(Cliente $cliente): Carbon
    {
        $hasta = $cliente->pagadoHasta();

        return $hasta && $hasta->gte(today()) ? $hasta->copy()->addDay() : today();
    }

    public function registrarSuscripcion(Cliente $cliente, Plan $plan, string $metodoPago, User $registrador, ?string $descuento = null, ?Grupo $grupo = null, ?string $lote = null): Suscripcion
    {
        return DB::transaction(function () use ($cliente, $plan, $metodoPago, $registrador, $descuento, $grupo, $lote): Suscripcion {
            $cliente->load('suscripciones');
            $esPrimera = $cliente->suscripciones->isEmpty();
            $cotizacion = $this->cotizar($cliente, $plan, $descuento, $grupo !== null);
            $inicio = $this->fechaInicio($cliente);

            $suscripcion = $cliente->suscripciones()->create([
                'plan_id' => $plan->id,
                'user_id' => $registrador->id,
                'promocion_id' => $cotizacion['promocion_id'],
                'grupo_id' => $grupo?->id,
                'lote' => $lote,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $inicio->copy()->addDays($plan->duracion_dias - 1),
                'precio_base' => $cotizacion['precio_base'],
                'descuento_porcentaje' => $cotizacion['porcentaje'],
                'descuento_motivo' => $cotizacion['motivo'],
                'total' => $cotizacion['total'],
                'metodo_pago' => $metodoPago,
                'puntos_ganados' => $plan->puntos,
            ]);

            if ($plan->puntos > 0) {
                $this->moverPuntos($cliente, 'suscripcion', $plan->puntos, 'Pago del plan '.$plan->nombre, ['suscripcion_id' => $suscripcion->id, 'user_id' => $registrador->id]);
            }

            // Referido: premia a quien lo recomendó cuando el nuevo cliente paga por primera vez.
            $puntosReferido = Configuracion::entero('puntos_referido');
            if ($esPrimera && $cliente->referidoPor && $puntosReferido > 0) {
                $this->moverPuntos($cliente->referidoPor, 'referido', $puntosReferido, 'Recomendaste a '.$cliente->nombre_completo, ['suscripcion_id' => $suscripcion->id]);
            }

            $cliente->unsetRelation('suscripciones');
            $this->revisarNivel($cliente);

            return $suscripcion;
        });
    }

    /**
     * Cobra el mismo plan a todos los integrantes de una pareja o familia en un solo pago.
     * Cada integrante recibe su suscripción, sus puntos y la promoción de grupo elegida.
     *
     * @return string el código del lote (comprobante grupal)
     */
    public function registrarGrupo(Grupo $grupo, Plan $plan, string $metodoPago, User $registrador, ?string $descuento = null): string
    {
        $grupo->load('clientes');
        if ($grupo->clientes->count() < 2) {
            throw ValidationException::withMessages(['grupo_id' => 'El grupo necesita al menos 2 integrantes para cobrar en grupo.']);
        }

        return DB::transaction(function () use ($grupo, $plan, $metodoPago, $registrador, $descuento): string {
            $lote = (string) Str::uuid();
            foreach ($grupo->clientes as $cliente) {
                $this->registrarSuscripcion($cliente, $plan, $metodoPago, $registrador, $descuento, $grupo, $lote);
            }

            return $lote;
        });
    }

    /** Entrega una sola vez el bono de cada nivel de antigüedad alcanzado. */
    public function revisarNivel(Cliente $cliente): void
    {
        $meses = $cliente->mesesAntiguedad();
        $entregados = $cliente->movimientos()->whereNotNull('nivel_id')->pluck('nivel_id');

        Nivel::where('meses_minimos', '<=', $meses)
            ->where('meses_minimos', '>', 0)
            ->where('bono_puntos', '>', 0)
            ->whereNotIn('id', $entregados)
            ->orderBy('meses_minimos')
            ->get()
            ->each(fn (Nivel $nivel) => $this->moverPuntos(
                $cliente, 'antiguedad', $nivel->bono_puntos,
                'Alcanzaste el nivel '.$nivel->nombre.' ('.$nivel->meses_minimos.' meses)',
                ['nivel_id' => $nivel->id]
            ));
    }

    public function canjear(Cliente $cliente, Premio $premio, User $registrador): Canje
    {
        return DB::transaction(function () use ($cliente, $premio, $registrador): Canje {
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);
            $premio = Premio::lockForUpdate()->findOrFail($premio->id);

            if (! $premio->disponible()) {
                throw ValidationException::withMessages(['premio_id' => 'Este premio no está disponible.']);
            }
            if ($cliente->puntos < $premio->puntos) {
                throw ValidationException::withMessages(['premio_id' => 'El cliente tiene '.$cliente->puntos.' puntos y el premio cuesta '.$premio->puntos.'.']);
            }

            $canje = Canje::create(['cliente_id' => $cliente->id, 'premio_id' => $premio->id, 'user_id' => $registrador->id, 'puntos' => $premio->puntos]);

            if ($premio->stock !== null) {
                $premio->decrement('stock');
            }

            $this->moverPuntos($cliente, 'canje', -$premio->puntos, 'Canje: '.$premio->nombre, ['canje_id' => $canje->id, 'user_id' => $registrador->id]);

            return $canje;
        });
    }

    public function moverPuntos(Cliente $cliente, string $tipo, int $puntos, string $descripcion, array $extra = []): MovimientoPunto
    {
        $movimiento = $cliente->movimientos()->create(['tipo' => $tipo, 'puntos' => $puntos, 'descripcion' => $descripcion] + $extra);
        $cliente->increment('puntos', $puntos);

        return $movimiento;
    }
}
