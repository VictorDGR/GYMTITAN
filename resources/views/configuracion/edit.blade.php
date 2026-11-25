@extends('layouts.app')

@section('title', 'Configuración')
@section('kicker', 'Administración')
@section('heading', 'Configuración del programa')

@section('content')
    <form class="panel form-card" method="POST" action="{{ route('configuracion.update') }}">
        @csrf @method('PUT')
        <div class="panel-body">
            <div class="form-grid">
                @foreach (\App\Models\Configuracion::PARAMETROS as $clave => [$etiqueta, $ayuda])
                    <div class="field">
                        <label for="{{ $clave }}">{{ $etiqueta }}</label>
                        <input id="{{ $clave }}" name="{{ $clave }}" type="number" min="0" value="{{ old($clave, $valores[$clave]) }}" required>
                        <span class="hint">{{ $ayuda }}</span>
                        <x-error :field="$clave" />
                    </div>
                @endforeach
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Guardar</button>
            </div>
        </div>
    </form>
@endsection
