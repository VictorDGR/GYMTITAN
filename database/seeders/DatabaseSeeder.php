<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Premio;
use App\Models\Promocion;
use App\Models\Suscripcion;
use App\Models\User;
use App\Services\Fidelizacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Datos de ejemplo para probar el programa de fidelización. */
class DatabaseSeeder extends Seeder
{
    private User $recepcion;

    public function run(): void
    {
        // Solo se cargan una vez: en cada despliegue se ejecuta el seeder y no debe duplicar datos.
        if (User::exists()) {
            return;
        }

        User::create(['name' => 'Administrador', 'email' => 'admin@titangym.com', 'password' => env('ADMIN_PASSWORD', 'admin123'), 'rol' => 'administrador']);
        $this->recepcion = User::create(['name' => 'Lucía Mamani', 'email' => 'recepcion@titangym.com', 'ci' => '5123456', 'password' => 'recepcion123', 'rol' => 'recepcionista']);

        foreach (Configuracion::PARAMETROS as $clave => [, , $valor]) {
            Configuracion::guardar($clave, $valor);
        }

        $mensual = Plan::create(['nombre' => 'Mensual', 'duracion_dias' => 30, 'precio' => 150, 'puntos' => 50]);
        $trimestral = Plan::create(['nombre' => 'Trimestral', 'duracion_dias' => 90, 'precio' => 400, 'puntos' => 160]);
        $semestral = Plan::create(['nombre' => 'Semestral', 'duracion_dias' => 180, 'precio' => 750, 'puntos' => 350]);
        $anual = Plan::create(['nombre' => 'Anual', 'duracion_dias' => 365, 'precio' => 1400, 'puntos' => 800]);

        Nivel::create(['nombre' => 'Bronce', 'meses_minimos' => 0, 'descuento_porcentaje' => 0, 'bono_puntos' => 0, 'beneficios' => 'Acceso al programa de puntos']);
        Nivel::create(['nombre' => 'Plata', 'meses_minimos' => 6, 'descuento_porcentaje' => 5, 'bono_puntos' => 150, 'beneficios' => '5% al renovar y bono de 150 puntos']);
        Nivel::create(['nombre' => 'Oro', 'meses_minimos' => 12, 'descuento_porcentaje' => 10, 'bono_puntos' => 300, 'beneficios' => '10% al renovar, bono de 300 puntos y evaluación física gratis']);
        Nivel::create(['nombre' => 'Platino', 'meses_minimos' => 24, 'descuento_porcentaje' => 15, 'bono_puntos' => 600, 'beneficios' => '15% al renovar, bono de 600 puntos y polera exclusiva']);

        Promocion::create(['nombre' => 'Noviembre Fit', 'tipo' => 'fecha_especial', 'porcentaje' => 10, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30', 'descripcion' => 'Prepárate para fin de año con 10% en cualquier plan.']);
        Promocion::create(['nombre' => 'Navidad Titán', 'tipo' => 'fecha_especial', 'porcentaje' => 20, 'fecha_inicio' => '2026-12-15', 'fecha_fin' => '2026-12-31', 'descripcion' => 'Regala salud: 20% en todos los planes.']);
        Promocion::create(['nombre' => 'Año nuevo, vida nueva', 'tipo' => 'fecha_especial', 'porcentaje' => 25, 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-01-15', 'plan_id' => $anual->id, 'descripcion' => 'Empieza el año con 25% en el plan anual.']);
        Promocion::create(['nombre' => 'Mes de tu cumpleaños', 'tipo' => 'cumpleanos', 'porcentaje' => 15, 'descripcion' => 'Te regalamos 15% en el mes de tu cumpleaños.']);
        $pareja = Promocion::create(['nombre' => 'Entrena en pareja', 'tipo' => 'pareja', 'porcentaje' => 10, 'descripcion' => 'Inscríbanse juntos y cada uno paga 10% menos.']);
        $familiar = Promocion::create(['nombre' => 'Plan familiar', 'tipo' => 'familiar', 'porcentaje' => 15, 'min_personas' => 3, 'descripcion' => 'Familias de 3 o más personas: 15% para cada integrante.']);

        Premio::create(['nombre' => 'Batido proteico', 'descripcion' => 'Un batido en la barra del gimnasio', 'puntos' => 150]);
        Premio::create(['nombre' => 'Pase de una semana para un amigo', 'descripcion' => 'Invita a alguien a entrenar 7 días', 'puntos' => 350]);
        Premio::create(['nombre' => 'Botella deportiva Titán', 'descripcion' => 'Botella de 750 ml', 'puntos' => 300, 'stock' => 20]);
        Premio::create(['nombre' => 'Toalla deportiva', 'descripcion' => 'Toalla de microfibra', 'puntos' => 400, 'stock' => 15]);
        Premio::create(['nombre' => 'Sesión con entrenador personal', 'descripcion' => '1 hora con rutina personalizada', 'puntos' => 500]);
        $polera = Premio::create(['nombre' => 'Polera Titán', 'descripcion' => 'Polera de entrenamiento', 'puntos' => 600, 'stock' => 10]);
        Premio::create(['nombre' => 'Mes gratis', 'descripcion' => 'Un plan mensual sin costo', 'puntos' => 1200]);

        // María: socia desde hace más de un año (nivel Oro), cumple años en noviembre.
        $maria = $this->cliente('María', 'Gutiérrez', '7845123', 'maria.gutierrez@gmail.com', '70745321', '1990-11-12');
        $this->suscribir($maria, $anual, '2025-09-15');
        $this->suscribir($maria, $mensual, '2026-09-15', 10, 'Nivel Oro (antigüedad)');
        $this->suscribir($maria, $mensual, '2026-10-15', 10, 'Nivel Oro (antigüedad)');

        // Jorge y Ana: inscritos como pareja. Ana llegó recomendada por María.
        $grupoPareja = Grupo::create(['nombre' => 'Fernández – Vargas', 'tipo' => 'pareja']);
        $jorge = $this->cliente('Jorge', 'Fernández', '6123098', 'jorge.fernandez@gmail.com', '72011548', '1985-08-21', $grupoPareja);
        $ana = $this->cliente('Ana', 'Vargas', '9034571', 'ana.vargas@gmail.com', '63490812', '1996-01-05', $grupoPareja, $maria);
        $this->suscribir($jorge, $semestral, '2026-05-10');
        $this->suscribir($ana, $mensual, '2026-10-20', 10, $pareja->nombre, $pareja->id);
        app(Fidelizacion::class)->moverPuntos($maria, 'referido', 100, 'Recomendaste a Ana Vargas');

        // Familia Rojas: Carlos lleva dos años (Platino) y su membresía venció hace poco.
        $familia = Grupo::create(['nombre' => 'Familia Rojas', 'tipo' => 'familiar']);
        $carlos = $this->cliente('Carlos', 'Rojas', '4567890', 'carlos.rojas@gmail.com', '72345678', '1979-03-30', $familia);
        $sofia = $this->cliente('Sofía', 'Rojas', '4987654', 'sofia.rojas@gmail.com', '73456789', '1982-06-14', $familia);
        $diego = $this->cliente('Diego', 'Rojas', '13456789', 'diego.rojas@gmail.com', '74567890', '2008-02-02', $familia);
        $this->suscribir($carlos, $anual, '2024-10-20');
        $this->suscribir($carlos, $anual, '2025-10-20', 15, $familiar->nombre, $familiar->id);
        $this->suscribir($sofia, $trimestral, '2026-09-01', 15, $familiar->nombre, $familiar->id);
        $this->suscribir($diego, $mensual, '2026-10-08', 15, $familiar->nombre, $familiar->id);

        // Camila: dos semestres seguidos (nivel Plata).
        $camila = $this->cliente('Camila', 'Torrez', '8812345', 'camila.torrez@gmail.com', '76543210', '1999-07-19');
        $this->suscribir($camila, $semestral, '2026-03-01');
        $this->suscribir($camila, $semestral, '2026-08-28');

        // Luis: probó un mes y no volvió.
        $luis = $this->cliente('Luis', 'Quispe', '9988776', 'luis.quispe@gmail.com', '77889900', '2001-12-01');
        $this->suscribir($luis, $mensual, '2026-07-01');

        // Valeria: recién registrada, recomendada por Jorge; aún no paga su primera suscripción.
        $this->cliente('Valeria', 'Choque', '10293847', 'valeria.choque@gmail.com', '78901234', '2003-04-25', null, $jorge);

        foreach (Cliente::with('suscripciones')->get() as $cliente) {
            // Fecha de registro = día de su primera suscripción (Valeria se registró hoy).
            $cliente->created_at = $cliente->fechaIngreso() ?? now();
            $cliente->save();
            app(Fidelizacion::class)->revisarNivel($cliente);
        }

        // Canjes de ejemplo
        $carlos->refresh();
        app(Fidelizacion::class)->canjear($carlos, $polera, $this->recepcion);
        $maria->refresh();
        app(Fidelizacion::class)->canjear($maria, Premio::where('nombre', 'Batido proteico')->first(), $this->recepcion);
    }

    private function cliente(string $nombre, string $apellido, string $ci, string $email, string $telefono, string $nacimiento, ?Grupo $grupo = null, ?Cliente $referidoPor = null): Cliente
    {
        $user = User::create(['name' => "$nombre $apellido", 'email' => $email, 'ci' => $ci, 'password' => $ci, 'rol' => 'cliente']);

        return $user->cliente()->create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'ci' => $ci,
            'email' => $email,
            'telefono' => $telefono,
            'fecha_nacimiento' => $nacimiento,
            'grupo_id' => $grupo?->id,
            'referido_por_id' => $referidoPor?->id,
        ]);
    }

    /** Suscripción histórica con su fecha real de pago y los puntos que generó. */
    private function suscribir(Cliente $cliente, Plan $plan, string $inicio, float $porcentaje = 0, ?string $motivo = null, ?int $promocionId = null): void
    {
        $fecha = Carbon::parse($inicio);
        $total = round($plan->precio * (100 - $porcentaje) / 100, 2);

        $suscripcion = new Suscripcion([
            'cliente_id' => $cliente->id,
            'plan_id' => $plan->id,
            'user_id' => $this->recepcion->id,
            'promocion_id' => $promocionId,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha->copy()->addDays($plan->duracion_dias - 1),
            'precio_base' => $plan->precio,
            'descuento_porcentaje' => $porcentaje,
            'descuento_motivo' => $porcentaje > 0 ? $motivo : null,
            'total' => $total,
            'metodo_pago' => $porcentaje > 0 ? 'QR' : 'Efectivo',
            'puntos_ganados' => $plan->puntos,
        ]);
        $suscripcion->created_at = $fecha->copy()->setTime(9, 30);
        $suscripcion->save();

        $movimiento = app(Fidelizacion::class)->moverPuntos($cliente, 'suscripcion', $plan->puntos, 'Pago del plan '.$plan->nombre, ['suscripcion_id' => $suscripcion->id, 'user_id' => $this->recepcion->id]);
        $movimiento->created_at = $suscripcion->created_at;
        $movimiento->save();
    }
}
