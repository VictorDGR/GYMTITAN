<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RecepcionistaController extends Controller
{
    public function index(): View
    {
        return view('recepcionistas.index', ['recepcionistas' => User::where('rol', 'recepcionista')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('recepcionistas.form', ['recepcionista' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validar($request) + ['rol' => 'recepcionista']);

        return redirect()->route('recepcionistas.index')->with('success', 'Recepcionista registrada.');
    }

    public function edit(User $recepcionista): View
    {
        abort_unless($recepcionista->esRecepcionista(), 404);

        return view('recepcionistas.form', compact('recepcionista'));
    }

    public function update(Request $request, User $recepcionista): RedirectResponse
    {
        abort_unless($recepcionista->esRecepcionista(), 404);
        $datos = $this->validar($request, $recepcionista);
        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        $recepcionista->update($datos);

        return redirect()->route('recepcionistas.index')->with('success', 'Datos actualizados.');
    }

    public function destroy(User $recepcionista): RedirectResponse
    {
        abort_unless($recepcionista->esRecepcionista(), 404);
        $recepcionista->delete();

        return back()->with('success', 'Recepcionista eliminada.');
    }

    private function validar(Request $request, ?User $user = null): array
    {
        \App\Support\Validacion::normalizar($request);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:'.\App\Support\Validacion::NOMBRE],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($user)],
            'ci' => ['nullable', 'string', 'max:20', 'regex:'.\App\Support\Validacion::CI, Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', Password::min(6)],
        ], [
            'required' => 'Este campo es obligatorio.',
            'unique' => 'Ya existe una cuenta con este dato.',
            'min' => 'Debe tener al menos :min caracteres.',
            'email' => 'Escribe un correo válido.',
        ] + \App\Support\Validacion::MENSAJES);
    }
}
