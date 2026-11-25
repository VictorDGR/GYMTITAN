<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(): View
    {
        return view('perfil.password');
    }

    public function update(Request $request): RedirectResponse
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

        return redirect()->route('inicio')->with('success', 'Contraseña actualizada.');
    }
}
