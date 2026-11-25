@extends('layouts.app')

@php($editando = $premio->exists)

@section('title', $editando ? 'Editar premio' : 'Nuevo premio')
@section('kicker', 'Premios')
@section('heading', $editando ? 'Editar premio' : 'Nuevo premio')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('premios.update', $premio) : route('premios.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $premio->nombre) }}" placeholder="Ej.: Toalla deportiva" required><x-error field="nombre" /></div>
                <div class="field"><label for="puntos">Puntos necesarios</label><input id="puntos" name="puntos" type="number" min="1" value="{{ old('puntos', $premio->puntos) }}" required><x-error field="puntos" /></div>
                <div class="field"><label for="stock">Stock</label><input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $premio->stock) }}"><span class="hint">Déjalo vacío si no tiene límite (por ejemplo, un servicio).</span><x-error field="stock" /></div>
                <div class="field"><label for="descripcion">Descripción</label><input id="descripcion" name="descripcion" value="{{ old('descripcion', $premio->descripcion) }}"><x-error field="descripcion" /></div>
                <label class="check full"><input type="checkbox" name="activo" value="1" @checked(old('activo', $premio->activo))> Visible en el catálogo</label>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('premios.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>
@endsection
