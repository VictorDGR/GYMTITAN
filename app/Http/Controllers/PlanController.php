<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('planes.index', ['planes' => Plan::withCount('suscripciones')->orderBy('duracion_dias')->get()]);
    }

    public function create(): View
    {
        return view('planes.form', ['plan' => new Plan(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::create($this->validar($request));

        return redirect()->route('planes.index')->with('success', 'Plan creado.');
    }

    public function edit(Plan $plan): View
    {
        return view('planes.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validar($request));

        return redirect()->route('planes.index')->with('success', 'Plan actualizado.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->suscripciones()->exists()) {
            $plan->update(['activo' => false]);

            return back()->with('success', 'El plan tiene suscripciones registradas, así que se desactivó en lugar de eliminarse.');
        }
        $plan->delete();

        return back()->with('success', 'Plan eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'duracion_dias' => ['required', 'integer', 'min:1', 'max:730'],
            'precio' => ['required', 'numeric', 'min:0', 'max:100000'],
            'puntos' => ['required', 'integer', 'min:0', 'max:100000'],
        ], ['required' => 'Este campo es obligatorio.']) + ['activo' => $request->boolean('activo')];
    }
}
