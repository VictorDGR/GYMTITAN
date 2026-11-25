<?php

namespace App\Http\Controllers;

use App\Models\Premio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Catálogo de premios que se canjean con puntos. */
class PremioController extends Controller
{
    public function index(): View
    {
        return view('premios.index', ['premios' => Premio::withCount('canjes')->orderBy('puntos')->get()]);
    }

    public function create(): View
    {
        return view('premios.form', ['premio' => new Premio(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Premio::create($this->validar($request));

        return redirect()->route('premios.index')->with('success', 'Premio agregado al catálogo.');
    }

    public function edit(Premio $premio): View
    {
        return view('premios.form', compact('premio'));
    }

    public function update(Request $request, Premio $premio): RedirectResponse
    {
        $premio->update($this->validar($request));

        return redirect()->route('premios.index')->with('success', 'Premio actualizado.');
    }

    public function destroy(Premio $premio): RedirectResponse
    {
        if ($premio->canjes()->exists()) {
            $premio->update(['activo' => false]);

            return back()->with('success', 'El premio ya fue canjeado antes, así que se desactivó en lugar de eliminarse.');
        }
        $premio->delete();

        return back()->with('success', 'Premio eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'puntos' => ['required', 'integer', 'min:1', 'max:1000000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], ['required' => 'Este campo es obligatorio.']) + ['activo' => $request->boolean('activo')];
    }
}
