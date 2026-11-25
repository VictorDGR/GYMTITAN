<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Grupo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Grupos de pareja o familiares: habilitan los descuentos de inscripción grupal. */
class GrupoController extends Controller
{
    public function index(): View
    {
        return view('grupos.index', ['grupos' => Grupo::with('clientes')->orderBy('nombre')->get()]);
    }

    public function create(): View
    {
        return view('grupos.form', ['grupo' => new Grupo(['tipo' => 'pareja']), 'clientes' => $this->disponibles(), 'seleccionados' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($datos): void {
            $grupo = Grupo::create(['nombre' => $datos['nombre'], 'tipo' => $datos['tipo']]);
            Cliente::whereIn('id', $datos['miembros'])->update(['grupo_id' => $grupo->id]);
        });

        return redirect()->route('grupos.index')->with('success', 'Grupo registrado. Sus integrantes ya tienen el descuento grupal en su próxima suscripción.');
    }

    public function edit(Grupo $grupo): View
    {
        return view('grupos.form', [
            'grupo' => $grupo,
            'clientes' => $this->disponibles($grupo),
            'seleccionados' => $grupo->clientes()->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Grupo $grupo): RedirectResponse
    {
        $datos = $this->validar($request, $grupo);

        DB::transaction(function () use ($grupo, $datos): void {
            $grupo->update(['nombre' => $datos['nombre'], 'tipo' => $datos['tipo']]);
            $grupo->clientes()->whereNotIn('id', $datos['miembros'])->update(['grupo_id' => null]);
            Cliente::whereIn('id', $datos['miembros'])->update(['grupo_id' => $grupo->id]);
        });

        return redirect()->route('grupos.index')->with('success', 'Grupo actualizado.');
    }

    public function destroy(Grupo $grupo): RedirectResponse
    {
        $grupo->delete();

        return redirect()->route('grupos.index')->with('success', 'Grupo eliminado. Sus integrantes siguen registrados como clientes.');
    }

    private function validar(Request $request, ?Grupo $grupo = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'tipo' => ['required', Rule::in(array_keys(Grupo::TIPOS))],
            'miembros' => ['required', 'array'],
            'miembros.*' => ['integer', Rule::exists('clientes', 'id')->where(fn ($q) => $q->whereNull('grupo_id')->when($grupo, fn ($q) => $q->orWhere('grupo_id', $grupo->id)))],
        ], [
            'required' => 'Este campo es obligatorio.',
            'miembros.required' => 'Selecciona a los integrantes.',
            'miembros.*.exists' => 'Uno de los clientes ya pertenece a otro grupo.',
        ]);

        $cantidad = count($datos['miembros']);
        if ($datos['tipo'] === 'pareja' && $cantidad !== 2) {
            throw ValidationException::withMessages(['miembros' => 'Una pareja debe tener exactamente 2 integrantes.']);
        }
        if ($datos['tipo'] === 'familiar' && $cantidad < 2) {
            throw ValidationException::withMessages(['miembros' => 'Un grupo familiar debe tener al menos 2 integrantes.']);
        }

        return $datos;
    }

    private function disponibles(?Grupo $grupo = null)
    {
        return Cliente::where(fn ($q) => $q->whereNull('grupo_id')->when($grupo, fn ($q) => $q->orWhere('grupo_id', $grupo->id)))
            ->orderBy('apellido')->orderBy('nombre')->get();
    }
}
