<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        $valores = collect(Configuracion::PARAMETROS)->mapWithKeys(fn ($p, $clave) => [$clave => Configuracion::entero($clave)]);

        return view('configuracion.edit', compact('valores'));
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'puntos_referido' => ['required', 'integer', 'min:0', 'max:100000'],
            'dias_aviso_vencimiento' => ['required', 'integer', 'min:1', 'max:60'],
        ], ['required' => 'Este campo es obligatorio.']);

        foreach ($datos as $clave => $valor) {
            Configuracion::guardar($clave, (int) $valor);
        }

        return back()->with('success', 'Configuración guardada.');
    }
}
