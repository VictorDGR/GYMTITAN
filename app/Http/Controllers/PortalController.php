<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Premio;
use App\Models\Promocion;
use App\Models\Suscripcion;
use App\Services\Fidelizacion;
use App\Support\Validacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Portal del socio (HU-012): inicio con promociones y planes, puntos y premios, historial,
 * membresía, pago en línea de su plan y configuración de su cuenta.
 */
class PortalController extends Controller
{
    public function __construct(private Fidelizacion $fidelizacion) {}

    /** Inicio: promociones vigentes en carrusel y los planes para elegir. */
    public function inicio(Request $request): View
    {
        $cliente = $this->cliente($request);

        return view('portal.inicio', [
            'cliente' => $cliente,
            'promociones' => $this->promociones($cliente),
            'planes' => $this->planes($cliente),
        ]);
    }

    /** Puntos y premios, con las promociones vigentes y su detalle. */
    public function puntos(Request $request): View
    {
        $cliente = $this->cliente($request);

        return view('portal.puntos', [
            'cliente' => $cliente,
            'premios' => Premio::where('activo', true)->orderBy('puntos')->get(),
            'promociones' => $this->promociones($cliente),
        ]);
    }

    /** Historial de puntos (ganados y utilizados) y código de recomendación. */
    public function historial(Request $request): View
    {
        $cliente = $this->cliente($request);
        $movimientos = $cliente->movimientos()->latest()->latest('id')->get();

        return view('portal.historial', [
            'cliente' => $cliente,
            'ganados' => $movimientos->where('puntos', '>', 0)->values(),
            'utilizados' => $movimientos->where('puntos', '<', 0)->values(),
            'puntosReferido' => Configuracion::entero('puntos_referido'),
        ]);
    }

    /** Membresía vigente, días restantes, suscripciones y premios por antigüedad. */
    public function membresia(Request $request): View
    {
        $cliente = $this->cliente($request);

        return view('portal.membresia', [
            'cliente' => $cliente,
            'vigente' => $cliente->suscripcionVigente(),
            'meses' => $cliente->mesesAntiguedad(),
            'nivel' => $cliente->nivel(),
            'siguiente' => $cliente->siguienteNivel(),
            'niveles' => Nivel::orderBy('meses_minimos')->get(),
        ]);
    }

    /** Pago en línea del socio: elige plan y promoción, y paga con QR o tarjeta. */
    public function pagar(Request $request): View
    {
        $cliente = $this->cliente($request);
        $planes = $this->planes($cliente);
        $promo = $request->filled('promo') ? Promocion::find($request->integer('promo')) : null;

        $planId = $request->integer('plan') ?: ($promo?->plan_id ?: ($planes->first()['plan']->id ?? null));
        $aviso = null;
        if ($promo && ! $planes->contains(fn ($p) => $p['opciones']->contains('clave', 'promo-'.$promo->id))) {
            $aviso = in_array($promo->tipo, ['pareja', 'familiar'], true)
                ? 'La promoción «'.$promo->nombre.'» se paga en grupo: acércate a recepción con tu '.($promo->tipo === 'pareja' ? 'pareja' : 'familia').'. Mientras tanto, te mostramos tu mejor descuento individual.'
                : 'La promoción «'.$promo->nombre.'» no aplica para ti en este momento. Te mostramos tu mejor descuento disponible.';
        }

        return view('portal.pagar', [
            'cliente' => $cliente,
            'planes' => $planes,
            'planId' => $planId,
            'clave' => $promo && ! $aviso ? 'promo-'.$promo->id : null,
            'aviso' => $aviso,
            'inicio' => $this->fidelizacion->fechaInicio($cliente),
        ]);
    }

