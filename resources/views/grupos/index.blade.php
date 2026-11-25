@extends('layouts.app')

@section('title', 'Parejas y familias')
@section('kicker', 'Inscripción grupal')
@section('heading', 'Parejas y familias')

@section('actions')
    <a class="button button-primary" href="{{ route('grupos.create') }}">＋ Nuevo grupo</a>
@endsection

@section('content')
<div class="stack">
    <p class="note" style="margin:0">Registra aquí a quienes se inscriben juntos. Las promociones de pareja y familiar solo se aplican al <strong>cobrar al grupo</strong>: se cobra el mismo plan a todos los integrantes en un solo pago y cada uno recibe su suscripción y sus puntos.</p>
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Grupo</th><th>Tipo</th><th>Integrantes</th><th></th></tr></thead>
                <tbody>
                @forelse ($grupos as $g)
                    <tr>
                        <td><strong>{{ $g->nombre }}</strong></td>
                        <td><span class="badge {{ $g->tipo === 'pareja' ? 'info' : 'teal' }} plain">{{ $g->tipoTexto() }}</span></td>
                        <td>
                            @foreach ($g->clientes as $c)<a class="teal" href="{{ route('clientes.show', $c) }}">{{ $c->nombre_completo }}</a>@if (! $loop->last), @endif @endforeach
                            <span class="muted">({{ $g->clientes->count() }})</span>
                        </td>
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('suscripciones.grupal', ['grupo' => $g->id]) }}">Cobrar al grupo</a>
                            <a class="icon-button" href="{{ route('grupos.edit', $g) }}">Editar</a>
                            <form method="POST" action="{{ route('grupos.destroy', $g) }}" onsubmit="return window.confirm('¿Eliminar este grupo? Los clientes no se borran.')">
                                @csrf @method('DELETE')
                                <button class="icon-button danger" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="4">Aún no hay parejas ni familias registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
