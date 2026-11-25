@extends('layouts.app')

@section('title', $cliente->nombre_completo)
@section('kicker', 'Clientes')
@section('heading', $cliente->nombre_completo)

@section('actions')
    <a class="button button-secondary" href="{{ route('clientes.edit', $cliente) }}">Editar</a>
    @modulo('canjes')<a class="button button-secondary" href="{{ route('canjes.create', ['cliente' => $cliente->id]) }}">★ Canjear puntos</a>@endmodulo
    <a class="button button-primary" href="{{ route('suscripciones.create', ['cliente' => $cliente->id]) }}">Cobrar plan</a>
@endsection

@section('content')
<div class="stack">
    <div class="stats">
        <div class="stat"><span class="stat-icon">★</span><div><strong>{{ number_format($cliente->puntos, 0, ',', '.') }}</strong><span>Puntos disponibles</span></div></div>
        @modulo('niveles')
        <div class="stat"><span class="stat-icon amber">▲</span><div><strong>{{ $nivel?->nombre ?? '—' }}</strong><span>{{ $cliente->mesesAntiguedad() }} meses de antigüedad</span></div></div>
        @endmodulo
        <div class="stat"><span class="stat-icon blue">⏱</span><div><strong>{{ $cliente->diasRestantes() }}</strong><span>Días restantes</span></div></div>
        <div class="stat"><span class="stat-icon {{ $cliente->claseEstado() === 'bad' ? 'red' : '' }}">●</span><div><strong style="font-size:17px">{{ $cliente->estado() }}</strong><span>{{ $cliente->pagadoHasta() ? 'Hasta '.$cliente->pagadoHasta()->format('d/m/Y') : 'Sin pagos' }}</span></div></div>
    </div>

    <div class="grid-main">
        <div class="stack">
            <div class="panel">
                <div class="panel-head"><h2>Suscripciones</h2></div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Plan</th><th>Periodo</th><th>Descuento</th><th class="right">Pagado</th><th>Puntos</th><th>Estado</th></tr></thead>
                        <tbody>
                        @forelse ($suscripciones as $s)
                            <tr>
                                <td><a href="{{ route('suscripciones.show', $s) }}">{{ $s->plan->nombre }}</a></td>
                                <td class="num">{{ $s->fecha_inicio->format('d/m/Y') }} – {{ $s->fecha_fin->format('d/m/Y') }}</td>
                                <td>{{ $s->descuento_motivo ? $s->descuento_motivo : '—' }} @if ($s->descuento_porcentaje > 0)<span class="muted">(@pct($s->descuento_porcentaje))</span>@endif</td>
                                <td class="right num">@bs($s->total)</td>
                                <td class="pts-plus num">+{{ $s->puntos_ganados }}</td>
                                <td><span class="badge {{ $s->claseEstado() }}">{{ $s->estado() }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="6">Todavía no pagó ninguna suscripción.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h2>Movimientos de puntos</h2></div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th class="right">Puntos</th></tr></thead>
                        <tbody>
                        @forelse ($movimientos as $m)
                            <tr>
                                <td class="num">{{ $m->created_at->format('d/m/Y') }}</td>
                                <td><span class="badge neutral plain">{{ $m->tipoTexto() }}</span></td>
                                <td>{{ $m->descripcion }}</td>
                                <td class="right num {{ $m->puntos >= 0 ? 'pts-plus' : 'pts-minus' }}">{{ $m->puntos >= 0 ? '+' : '' }}{{ number_format($m->puntos, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="4">Sin movimientos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="panel">
                <div class="panel-head"><h2>Datos</h2></div>
                <div class="panel-body">
                    <dl class="kv">
                        <dt>CI</dt><dd>{{ $cliente->ci }}</dd>
                        <dt>Correo</dt><dd>{{ $cliente->email }}</dd>
                        <dt>Teléfono</dt><dd>{{ $cliente->telefono ?: '—' }}</dd>
                        <dt>Nacimiento</dt><dd>{{ $cliente->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</dd>
                        <dt>Socio desde</dt><dd>{{ $cliente->fechaIngreso()?->format('d/m/Y') ?? '—' }}</dd>
                        <dt>Recomendado por</dt><dd>@if ($cliente->referidoPor)<a class="teal" href="{{ route('clientes.show', $cliente->referidoPor) }}">{{ $cliente->referidoPor->nombre_completo }}</a>@else — @endif</dd>
                        <dt>Recomendó a</dt><dd>{{ $cliente->referidos->count() }} {{ $cliente->referidos->count() === 1 ? 'persona' : 'personas' }}</dd>
                        @modulo('grupos')
                        <dt>Grupo</dt><dd>{{ $cliente->grupo ? $cliente->grupo->nombre.' ('.$cliente->grupo->tipoTexto().')' : '—' }}</dd>
                        @endmodulo
                    </dl>
                </div>
            </div>

            @modulo('niveles')
            <div class="panel">
                <div class="panel-head"><h2>Antigüedad</h2></div>
                <div class="panel-body stack">
                    @if ($siguiente)
                        @php($base = $nivel?->meses_minimos ?? 0)
                        <div class="muted" style="font-size:13px">Faltan {{ $siguiente->meses_minimos - $cliente->mesesAntiguedad() }} meses para {{ $siguiente->nombre }} (+{{ $siguiente->bono_puntos }} pts)</div>
                        <div class="progress"><span style="width: {{ max(4, min(100, ($cliente->mesesAntiguedad() - $base) / max(1, $siguiente->meses_minimos - $base) * 100)) }}%"></span></div>
                    @else
                        <div class="muted" style="font-size:13px">Está en el nivel más alto.</div>
                    @endif
                    @if ($nivel?->beneficios)<div class="note">{{ $nivel->beneficios }}</div>@endif
                </div>
            </div>
            @endmodulo

            <form class="panel" id="ajustar-puntos" method="POST" action="{{ route('clientes.puntos', $cliente) }}">
                @csrf
                <div class="panel-head"><div><h2>Ajustar puntos</h2><p>Para bonos especiales o correcciones. Usa negativo para restar.</p></div></div>
                <div class="panel-body stack">
                    <div class="field"><label for="puntos">Puntos</label><input id="puntos" name="puntos" type="number" step="1" value="{{ old('puntos') }}" placeholder="Ej.: 50 o -20" required><x-error field="puntos" /></div>
                    <div class="field"><label for="motivo">Motivo</label><input id="motivo" name="motivo" value="{{ old('motivo') }}" placeholder="Ej.: Ganó el reto de 30 días" required><x-error field="motivo" /></div>
                    <button class="button button-secondary" type="submit">Aplicar ajuste</button>
                </div>
            </form>

            <div class="panel panel-body stack">
                <form method="POST" action="{{ route('clientes.restablecer', $cliente) }}">
                    @csrf
                    <button class="button button-secondary button-block" type="submit">Restablecer contraseña a su CI</button>
                </form>
                <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return window.confirm('¿Eliminar este cliente con todas sus suscripciones y puntos?')">
                    @csrf @method('DELETE')
                    <button class="button button-danger button-block" type="submit">Eliminar cliente</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
