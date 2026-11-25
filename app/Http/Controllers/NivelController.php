<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Niveles de antigüedad: premian la permanencia con bono de puntos y descuento al renovar. */
class NivelController extends Controller
{
    public function index(): View
    {
        return view('niveles.index', ['niveles' => Nivel::orderBy('meses_minimos')->get()]);
    }

    public function create(): View
    {
        return view('niveles.form', ['nivel' => new Nivel]);
    }

    public function store(Request $request): RedirectResponse
    {
        Nivel::create($this->validar($request));

        return redirect()->route('niveles.index')->with('success', 'Nivel creado.');
    }

    public function edit(Nivel $nivel): View
    {
        return view('niveles.form', compact('nivel'));
    }

    public function update(Request $request, Nivel $nivel): RedirectResponse
    {
        $nivel->update($this->validar($request, $nivel));

        return redirect()->route('niveles.index')->with('success', 'Nivel actualizado.');
    }

    public function destroy(Nivel $nivel): RedirectResponse
    {
        $nivel->delete();

        return back()->with('success', 'Nivel eliminado.');
    }

    private function validar(Request $request, ?Nivel $nivel = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:40'],
            'meses_minimos' => ['required', 'integer', 'min:0', 'max:600', Rule::unique('niveles')->ignore($nivel)],
            'descuento_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'bono_puntos' => ['required', 'integer', 'min:0', 'max:100000'],
            'beneficios' => ['nullable', 'string', 'max:200'],
        ], ['required' => 'Este campo es obligatorio.', 'unique' => 'Ya hay un nivel con esa cantidad de meses.']);
    }
}
