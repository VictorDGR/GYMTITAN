<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Promocion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Descuentos: fechas especiales, cumpleaños, pareja y familiar. */
class PromocionController extends Controller
{
    public function index(): View
    {
        $vigentes = Promocion::vigentes()->pluck('id')->all();

        return view('promociones.index', [
            'promociones' => Promocion::with('plan')->orderBy('tipo')->orderByDesc('fecha_inicio')->get(),
            'vigentes' => $vigentes,
        ]);
    }

    public function create(): View
    {
        return view('promociones.form', ['promocion' => new Promocion(['tipo' => 'fecha_especial', 'activo' => true]), 'planes' => Plan::orderBy('duracion_dias')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Promocion::create($this->validar($request));

        return redirect()->route('promociones.index')->with('success', 'Promoción creada.');
    }

    public function edit(Promocion $promocion): View
    {
        return view('promociones.form', ['promocion' => $promocion, 'planes' => Plan::orderBy('duracion_dias')->get()]);
    }

    public function update(Request $request, Promocion $promocion): RedirectResponse
    {
        $promocion->update($this->validar($request));

        return redirect()->route('promociones.index')->with('success', 'Promoción actualizada.');
    }

    public function destroy(Promocion $promocion): RedirectResponse
    {
        $promocion->delete();

        return back()->with('success', 'Promoción eliminada.');
    }

    private function validar(Request $request): array
    {
        $esFecha = $request->input('tipo') === 'fecha_especial';

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'tipo' => ['required', Rule::in(array_keys(Promocion::TIPOS))],
            'porcentaje' => ['required', 'numeric', 'min:1', 'max:100'],
            'fecha_inicio' => [Rule::requiredIf($esFecha), 'nullable', 'date'],
            'fecha_fin' => [Rule::requiredIf($esFecha), 'nullable', 'date', 'after_or_equal:fecha_inicio'],
            'min_personas' => ['nullable', 'integer', 'min:2', 'max:20'],
            'plan_id' => ['nullable', Rule::exists('planes', 'id')],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
        ]);

        if (! $esFecha) {
            $datos['fecha_inicio'] = $datos['fecha_fin'] = null;
        }
        if ($datos['tipo'] !== 'familiar') {
            $datos['min_personas'] = null;
        }

        return $datos + ['activo' => $request->boolean('activo')];
    }
}
