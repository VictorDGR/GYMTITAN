@extends('layouts.app')

@section('title', 'Configuración')
@section('kicker', 'Mi cuenta')
@section('heading', 'Configuración')

@section('content')
<div class="grid-2">
    <form class="panel" method="POST" action="{{ route('portal.configuracion.datos') }}">
        @csrf @method('PUT')
        <div class="panel-head"><div><h2>Mis datos</h2><p>Si cambian tu nombre, CI o correo, pídelo en recepción.</p></div></div>
        <div class="panel-body form-grid">
            <div class="field full"><label>Nombre</label><input value="{{ $cliente->nombre_completo }}" disabled></div>
            <div class="field"><label>CI</label><input value="{{ $cliente->ci }}" disabled></div>
            <div class="field"><label>Correo</label><input value="{{ $cliente->email }}" disabled></div>
            <div class="field full">
                <label for="telefono">Número de celular</label>
                <input id="telefono" name="telefono" type="tel" inputmode="numeric" maxlength="12" value="{{ old('telefono', $cliente->telefono) }}" required
                       pattern="(\+591)?([67][0-9]{7}|[2-4][0-9]{6})" title="Celular de 8 dígitos (6 o 7…) o fijo de 7 dígitos" placeholder="Ej.: 71234567">
                <span class="hint">Lo usamos para avisarte antes de que venza tu membresía.</span>
                <x-error field="telefono" />
            </div>
        </div>
        <div class="panel-body" style="padding-top:0"><button class="button button-primary" type="submit">Guardar mis datos</button></div>
    </form>

    <form class="panel" method="POST" action="{{ route('portal.configuracion.contrasena') }}">
        @csrf @method('PUT')
        <div class="panel-head"><div><h2>Cambiar contraseña</h2><p>Tu contraseña inicial es tu CI. Te recomendamos cambiarla.</p></div></div>
        <div class="panel-body form-grid">
            <div class="field full"><label for="actual">Contraseña actual</label><input id="actual" name="actual" type="password" required autocomplete="current-password"><x-error field="actual" /></div>
            <div class="field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" required minlength="6" autocomplete="new-password"><span class="hint">Mínimo 6 caracteres.</span><x-error field="password" /></div>
            <div class="field"><label for="password_confirmation">Repite la nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
        </div>
        <div class="panel-body" style="padding-top:0"><button class="button button-primary" type="submit">Cambiar contraseña</button></div>
    </form>
</div>
@endsection
