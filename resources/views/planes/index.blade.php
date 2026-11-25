@extends('layouts.app')

@section('title', 'Planes')
@section('kicker', 'Programa de fidelización')
@section('heading', 'Planes de suscripción')

@section('actions')
    <a class="button button-primary" href="{{ route('planes.create') }}">＋ Nuevo plan</a>
@endsection

@section('content')
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Plan</th><th>Duración</th><th class="right">Precio</th><th class="right">Puntos que otorga</th><th class="right">Ventas</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($planes as $p)
                    <tr>
                        <td><strong>{{ $p->nombre }}</strong></td>
                        <td>{{ $p->duracion_dias }} días</td>
                        <td class="right num">@bs($p->precio)</td>
                        <td class="right num pts-plus">+{{ number_format($p->puntos, 0, ',', '.') }}</td>
                        <td class="right num">{{ $p->suscripciones_count }}</td>
                        <td><span class="badge {{ $p->activo ? 'ok' : 'neutral' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('planes.edit', $p) }}">Editar</a>
                            <form method="POST" action="{{ route('planes.destroy', $p) }}" onsubmit="return window.confirm('¿Eliminar este plan?')">
                                @csrf @method('DELETE')
                                <button class="icon-button danger" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="7">Crea el primer plan para empezar a cobrar suscripciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
