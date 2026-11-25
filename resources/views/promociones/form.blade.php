@extends('layouts.app')

@php($editando = $promocion->exists)

@section('title', $editando ? 'Editar promoción' : 'Nueva promoción')
@section('kicker', 'Promociones')
@section('heading', $editando ? 'Editar promoción' : 'Nueva promoción')

@section('content')
    <form class="panel form-card" method="POST" action="{{ $editando ? route('promociones.update', $promocion) : route('promociones.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="panel-body">
            <div class="form-grid">
                <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $promocion->nombre) }}" placeholder="Ej.: Navidad Titán" required><x-error field="nombre" /></div>
                <div class="field">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
                        @foreach (\App\Models\Promocion::TIPOS as $valor => $texto)
                            <option value="{{ $valor }}" @selected(old('tipo', $promocion->tipo) === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <x-error field="tipo" />
                </div>
                <div class="field"><label for="porcentaje">Descuento (%)</label><input id="porcentaje" name="porcentaje" type="number" step="0.5" min="1" max="100" value="{{ old('porcentaje', $promocion->porcentaje) }}" required><x-error field="porcentaje" /></div>
                <div class="field">
                    <label for="plan_id">Aplica al plan</label>
                    <select id="plan_id" name="plan_id">
                        <option value="">Todos los planes</option>
                        @foreach ($planes as $plan)
                            <option value="{{ $plan->id }}" @selected((int) old('plan_id', $promocion->plan_id) === $plan->id)>{{ $plan->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" data-tipo="fecha_especial"><label for="fecha_inicio">Desde</label><input id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $promocion->fecha_inicio?->toDateString()) }}"><x-error field="fecha_inicio" /></div>
                <div class="field" data-tipo="fecha_especial"><label for="fecha_fin">Hasta</label><input id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $promocion->fecha_fin?->toDateString()) }}"><x-error field="fecha_fin" /></div>
                <div class="field" data-tipo="familiar"><label for="min_personas">Mínimo de integrantes</label><input id="min_personas" name="min_personas" type="number" min="2" max="20" value="{{ old('min_personas', $promocion->min_personas ?? 3) }}"><x-error field="min_personas" /></div>
                <div class="field full"><label for="descripcion">Texto que verá el cliente</label><input id="descripcion" name="descripcion" value="{{ old('descripcion', $promocion->descripcion) }}" placeholder="Ej.: Regala salud: 20% en todos los planes."><x-error field="descripcion" /></div>
                <label class="check full"><input type="checkbox" name="activo" value="1" @checked(old('activo', $promocion->activo))> Promoción activa</label>
            </div>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('promociones.index') }}">Cancelar</a>
                <button class="button button-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // Muestra solo los campos que corresponden al tipo elegido.
    (function () {
        const tipo = document.getElementById('tipo');
        const actualizar = () => document.querySelectorAll('[data-tipo]').forEach(el => { el.hidden = el.dataset.tipo !== tipo.value; });
        tipo.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
@endpush
