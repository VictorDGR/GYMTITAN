@extends('layouts.app')

@php
    $dias = $cliente->diasRestantes();
    $duracion = $vigente?->plan->duracion_dias ?: 30;
    $ratio = $vigente ? min(1, $dias / $duracion) : 0;
    $circ = 2 * M_PI * 64;
@endphp

@section('title', 'Mi membresía')
@section('kicker', 'Mi cuenta')
@section('heading', 'Mi membresía')

@section('actions')
    <a class="button button-primary" href="{{ route('portal.pagar') }}">{{ $vigente ? 'Renovar mi plan' : 'Pagar un plan' }}</a>
@endsection

@section('content')
<div class="portal">
    <section class="hero-card">
        <div>
            <div class="hello">Tu membresía</div>
            <h2>{{ $cliente->nombre_completo }}</h2>
            <div class="level-chip"><i></i> Nivel {{ $nivel?->nombre ?? 'Bronce' }} · {{ $meses }} {{ $meses === 1 ? 'mes' : 'meses' }} con nosotros</div>
            @if ($cliente->grupo)
                <p class="muted" style="margin:14px 0 0">Inscrito en el grupo <strong>{{ $cliente->grupo->nombre }}</strong> ({{ mb_strtolower($cliente->grupo->tipoTexto()) }}).</p>
            @endif
        </div>
        <div class="membership">
            <div class="ring" aria-label="{{ $dias }} días restantes">
                <svg viewBox="0 0 150 150"><circle class="track" cx="75" cy="75" r="64"/><circle class="value" cx="75" cy="75" r="64" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $circ * (1 - $ratio) }}"/></svg>
                <div class="center"><strong>{{ $dias }}</strong><span>{{ $dias === 1 ? 'día' : 'días' }}<br>restantes</span></div>
            </div>
            <div>
                @if ($vigente)
                    <h3>Plan {{ $vigente->plan->nombre }}</h3>
                    <p>Pagado hasta el {{ $cliente->pagadoHasta()->translatedFormat('d \d\e F \d\e Y') }}</p>
                    <p style="margin-top:10px"><span class="badge {{ $cliente->claseEstado() }}">{{ $cliente->estado() }}</span></p>
                @else
                    <h3>Sin membresía vigente</h3>
                    <p>Paga tu plan en línea y vuelve a entrenar hoy mismo.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Premios por antigüedad</h2><p>Mientras más tiempo entrenas, más beneficios tienes.</p></div></div>
        <div class="panel-body stack">
            @if ($siguiente)
                @php($base = $nivel?->meses_minimos ?? 0)
                <div>
                    <div style="display:flex;justify-content:space-between;gap:12px;font-size:13px;margin-bottom:8px">
                        <span>Te faltan <strong class="teal">{{ $siguiente->meses_minimos - $meses }} {{ $siguiente->meses_minimos - $meses === 1 ? 'mes' : 'meses' }}</strong> para el nivel {{ $siguiente->nombre }}</span>
                        <span class="muted">+{{ number_format($siguiente->bono_puntos, 0, ',', '.') }} pts</span>
                    </div>
                    <div class="progress"><span style="width: {{ max(4, min(100, ($meses - $base) / max(1, $siguiente->meses_minimos - $base) * 100)) }}%"></span></div>
                </div>
            @else
                <p class="note" style="margin:0">Alcanzaste el nivel más alto. ¡Gracias por tu fidelidad!</p>
            @endif
            <div class="levels-track" style="--n: {{ max(1, $niveles->count()) }}">
                @foreach ($niveles as $n)
                    <div class="level-step {{ $nivel?->id === $n->id ? 'current' : ($n->meses_minimos <= $meses ? 'done' : '') }}">
                        <strong>{{ $n->nombre }}</strong>
                        <small>{{ $n->meses_minimos }} meses</small>
                        <small>{{ $n->descuento_porcentaje > 0 ? rtrim(rtrim(number_format($n->descuento_porcentaje, 2), '0'), '.').'% al renovar' : 'Puntos por pago' }}</small>
                    </div>
                @endforeach
            </div>
            @if ($nivel?->beneficios)<p class="muted" style="margin:0;font-size:12.5px">Tu nivel incluye: {{ $nivel->beneficios }}</p>@endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Mis suscripciones</h2></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Plan</th><th>Desde</th><th>Hasta</th><th>Descuento</th><th class="right">Pagado</th><th>Método</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($cliente->suscripciones->sortByDesc('fecha_inicio') as $s)
                    <tr>
                        <td>{{ $s->plan->nombre }}</td>
                        <td class="num">{{ $s->fecha_inicio->format('d/m/Y') }}</td>
                        <td class="num">{{ $s->fecha_fin->format('d/m/Y') }}</td>
                        <td>@if ($s->descuento_porcentaje > 0) {{ $s->descuento_motivo }} (−@pct($s->descuento_porcentaje)) @else — @endif</td>
                        <td class="right num">@bs($s->total)</td>
                        <td>{{ $s->metodo_pago }}</td>
                        <td><span class="badge {{ $s->claseEstado() }}">{{ $s->estado() }}</span></td>
                        <td><a class="icon-button" href="{{ route('portal.comprobante', $s->id) }}">Comprobante</a></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="8">Aún no tienes suscripciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