    public function pagarStore(Request $request): RedirectResponse
    {
        $cliente = $this->cliente($request);
        $datos = $request->validate([
            'plan_id' => ['required', Rule::exists('planes', 'id')->where('activo', true)],
            'metodo_pago' => ['required', Rule::in(['QR', 'Tarjeta'])],
            'descuento' => ['nullable', 'string', 'max:30'],
        ], ['required' => 'Selecciona una opción.']);

        $suscripcion = $this->fidelizacion->registrarSuscripcion(
            $cliente, Plan::findOrFail($datos['plan_id']), $datos['metodo_pago'], $request->user(), $datos['descuento'] ?? null,
        );

        return redirect()->route('portal.comprobante', $suscripcion->id)
            ->with('success', '¡Pago confirmado! Ganaste '.$suscripcion->puntos_ganados.' puntos.');
    }

    public function comprobante(Request $request, int $pago): View
    {
        $cliente = $this->cliente($request);
        $suscripcion = Suscripcion::with('plan')->where('cliente_id', $cliente->id)->findOrFail($pago);

        return view('portal.comprobante', ['cliente' => $cliente, 'pago' => $suscripcion]);
    }

    /** Configuración de la cuenta: teléfono y contraseña. */
    public function configuracion(Request $request): View
    {
        return view('portal.configuracion', ['cliente' => $this->cliente($request)]);
    }

    public function guardarDatos(Request $request): RedirectResponse
    {
        $cliente = $this->cliente($request);
        Validacion::normalizar($request);
        $datos = $request->validate([
            'telefono' => ['required', 'string', 'max:20', 'regex:'.Validacion::TELEFONO],
        ], ['required' => 'Escribe tu número de celular.'] + Validacion::MENSAJES);

        $cliente->update($datos);

        return back()->with('success', 'Tu número de teléfono se actualizó.');
    }

    public function guardarContrasena(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password' => 'La contraseña actual no es correcta.',
            'confirmed' => 'Las contraseñas no coinciden.',
            'required' => 'Este campo es obligatorio.',
            'min' => 'Debe tener al menos :min caracteres.',
        ]);

        $request->user()->update(['password' => $datos['password']]);

        return back()->with('success', 'Tu contraseña se cambió correctamente.');
    }

    // ------------------------------------------------------------------ ayudas

    private function cliente(Request $request): Cliente
    {
        $cliente = $request->user()->cliente;
        abort_if($cliente === null, 404, 'Esta cuenta no tiene un perfil de socio.');

        $cliente->load('suscripciones.plan', 'grupo.clientes', 'referidos');
        $this->fidelizacion->revisarNivel($cliente);

        return $cliente->refresh()->load('suscripciones.plan', 'grupo.clientes', 'referidos');
    }

    /** Planes activos con el mejor precio para el socio y todas sus opciones de descuento. */
    private function planes(Cliente $cliente)
    {
        return Plan::where('activo', true)->orderBy('duracion_dias')->get()
            ->map(fn (Plan $plan) => ['plan' => $plan] + $this->fidelizacion->cotizar($cliente, $plan));
    }

    /** Promociones vigentes, marcando si ya le aplican al socio (las de grupo, si está en un grupo). */
    private function promociones(Cliente $cliente)
    {
        $planes = Plan::where('activo', true)->get();
        $aplicables = $planes->flatMap(fn (Plan $p) => $this->fidelizacion->descuentosDisponibles($cliente, $p)->pluck('promocion_id'));
        if ($cliente->grupo) {
            $aplicables = $aplicables->merge($planes->flatMap(fn (Plan $p) => $this->fidelizacion->descuentosDisponibles($cliente, $p, true)->pluck('promocion_id')));
        }
        $aplicables = $aplicables->filter()->unique();

        return Promocion::vigentes()->with('plan')->orderByDesc('porcentaje')->get()
            ->map(function (Promocion $p) use ($aplicables) {
                $p->aplica = $aplicables->contains($p->id);
                $p->es_grupal = in_array($p->tipo, ['pareja', 'familiar'], true);

                return $p;
            });
    }
}
