@extends('layouts.app')

@php($editando = $recepcionista->exists)

@section('title', $editando ? 'Editar recepcionista' : 'Nueva recepcionista')
@section('kicker', 'Recepcionistas')
@section('heading', $editando ? 'Editar a '.$recepcionista->name : 'Nueva recepcionista')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('recepcionistas.update', $recepcionista) : route('recepcionistas.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="name">Nombre completo</label><input id="name" name="name" value="{{ old('name', $recepcionista->name) }}" required maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,}" title="Solo letras y espacios"><x-error field="name" /></div>
                <div class="field"><label for="ci">CI</label><input id="ci" name="ci" value="{{ old('ci', $recepcionista->ci) }}" maxlength="13" pattern="[0-9]{5,10}(-[0-9A-Za-z]{1,2})?" title="De 5 a 10 números; complemento opcional, ej.: 7845123-1A"><x-error field="ci" /></div>
                <div class="field"><label for="email">Correo</label><input id="email" name="email" type="email" value="{{ old('email', $recepcionista->email) }}" required><x-error field="email" /></div>
                <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="new-password" @required(! $editando)><span class="hint">{{ $editando ? 'Déjala vacía para no cambiarla.' : 'Mínimo 6 caracteres.' }}</span><x-error field="password" /></div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('recepcionistas.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>
@endsection
