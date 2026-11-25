@extends('layouts.app')

@php($editando = $plan->exists)

@section('title', $editando ? 'Editar plan' : 'Nuevo plan')
@section('kicker', 'Planes')
@section('heading', $editando ? 'Editar plan '.$plan->nombre : 'Nuevo plan')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('planes.update', $plan) : route('planes.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $plan->nombre) }}" placeholder="Ej.: Trimestral" required><x-error field="nombre" /></div>
                <div class="field"><label for="duracion_dias">Duración (días)</label><input id="duracion_dias" name="duracion_dias" type="number" min="1" value="{{ old('duracion_dias', $plan->duracion_dias) }}" required><x-error field="duracion_dias" /></div>
                <div class="field"><label for="precio">Precio (Bs)</label><input id="precio" name="precio" type="number" step="0.01" min="0" value="{{ old('precio', $plan->precio) }}" required><x-error field="precio" /></div>
                <div class="field"><label for="puntos">Puntos que gana el cliente</label><input id="puntos" name="puntos" type="number" min="0" value="{{ old('puntos', $plan->puntos) }}" required><span class="hint">Se suman cada vez que paga este plan.</span><x-error field="puntos" /></div>
                <label class="check full"><input type="checkbox" name="activo" value="1" @checked(old('activo', $plan->activo))> Disponible para la venta</label>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('planes.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>
@endsection
