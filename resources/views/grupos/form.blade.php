@extends('layouts.app')

@php($editando = $grupo->exists)
@php($marcados = array_map('intval', old('miembros', $seleccionados)))

@section('title', $editando ? 'Editar grupo' : 'Nuevo grupo')
@section('kicker', 'Parejas y familias')
@section('heading', $editando ? 'Editar grupo' : 'Registrar pareja o familia')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('grupos.update', $grupo) : route('grupos.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre del grupo</label><input id="nombre" name="nombre" value="{{ old('nombre', $grupo->nombre) }}" placeholder="Ej.: Familia Rojas" required><x-error field="nombre" /></div>
                <div class="field">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
                        @foreach (\App\Models\Grupo::TIPOS as $valor => $texto)
                            <option value="{{ $valor }}" @selected(old('tipo', $grupo->tipo) === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <span class="hint">Pareja: exactamente 2. Familiar: 2 o más (el descuento exige el mínimo definido en la promoción).</span>
                </div>
                <div class="field full">
                    <span class="label">Integrantes</span>
                    <div class="check-list">
                        @forelse ($clientes as $c)
                            <label class="check-card">
                                <input type="checkbox" name="miembros[]" value="{{ $c->id }}" @checked(in_array($c->id, $marcados, true))>
                                <span>{{ $c->nombre_completo }}<small>CI {{ $c->ci }}</small></span>
                            </label>
                        @empty
                            <p class="muted">No hay clientes disponibles. Cada cliente puede estar en un solo grupo.</p>
                        @endforelse
                    </div>
                    <span class="hint">Solo aparecen clientes que no pertenecen a otro grupo.</span>
                    <x-error field="miembros" />
                </div>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('grupos.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">{{ $editando ? 'Guardar cambios' : 'Registrar grupo' }}</button>
            </div>
        </div>
    </form>
@endsection
