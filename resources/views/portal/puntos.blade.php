@extends('layouts.app')

@section('title', 'Puntos y premios')
@section('kicker', 'Mi cuenta')
@section('heading', 'Puntos y premios')

@section('content')
<div class="portal">
    @include('portal._barra')

    <section class="hero-card" style="grid-template-columns:minmax(0,1fr)">
        <div>
            <div class="hello">Tienes</div>
            <div class="points-big" style="margin-top:4px"><strong>{{ number_format($cliente->puntos, 0, ',', '.') }}</strong><span>puntos para canjear</span></div>
            <p class="muted" style="margin:12px 0 0">Ganas puntos con cada pago, al recomendar a un amigo y al subir de nivel. Pide tu premio en recepción.</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Premios</h2><p>Los marcados en turquesa ya los puedes canjear.</p></div></div>
        <div class="panel-body cards-grid">
            @forelse ($premios as $premio)
                @php($faltan = max(0, $premio->puntos - $cliente->puntos))
                <div class="reward {{ $faltan === 0 && $premio->disponible() ? 'ready' : '' }}">
                    <div class="reward-head"><h3>{{ $premio->nombre }}</h3><span class="cost">{{ number_format($premio->puntos, 0, ',', '.') }} pts</span></div>
                    @if ($premio->descripcion)<small>{{ $premio->descripcion }}</small>@endif
                    <div class="progress"><span style="width: {{ min(100, $cliente->puntos / max(1, $premio->puntos) * 100) }}%"></span></div>
                    <small>
                        @if (! $premio->disponible()) Agotado por ahora
                        @elseif ($faltan === 0) <span class="teal">¡Ya puedes canjearlo en recepción!</span>
                        @else Te faltan {{ number_format($faltan, 0, ',', '.') }} puntos
                        @endif
                    </small>
                </div>
            @empty
                <p class="muted">Aún no hay premios en el catálogo.</p>
            @endforelse
        </div>
    </section>

    <section class="panel" id="promociones">
        <div class="panel-head"><div><h2>Promociones vigentes</h2><p>Qué incluye cada una y cuándo aplica. Elige una para pagar tu plan con ese descuento.</p></div></div>
        <div class="panel-body cards-grid">
            @forelse ($promociones as $promo)
                <div class="promo-detalle {{ $promo->aplica ? 'yours' : '' }}">
                    <div class="cab">
                        <div class="promo-pct">@pct($promo->porcentaje)</div>
                        <div><h3 style="font-size:15px;font-weight:700">{{ $promo->nombre }}</h3>
                            @if ($promo->aplica)<span class="badge ok">✓ Aplica para ti</span>@else<span class="badge neutral plain">No aplica para ti ahora</span>@endif
                        </div>
                    </div>
                    @if ($promo->descripcion)<p class="muted" style="margin:0;font-size:13px">{{ $promo->descripcion }}</p>@endif
                    <dl>
                        <dt>Tipo</dt><dd>{{ $promo->tipoTexto() }}</dd>
                        <dt>Cuándo aplica</dt><dd>{{ $promo->condicion() }}</dd>
                        <dt>Planes</dt><dd>{{ $promo->plan?->nombre ?? 'Todos los planes' }}</dd>
                        @if ($promo->fecha_fin)<dt>Vigente hasta</dt><dd>{{ $promo->fecha_fin->format('d/m/Y') }}</dd>@endif
                        <dt>Acumulable</dt><dd>No, se aplica un solo descuento</dd>
                    </dl>
                    @if ($promo->es_grupal)
                        <span class="note" style="font-size:12.5px">Se paga en grupo: acércate a recepción con tu {{ $promo->tipo === 'pareja' ? 'pareja' : 'familia' }}.</span>
                    @else
                        <a class="button {{ $promo->aplica ? 'button-primary' : 'button-secondary' }}" href="{{ route('portal.pagar', ['promo' => $promo->id]) }}">Elegir</a>
                    @endif
                </div>
            @empty
                <p class="muted">No hay promociones activas en este momento.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
