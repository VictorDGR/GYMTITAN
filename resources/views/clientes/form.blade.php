@extends('layouts.app')

@php($editando = $cliente->exists)

@section('title', $editando ? 'Editar cliente' : 'Nuevo cliente')
@section('kicker', 'Clientes')
@section('heading', $editando ? 'Editar a '.$cliente->nombre_completo : 'Registrar cliente')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('clientes.update', $cliente) : route('clientes.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required maxlength="80" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,}" title="Solo letras y espacios"><x-error field="nombre" /></div>
                <div class="field"><label for="apellido">Apellido</label><input id="apellido" name="apellido" value="{{ old('apellido', $cliente->apellido) }}" required maxlength="80" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,}" title="Solo letras y espacios"><x-error field="apellido" /></div>
                <div class="field"><label for="ci">CI</label><input id="ci" name="ci" value="{{ old('ci', $cliente->ci) }}" required maxlength="13" pattern="[0-9]{5,10}(-[0-9A-Za-z]{1,2})?" title="De 5 a 10 números; complemento opcional, ej.: 7845123-1A"><span class="hint">{{ $editando ? 'El cliente ingresa con este CI.' : 'También será su contraseña inicial.' }}</span><x-error field="ci" /></div>
                <div class="field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email', $cliente->email) }}" required><span class="hint">Para ingresar a su cuenta.</span><x-error field="email" /></div>
                <div class="field"><label for="telefono">Teléfono / WhatsApp</label><input id="telefono" name="telefono" type="tel" inputmode="numeric" maxlength="12" value="{{ old('telefono', $cliente->telefono) }}" pattern="(\+591)?([67][0-9]{7}|[2-4][0-9]{6})" title="Celular de 8 dígitos (6 o 7…) o fijo de 7 dígitos" placeholder="Ej.: 71234567"><x-error field="telefono" /></div>
                <div class="field"><label for="fecha_nacimiento">Fecha de nacimiento</label><input id="fecha_nacimiento" name="fecha_nacimiento" type="date" min="1920-01-02" max="{{ today()->subYears(12)->toDateString() }}" value="{{ old('fecha_nacimiento', $cliente->fecha_nacimiento?->toDateString()) }}"><span class="hint">Para el descuento de cumpleaños.</span><x-error field="fecha_nacimiento" /></div>
                <div class="field full">
                    <label for="referido_por_id">¿Quién lo recomendó?</label>
                    <select id="referido_por_id" name="referido_por_id">
                        <option value="">Nadie / vino por su cuenta</option>
                        @foreach ($referentes as $r)
                            <option value="{{ $r->id }}" @selected((int) old('referido_por_id', $cliente->referido_por_id) === $r->id)>{{ $r->apellido }}, {{ $r->nombre }} · CI {{ $r->ci }}</option>
                        @endforeach
                    </select>
                    <span class="hint">El socio que lo recomendó gana puntos cuando este cliente paga su primera suscripción.</span>
                    <x-error field="referido_por_id" />
                </div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ $editando ? route('clientes.show', $cliente) : route('clientes.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">{{ $editando ? 'Guardar cambios' : 'Registrar y cobrar plan' }}</button>
            </div>
        </div>
    </form>
@endsection
