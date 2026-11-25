@extends('layouts.app')

@section('title', 'Mi historial')
@section('kicker', 'Mi cuenta')
@section('heading', 'Historial de puntos')

@section('content')
<div class="portal">
    @include('portal._barra')

    <div class="stats">
        <div class="stat"><span class="stat-icon">★</span><div><strong>{{ number_format($cliente->puntos, 0, ',', '.') }}</strong><span>Puntos sin utilizar (disponibles)</span></div></div>
        <div class="stat"><span class="stat-icon blue">＋</span><div><strong>{{ number_format($ganados->sum('puntos'), 0, ',', '.') }}</strong><span>Puntos ganados en total</span></div></div>
        <div class="stat"><span class="stat-icon red">−</span><div><strong>{{ number_format(abs($utilizados->sum('puntos')), 0, ',', '.') }}</strong><span>Puntos utilizados en canjes</span></div></div>
        <div class="stat"><span class="stat-icon amber">♙</span><div><strong>{{ $cliente->referidos->count() }}</strong><span>Amigos recomendados</span></div></div>
    </div>

    <div class="grid-2">
        <section class="panel">
            <div class="panel-head"><div><h2>Puntos ganados</h2><p>Pagos, recomendaciones y bonos por antigüedad.</p></div></div>
            <div class="list">
                @forelse ($ganados as $m)
                    <div class="list-item">
                        <div class="grow"><strong>{{ $m->descripcion }}</strong><small>{{ $m->tipoTexto() }} · {{ $m->created_at->format('d/m/Y') }}</small></div>
                        <span class="num pts-plus">+{{ number_format($m->puntos, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="list-item muted">Todavía no ganaste puntos.</div>
                @endforelse
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>Puntos utilizados</h2><p>Premios que canjeaste en recepción.</p></div></div>
            <div class="list">
                @forelse ($utilizados as $m)
                    <div class="list-item">
                        <div class="grow"><strong>{{ $m->descripcion }}</strong><small>{{ $m->tipoTexto() }} · {{ $m->created_at->format('d/m/Y') }}</small></div>
                        <span class="num pts-minus">{{ number_format($m->puntos, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="list-item muted">Aún no utilizaste tus puntos. ¡Mira los premios disponibles!</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="panel">
        <div class="panel-head"><div><h2>Mi código de recomendación</h2><p>Por cada amigo que se inscriba de tu parte ganas {{ number_format($puntosReferido, 0, ',', '.') }} puntos.</p></div></div>
        <div class="panel-body stack">
            <div>
                <span class="muted" style="font-size:12.5px">Tu amigo debe dar este código en recepción al inscribirse:</span><br>
                <span class="referral-code">{{ $cliente->ci }}</span>
            </div>
            @if ($cliente->referidos->isNotEmpty())
                <p style="margin:0">Ya recomendaste a: <strong>{{ $cliente->referidos->pluck('nombre_completo')->join(', ', ' y ') }}</strong>.</p>
            @else
                <p class="muted" style="margin:0">Aún no recomendaste a nadie.</p>
            @endif
        </div>
    </section>
</div>
@endsection
