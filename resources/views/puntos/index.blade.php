@extends('layouts.app')

@section('title', 'Puntos')
@section('kicker', 'Recepción')
@section('heading', 'Puntos de los socios')

@section('content')
<div class="stack">
    @php($n = fn ($v) => number_format((int) $v, 0, ',', '.'))

    <div class="stats">
        <div class="stat"><span class="stat-icon">★</span><div><strong>{{ $n($enCirculacion) }}</strong><span>Puntos disponibles entre todos los socios</span></div></div>
        <div class="stat"><span class="stat-icon blue">＋</span><div><strong>{{ $n($otorgadosMes) }}</strong><span>Puntos otorgados este mes</span></div></div>
        <div class="stat"><span class="stat-icon red">−</span><div><strong>{{ $n($usadosMes) }}</strong><span>Puntos canjeados o descontados este mes</span></div></div>
        <div class="stat"><span class="stat-icon amber">♙</span><div><strong>{{ $conPuntos }}</strong><span>Socios con saldo de puntos</span></div></div>
    </div>

    <div class="panel">
        <form class="panel-head inline-form" method="GET">
            <div><h2>Saldo por socio</h2><p>Ordenado de mayor a menor saldo.</p></div>
            <div class="field"><label for="buscar">Socio (nombre o CI)</label><input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Buscar…"></div>
            @if ($tipo)<input type="hidden" name="tipo" value="{{ $tipo }}">@endif
            <button class="button button-secondary" type="submit">Buscar</button>
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Socio</th><th>CI</th><th class="right">Saldo</th><th class="right">Ganados</th><th class="right">Usados</th><th>Último movimiento</th><th></th></tr></thead>
                <tbody>
                @forelse ($socios as $s)
                    <tr>
                        <td><a href="{{ route('clientes.show', $s) }}">{{ $s->nombre_completo }}</a></td>
                        <td class="num">{{ $s->ci }}</td>
                        <td class="right num"><strong>{{ $n($s->puntos) }}</strong></td>
                        <td class="right num pts-plus">+{{ $n($s->ganados) }}</td>
                        <td class="right num pts-minus">{{ $s->usados ? $n($s->usados) : '0' }}</td>
                        <td class="num muted">{{ $s->ultimo_movimiento ? \Illuminate\Support\Carbon::parse($s->ultimo_movimiento)->format('d/m/Y') : '—' }}</td>
                        <td><a class="button button-secondary" href="{{ route('clientes.show', $s) }}#ajustar-puntos">Ver y ajustar</a></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="7">No hay socios con ese nombre o CI.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $socios->count() }} socios · Saldo total: <strong>{{ $n($socios->sum('puntos')) }} puntos</strong></div>
    </div>

    <div class="panel">
        <form class="panel-head inline-form" method="GET">
            <div><h2>Últimos movimientos</h2><p>Los 30 más recientes de todos los socios.</p></div>
            @if ($buscar !== '')<input type="hidden" name="buscar" value="{{ $buscar }}">@endif
            <div class="field">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach (\App\Models\MovimientoPunto::TIPOS as $clave => $texto)
                        <option value="{{ $clave }}" @selected($tipo === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Fecha</th><th>Socio</th><th>Tipo</th><th>Detalle</th><th class="right">Puntos</th></tr></thead>
                <tbody>
                @forelse ($movimientos as $m)
                    <tr>
                        <td class="num">{{ $m->created_at->format('d/m/Y') }}</td>
                        <td><a href="{{ route('clientes.show', $m->cliente) }}">{{ $m->cliente->nombre_completo }}</a></td>
                        <td>{{ $m->tipoTexto() }}</td>
                        <td class="muted">{{ $m->descripcion }}</td>
                        <td class="right num {{ $m->puntos >= 0 ? 'pts-plus' : 'pts-minus' }}">{{ $m->puntos >= 0 ? '+' : '' }}{{ $n($m->puntos) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="5">No hay movimientos de este tipo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
