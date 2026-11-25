@extends('layouts.app')

@section('title', 'Inicio')
@section('heading', 'Hola, '.explode(' ', auth()->user()->name)[0])

@section('actions')
    @modulo('canjes')<a class="button button-secondary" href="{{ route('canjes.create') }}">★ Canjear puntos</a>@endmodulo
    <a class="button button-primary" href="{{ route('clientes.create') }}">＋ Nuevo cliente</a>
@endsection

@section('content')
<div class="stack">
    @modulo('indicadores')
    <div class="stats">
        <div class="stat"><span class="stat-icon">♙</span><div><strong>{{ $activos }}</strong><span>Socios activos de {{ $totalClientes }}</span></div></div>
        <div class="stat"><span class="stat-icon amber">⏱</span><div><strong>{{ $porVencer->count() }}</strong><span>Por vencer en {{ $aviso }} días</span></div></div>
        <div class="stat"><span class="stat-icon blue">$</span><div><strong>@bs($ingresosMes)</strong><span>Ingresos del mes</span></div></div>
        <div class="stat"><span class="stat-icon red">%</span><div><strong>@bs($descuentosMes)</strong><span>Descuentos otorgados este mes</span></div></div>
    </div>
    @endmodulo

    <div class="{{ \App\Support\Sprint::activo('vencimientos') ? 'grid-main' : 'stack' }}">
        @modulo('vencimientos')
        <div class="stack">
            <div class="panel">
                <div class="panel-head">
                    <div><h2>Membresías por vencer</h2><p>Llama o escribe a estos socios para que renueven a tiempo.</p></div>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Cliente</th><th>Vence</th><th>Quedan</th><th>Puntos</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($porVencer as $c)
                            <tr>
                                <td><span class="person-cell"><span class="avatar">{{ $c->iniciales() }}</span><span><a href="{{ route('clientes.show', $c) }}">{{ $c->nombre_completo }}</a><small>{{ $c->telefono }}</small></span></span></td>
                                <td class="num">{{ $c->pagadoHasta()->format('d/m/Y') }}</td>
                                <td><span class="badge warn">{{ $c->diasRestantes() }} {{ $c->diasRestantes() === 1 ? 'día' : 'días' }}</span></td>
                                <td class="num">{{ number_format($c->puntos, 0, ',', '.') }}</td>
                                <td class="right">@modulo('renovar-aviso')<a class="button button-primary button-small" href="{{ route('suscripciones.create', ['cliente' => $c->id]) }}">Renovar</a>@endmodulo</td>
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="5">Ninguna membresía vence en los próximos {{ $aviso }} días.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <div><h2>Vencidos en los últimos 30 días</h2><p>Socios que aún se pueden recuperar.</p></div>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Cliente</th><th>Venció</th><th>Nivel</th><th>Puntos</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($vencidosRecientes as $c)
                            <tr>
                                <td><span class="person-cell"><span class="avatar">{{ $c->iniciales() }}</span><span><a href="{{ route('clientes.show', $c) }}">{{ $c->nombre_completo }}</a><small>{{ $c->telefono }}</small></span></span></td>
                                <td class="num">{{ $c->pagadoHasta()->format('d/m/Y') }}</td>
                                <td>{{ $c->nivel()?->nombre ?? '—' }}</td>
                                <td class="num">{{ number_format($c->puntos, 0, ',', '.') }}</td>
                                <td class="right">@modulo('renovar-aviso')<a class="button button-secondary button-small" href="{{ route('suscripciones.create', ['cliente' => $c->id]) }}">Reactivar</a>@endmodulo</td>
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="5">No hay socios vencidos recientemente.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endmodulo

        <div class="{{ \App\Support\Sprint::activo('vencimientos') ? 'stack' : 'grid-2' }}">
            <div class="panel">
                <div class="panel-head"><h2>Promociones vigentes hoy</h2>@if (auth()->user()->esAdministrador())<a class="see-all" href="{{ route('promociones.index') }}">Gestionar</a>@endif</div>
                <div class="list">
                    @forelse ($promociones as $p)
                        <div class="list-item">
                            <div class="grow"><strong>{{ $p->nombre }}</strong><small>{{ $p->condicion() }}</small></div>
                            <span class="badge teal plain">{{ rtrim(rtrim(number_format($p->porcentaje, 2), '0'), '.') }}%</span>
                        </div>
                    @empty
                        <div class="list-item muted">No hay promociones activas.</div>
                    @endforelse
                </div>
            </div>

            {{-- T-011-04 · Indicadores del inicio --}}
            @modulo('indicadores')
            <div class="panel">
                <div class="panel-head"><h2>Últimos pagos</h2><a class="see-all" href="{{ route('suscripciones.index') }}">Ver todos</a></div>
                <div class="list">
                    @forelse ($ultimasSuscripciones as $s)
                        <a class="list-item" href="{{ route('suscripciones.show', $s) }}">
                            <div class="grow"><strong>{{ $s->cliente->nombre_completo }}</strong><small>{{ $s->plan->nombre }} · {{ $s->created_at->format('d/m/Y') }}</small></div>
                            <span class="num">@bs($s->total)</span>
                        </a>
                    @empty
                        <div class="list-item muted">Aún no hay pagos registrados.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel panel-body">
                <dl class="kv">
                    <dt>Clientes nuevos este mes</dt><dd>{{ $nuevosMes }}</dd>
                    <dt>Canjes este mes</dt><dd>{{ $canjesMes }}</dd>
                </dl>
            </div>
            @endmodulo
        </div>
    </div>
</div>
@endsection
