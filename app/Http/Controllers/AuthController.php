<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /** Se puede ingresar con el correo o con el CI. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'acceso' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], ['required' => 'Este campo es obligatorio.']);

        $acceso = trim($datos['acceso']);
        $campo = filter_var($acceso, FILTER_VALIDATE_EMAIL) ? 'email' : 'ci';

        if (! Auth::attempt([$campo => $acceso, 'password' => $datos['password']], $request->boolean('recordar'))) {
            throw ValidationException::withMessages(['acceso' => 'Correo, CI o contraseña incorrectos.']);
        }

        // El portal del socio corresponde a HU-012; mientras no esté habilitado, el socio no ingresa.
        if (Auth::user()->esCliente() && ! \App\Support\Sprint::activo('portal')) {
            Auth::logout();
            throw ValidationException::withMessages(['acceso' => 'El portal del socio todavía no está disponible.']);
        }

        $request->session()->regenerate();

        return redirect()->route('inicio');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
