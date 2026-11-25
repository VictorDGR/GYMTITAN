@extends('layouts.app')

@section('title', 'Premios')
@section('kicker', 'Programa de fidelización')
@section('heading', 'Catálogo de premios')

@section('actions')
    <a class="button button-primary" href="{{ route('premios.create') }}">＋ Nuevo premio</a>
@endsection

@section('content')
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Premio</th><th class="right">Puntos</th><th class="right">Stock</th><th class="right">Canjeado</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($premios as $p)
                    <tr>
                        <td><strong>{{ $p->nombre }}</strong>@if ($p->descripcion)<br><small class="muted">{{ $p->descripcion }}</small>@endif</td>
                        <td class="right num" style="color:var(--amber);font-weight:600">{{ number_format($p->puntos, 0, ',', '.') }}</td>
                        <td class="right num">{{ $p->stock ?? 'Sin límite' }}</td>
                        <td class="right num">{{ $p->canjes_count }}</td>
                        <td>
                            @if (! $p->activo)<span class="badge neutral">Inactivo</span>
                            @elseif (! $p->disponible())<span class="badge warn">Agotado</span>
                            @else<span class="badge ok">Disponible</span>
                            @endif
                        </td>
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('premios.edit', $p) }}">Editar</a>
                            <form method="POST" action="{{ route('premios.destroy', $p) }}" onsubmit="return window.confirm('¿Eliminar este premio?')">
                                @csrf @method('DELETE')
                                <button class="icon-button danger" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="6">Agrega premios para que los clientes canjeen sus puntos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
