<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use App\Services\Fidelizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClienteController extends Controller
{
    private const MENSAJES = [
        'required' => 'Este campo es obligatorio.',
        'unique' => 'Ya existe una cuenta con este dato.',
        'email' => 'Escribe un correo válido.',
        'before' => 'La fecha debe ser anterior a hoy.',
    ];

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = (string) $request->query('estado');

        $clientes = Cliente::with('suscripciones', 'grupo')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('apellido', 'like', "%{$buscar}%")
                ->orWhere('ci', 'like', "%{$buscar}%")
                ->orWhere('email', 'like', "%{$buscar}%")))
            ->orderBy('apellido')->orderBy('nombre')
            ->get()
            ->when($estado !== '', fn ($c) => $c->filter(fn (Cliente $cliente) => $cliente->estado() === $estado))
            ->values();

        return view('clientes.index', compact('clientes', 'buscar', 'estado'));
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente, 'referentes' => $this->referentes()]);
    }

    /** Registra al cliente y su cuenta de acceso. La contraseña inicial es su CI. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $cliente = DB::transaction(function () use ($datos): Cliente {
            $user = User::create([
                'name' => $datos['nombre'].' '.$datos['apellido'],
                'email' => $datos['email'],
                'ci' => $datos['ci'],
                'password' => $datos['ci'],
                'rol' => 'cliente',
            ]);

            return $user->cliente()->create($datos);
        });

        return redirect()->route('suscripciones.create', ['cliente' => $cliente->id])
            ->with('success', "Cliente registrado. Puede ingresar con su correo o CI; su contraseña inicial es su CI ({$cliente->ci}). Ahora registra su primera suscripción.");
    }

    public function show(Cliente $cliente, Fidelizacion $fidelizacion): View
    {
        $cliente->load('suscripciones.plan', 'grupo.clientes', 'referidoPor', 'referidos');
        $fidelizacion->revisarNivel($cliente);
        $cliente->refresh()->load('suscripciones.plan', 'grupo.clientes', 'referidoPor', 'referidos');

        return view('clientes.show', [
            'cliente' => $cliente,
            'suscripciones' => $cliente->suscripciones->sortByDesc('fecha_inicio'),
            'movimientos' => $cliente->movimientos()->latest()->latest('id')->get(),
            'nivel' => $cliente->nivel(),
            'siguiente' => $cliente->siguienteNivel(),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', ['cliente' => $cliente, 'referentes' => $this->referentes($cliente)]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $this->validar($request, $cliente);

        DB::transaction(function () use ($cliente, $datos): void {
            $cliente->update($datos);
            $cliente->user->update(['name' => $datos['nombre'].' '.$datos['apellido'], 'email' => $datos['email'], 'ci' => $datos['ci']]);
        });

        return redirect()->route('clientes.show', $cliente)->with('success', 'Datos del cliente actualizados.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        // Al borrar la cuenta se eliminan también el cliente, sus suscripciones y sus puntos.
        $cliente->user->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    public function ajustarPuntos(Request $request, Cliente $cliente, Fidelizacion $fidelizacion): RedirectResponse
    {
        $datos = $request->validate([
            'puntos' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'motivo' => ['required', 'string', 'max:150'],
        ], ['required' => 'Este campo es obligatorio.', 'not_in' => 'Escribe una cantidad distinta de cero.']);

        if ($cliente->puntos + $datos['puntos'] < 0) {
            return back()->withErrors(['puntos' => 'El saldo no puede quedar negativo. Saldo actual: '.$cliente->puntos.'.'])->withInput();
        }

        $fidelizacion->moverPuntos($cliente, 'ajuste', (int) $datos['puntos'], $datos['motivo'], ['user_id' => $request->user()->id]);

        return back()->with('success', 'Puntos ajustados.');
    }

    public function restablecerContrasena(Cliente $cliente): RedirectResponse
    {
        $cliente->user->update(['password' => $cliente->ci]);

        return back()->with('success', 'La contraseña del cliente ahora es su CI ('.$cliente->ci.').');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        \App\Support\Validacion::normalizar($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'regex:'.\App\Support\Validacion::NOMBRE],
            'apellido' => ['required', 'string', 'max:80', 'regex:'.\App\Support\Validacion::NOMBRE],
            'ci' => ['required', 'string', 'max:20', 'regex:'.\App\Support\Validacion::CI, Rule::unique('clientes')->ignore($cliente), Rule::unique('users')->ignore($cliente?->user_id)],
            'email' => ['required', 'email', 'max:120', Rule::unique('clientes')->ignore($cliente), Rule::unique('users')->ignore($cliente?->user_id)],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:'.\App\Support\Validacion::TELEFONO],
            'fecha_nacimiento' => ['nullable', 'date', 'after:1920-01-01', 'before:'.today()->subYears(12)->addDay()->toDateString()],
            'referido_por_id' => ['nullable', 'integer', Rule::exists('clientes', 'id'), Rule::notIn([$cliente?->id])],
        ], \App\Support\Validacion::MENSAJES + self::MENSAJES);

        $datos['ci'] = trim($datos['ci']);
        $datos['email'] = mb_strtolower(trim($datos['email']));

        return $datos;
    }

    private function referentes(?Cliente $excepto = null)
    {
        return Cliente::when($excepto, fn ($q) => $q->whereKeyNot($excepto->id))->orderBy('apellido')->get();
    }
}
