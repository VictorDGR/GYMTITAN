<?php

namespace App\Http\Controllers;

use App\Models\Canje;
use App\Models\Cliente;
use App\Models\Premio;
use App\Services\Fidelizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CanjeController extends Controller
{
    public function index(): View
    {
        return view('canjes.index', ['canjes' => Canje::with('cliente', 'premio', 'registradoPor')->latest()->latest('id')->get()]);
    }

    public function create(Request $request): View
    {
        $cliente = $request->filled('cliente') ? Cliente::find($request->integer('cliente')) : null;

        return view('canjes.create', [
            'clientes' => Cliente::orderBy('apellido')->orderBy('nombre')->get(),
            'cliente' => $cliente,
            'premios' => Premio::where('activo', true)->orderBy('puntos')->get(),
        ]);
    }

    public function store(Request $request, Fidelizacion $fidelizacion): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')],
            'premio_id' => ['required', Rule::exists('premios', 'id')],
        ], ['required' => 'Selecciona una opción.']);

        $canje = $fidelizacion->canjear(Cliente::findOrFail($datos['cliente_id']), Premio::findOrFail($datos['premio_id']), $request->user());

        return redirect()->route('clientes.show', $canje->cliente_id)
            ->with('success', 'Canje registrado: '.$canje->premio->nombre.' por '.$canje->puntos.' puntos. Entrega el premio al cliente.');
    }
}
