@extends('layouts.app')

@section('title', 'Nuevo canje')
@section('kicker', 'Canjes')
@section('heading', 'Canjear puntos')

@section('content')
<div class="stack">
    <form class="panel" method="GET" action="{{ route('canjes.create') }}">
        <div class="panel-head inline-form">
            <div class="field" style="flex:3">
                <label for="cliente">Cliente</label>
                <select id="cliente" name="cliente" onchange="this.form.submit()" required>
                    <option value="">Selecciona un cliente…</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id }}" @selected($cliente?->id === $c->id)>{{ $c->apellido }}, {{ $c->nombre }} · {{ number_format($c->puntos, 0, ',', '.') }} pts</option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="button button-secondary" type="submit">Ver premios</button></noscript>
        </div>
    </form>

    @if ($cliente)
        <form class="panel" method="POST" action="{{ route('canjes.store') }}">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            <div class="panel-head"><div><h2>{{ $cliente->nombre_completo }}</h2><p>Tiene <strong class="teal">{{ number_format($cliente->puntos, 0, ',', '.') }} puntos</strong> disponibles.</p></div></div>
            <div class="panel-body stack">
                <div class="plan-options">
                    @forelse ($premios as $p)
                        @php($alcanza = $cliente->puntos >= $p->puntos && $p->disponible())
                        <label class="plan-option" style="{{ $alcanza ? '' : 'opacity:.5;cursor:not-allowed' }}">
                            <input type="radio" name="premio_id" value="{{ $p->id }}" @disabled(! $alcanza) required>
                            <h3>{{ $p->nombre }}</h3>
                            <span class="meta">{{ $p->descripcion }}</span>
                            <div class="price num" style="color:var(--amber)">{{ number_format($p->puntos, 0, ',', '.') }} pts</div>
                            <span class="meta">
                                @if (! $p->disponible()) Agotado
                                @elseif (! $alcanza) Le faltan {{ number_format($p->puntos - $cliente->puntos, 0, ',', '.') }} puntos
                                @else Disponible{{ $p->stock !== null ? ' · quedan '.$p->stock : '' }}
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="muted">El catálogo de premios está vacío.</p>
                    @endforelse
                </div>
                <x-error field="premio_id" />
                <div class="form-actions">
                    <a class="button button-secondary" href="{{ route('clientes.show', $cliente) }}">Cancelar</a>
                    <button class="button button-primary" type="submit">Confirmar canje</button>
                </div>
            </div>
        </form>
    @else
        <div class="panel panel-body muted">Elige un cliente para ver los premios que puede canjear.</div>
    @endif
</div>
@endsection
