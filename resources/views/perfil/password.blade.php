@extends('layouts.app')

@section('title', 'Cambiar contraseña')
@section('kicker', 'Mi cuenta')
@section('heading', 'Cambiar contraseña')

@section('content')
    <form class="panel form-card" method="POST" action="{{ route('perfil.password') }}">
        @csrf @method('PUT')
        <div class="panel-body">
            @if (auth()->user()->esCliente())
                <p class="note" style="margin-top:0">Tu contraseña inicial es tu número de CI. Te recomendamos cambiarla por una que solo tú conozcas.</p>
            @endif
            <div class="form-grid">
                <div class="field full"><label for="actual">Contraseña actual</label><input id="actual" name="actual" type="password" required autocomplete="current-password"><x-error field="actual" /></div>
                <div class="field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" required autocomplete="new-password"><span class="hint">Mínimo 6 caracteres.</span><x-error field="password" /></div>
                <div class="field"><label for="password_confirmation">Repite la nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('inicio') }}">Cancelar</a>
                <button class="button button-primary" type="submit">Guardar contraseña</button>
            </div>
        </div>
    </form>
@endsection
