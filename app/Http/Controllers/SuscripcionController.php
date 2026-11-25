<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Grupo;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Services\Fidelizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuscripcionController extends Controller
{
    public function index(Request $request): View
    {
        $desde = $request->date('desde') ?? today()->startOfMonth();
        $hasta = $request->date('hasta') ?? today();

        $suscripciones = Suscripcion::with('cliente', 'plan', 'registradoPor')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)
            ->latest()->latest('id')
            ->get();

        return view('suscripciones.index', compact('suscripciones', 'desde', 'hasta'));
    }

    /** Elegido el cliente, muestra cada plan con el precio final que pagaría hoy. */
    public function create(Request $request, Fidelizacion $fidelizacion): View
    {
        $cliente = $request->filled('cliente') ? Cliente::with('suscripciones', 'grupo.clientes')->find($request->integer('cliente')) : null;
        $opciones = collect();

        if ($cliente) {
            $opciones = Plan::where('activo', true)->orderBy('duracion_dias')->get()
                ->map(fn (Plan $plan) => ['plan' => $plan] + $fidelizacion->cotizar($cliente, $plan));
        }

        return view('suscripciones.create', [
            'clientes' => Cliente::orderBy('apellido')->orderBy('nombre')->get(),
            'cliente' => $cliente,
            'opciones' => $opciones,
            'inicio' => $cliente ? $fidelizacion->fechaInicio($cliente) : null,
            'nivel' => $cliente?->nivel(),
        ]);
    }

    public function store(Request $request, Fidelizacion $fidelizacion): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')],
            'plan_id' => ['required', Rule::exists('planes', 'id')->where('activo', true)],
            'metodo_pago' => ['required', Rule::in(Suscripcion::METODOS)],
            'descuento' => ['nullable', 'string', 'max:30'],
        ], ['required' => 'Selecciona una opción.']);

        $suscripcion = $fidelizacion->registrarSuscripcion(
            Cliente::findOrFail($datos['cliente_id']),
            Plan::findOrFail($datos['plan_id']),
            $datos['metodo_pago'],
            $request->user(),
            $datos['descuento'] ?? null,
        );

        return redirect()->route('suscripciones.show', $suscripcion)
            ->with('success', 'Suscripción registrada. El cliente ganó '.$suscripcion->puntos_ganados.' puntos.');
    }

    /** HU-005 · Cobrar el mismo plan a toda la pareja o familia con su promoción de grupo. */
    public function createGrupal(Request $request, Fidelizacion $fidelizacion): View
    {
        $grupo = $request->filled('grupo') ? Grupo::with('clientes.suscripciones', 'clientes.grupo.clientes')->find($request->integer('grupo')) : null;
        $opciones = collect();

        if ($grupo && $grupo->clientes->count() >= 2) {
            $referencia = $grupo->clientes->first();
            $opciones = Plan::where('activo', true)->orderBy('duracion_dias')->get()->map(function (Plan $plan) use ($fidelizacion, $referencia, $grupo) {
                $cot = $fidelizacion->cotizar($referencia, $plan, null, true);
                $n = $grupo->clientes->count();

                return ['plan' => $plan, 'n' => $n, 'precio_grupo' => $cot['precio_base'] * $n] + $cot;
            });
        }

        return view('suscripciones.grupal', [
            'grupos' => Grupo::withCount('clientes')->orderBy('nombre')->get(),
            'grupo' => $grupo,
            'opciones' => $opciones,
            'inicios' => $grupo ? $grupo->clientes->mapWithKeys(fn ($c) => [$c->id => $fidelizacion->fechaInicio($c)]) : collect(),
        ]);
    }

    public function storeGrupal(Request $request, Fidelizacion $fidelizacion): RedirectResponse
    {
        $datos = $request->validate([
            'grupo_id' => ['required', Rule::exists('grupos', 'id')],
            'plan_id' => ['required', Rule::exists('planes', 'id')->where('activo', true)],
            'metodo_pago' => ['required', Rule::in(Suscripcion::METODOS)],
            'descuento' => ['nullable', 'string', 'max:30'],
        ], ['required' => 'Selecciona una opción.']);

        $lote = $fidelizacion->registrarGrupo(
            Grupo::findOrFail($datos['grupo_id']),
            Plan::findOrFail($datos['plan_id']),
            $datos['metodo_pago'],
            $request->user(),
            $datos['descuento'] ?? null,
        );

        return redirect()->route('suscripciones.grupal.show', $lote)->with('success', 'Pago grupal registrado.');
    }

    public function showGrupal(string $lote): View
    {
        $suscripciones = Suscripcion::with('cliente', 'plan', 'grupo', 'registradoPor')->where('lote', $lote)->orderBy('id')->get();
        abort_if($suscripciones->isEmpty(), 404);

        return view('suscripciones.grupal-show', ['suscripciones' => $suscripciones, 'primera' => $suscripciones->first()]);
    }

    public function show(Suscripcion $suscripcion): View
    {
        $suscripcion->load('cliente', 'plan', 'registradoPor', 'promocion');

        return view('suscripciones.show', compact('suscripcion'));
    }
}
