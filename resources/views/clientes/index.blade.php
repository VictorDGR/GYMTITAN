@extends('layouts.app')

@section('title', 'Clientes')
@section('kicker', 'Recepción')
@section('heading', 'Clientes')

@section('actions')
    <a class="button button-primary" href="{{ route('clientes.create') }}">＋ Nuevo cliente</a>
@endsection

@section('content')
    <div class="panel">
        <form class="panel-head inline-form" method="GET" action="{{ route('clientes.index') }}">
            <div class="field" style="flex:2">
                <label for="buscar">Buscar</label>
                <input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Nombre, apellido, CI o correo">
            </div>
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach (['Activo', 'Por vencer', 'Vencido', 'Sin suscripción'] as $e)
                        <option @selected($estado === $e)>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button-secondary" type="submit">Buscar</button>
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Cliente</th><th>CI</th><th>Estado</th><th>Vence</th>@modulo('niveles')<th>Nivel</th>@endmodulo<th class="right">Puntos</th>@modulo('grupos')<th>Grupo</th>@endmodulo<th></th></tr></thead>
                <tbody>
                @forelse ($clientes as $c)
                    <tr>
                        <td><span class="person-cell"><span class="avatar">{{ $c->iniciales() }}</span><span><a href="{{ route('clientes.show', $c) }}">{{ $c->nombre_completo }}</a><small>{{ $c->email }}</small></span></span></td>
                        <td class="num">{{ $c->ci }}</td>
                        <td><span class="badge {{ $c->claseEstado() }}">{{ $c->estado() }}</span></td>
                        <td class="num">{{ $c->pagadoHasta()?->format('d/m/Y') ?? '—' }}</td>
                        @modulo('niveles')<td>{{ $c->nivel()?->nombre ?? '—' }}</td>@endmodulo
                        <td class="right num">{{ number_format($c->puntos, 0, ',', '.') }}</td>
                        @modulo('grupos')<td>{{ $c->grupo ? $c->grupo->tipoTexto() : '—' }}</td>@endmodulo
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('clientes.show', $c) }}">Ver</a>
                            <a class="icon-button" href="{{ route('suscripciones.create', ['cliente' => $c->id]) }}">Cobrar plan</a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="8">{{ $buscar || $estado ? 'No hay clientes que coincidan con la búsqueda.' : 'Aún no hay clientes. Registra el primero con "Nuevo cliente".' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $clientes->count() }} {{ $clientes->count() === 1 ? 'cliente' : 'clientes' }}</div>
    </div>
@endsection
