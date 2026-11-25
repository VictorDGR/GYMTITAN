@extends('layouts.app')

@php($editando = $nivel->exists)

@section('title', $editando ? 'Editar nivel' : 'Nuevo nivel')
@section('kicker', 'Niveles de antigüedad')
@section('heading', $editando ? 'Editar nivel '.$nivel->nombre : 'Nuevo nivel')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('niveles.update', $nivel) : route('niveles.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $nivel->nombre) }}" placeholder="Ej.: Oro" required><x-error field="nombre" /></div>
                <div class="field"><label for="meses_minimos">Meses de antigüedad necesarios</label><input id="meses_minimos" name="meses_minimos" type="number" min="0" value="{{ old('meses_minimos', $nivel->meses_minimos) }}" required><x-error field="meses_minimos" /></div>
                <div class="field"><label for="descuento_porcentaje">Descuento al renovar (%)</label><input id="descuento_porcentaje" name="descuento_porcentaje" type="number" step="0.5" min="0" max="100" value="{{ old('descuento_porcentaje', $nivel->descuento_porcentaje ?? 0) }}" required><x-error field="descuento_porcentaje" /></div>
                <div class="field"><label for="bono_puntos">Bono de puntos al alcanzarlo</label><input id="bono_puntos" name="bono_puntos" type="number" min="0" value="{{ old('bono_puntos', $nivel->bono_puntos ?? 0) }}" required><x-error field="bono_puntos" /></div>
                <div class="field full"><label for="beneficios">Beneficios (texto para el cliente)</label><input id="beneficios" name="beneficios" value="{{ old('beneficios', $nivel->beneficios) }}" placeholder="Ej.: 10% al renovar y evaluación física gratis"><x-error field="beneficios" /></div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('niveles.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>
@endsection
