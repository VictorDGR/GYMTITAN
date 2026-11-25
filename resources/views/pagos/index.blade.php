@extends('layouts.app')

@section('title', 'Historial de pagos')
@section('kicker', 'Recepción')
@section('heading', 'Historial de pagos')

@section('actions')
    <a class="button button-primary" href="{{ route('suscripciones.create') }}">＋ Cobrar plan</a>
@endsection

@section('content')
<div class="stack">
    <div class="stats">
        <div class="stat"><span class="stat-icon blue">$</span><div><strong>@bs($pagos->sum('total'))</strong><span>{{ $pagos->count() }} pagos en el periodo</span></div></div>
        <div class="stat"><span class="stat-icon">◧</span><div><strong>@bs($porMetodo['Efectivo'])</strong><span>En efectivo (recepción)</span></div></div>
        <div class="stat"><span class="stat-icon amber">⌁</span><div><strong>@bs($porMetodo['QR'] + $porMetodo['Tarjeta'] + $porMetodo['Transferencia'])</strong><span>QR, tarjeta y transferencia</span></div></div>
        <div class="stat"><span class="stat-icon red">%</span><div><strong>@bs($descuentos)</strong><span>Descuentos otorgados</span></div></div>
    </div>

    <div class="panel">
        <form class="panel-head inline-form" method="GET">
            <div class="field"><label for="buscar">Socio (nombre o CI)</label><input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Buscar…"></div>
            <div class="field"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="{{ $desde->toDateString() }}"></div>
            <div class="field"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ $hasta->toDateString() }}"></div>
            <div class="field">
                <label for="metodo">Método</label>
                <select id="metodo" name="metodo">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Suscripcion::METODOS as $m)
                        <option @selected($metodo === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button-secondary" type="submit">Filtrar</button>
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr><th>N.º</th><th>Fecha y hora</th><th>Socio</th><th>Plan</th><th class="right">Precio</th><th>Descuento</th><th class="right">Total</th><th>Método</th><th>Registró</th><th></th></tr></thead>
                <tbody>
                @forelse ($pagos as $p)
                    <tr>
                        <td class="num">#{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="num">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('clientes.show', $p->cliente) }}">{{ $p->cliente->nombre_completo }}</a><br><span class="muted">CI {{ $p->cliente->ci }}</span></td>
                        <td>{{ $p->plan->nombre }}@if ($p->lote)<br><a class="badge info plain" href="{{ route('suscripciones.grupal.show', $p->lote) }}">Grupal · {{ $p->grupo?->nombre ?? 'grupo' }}</a>@endif</td>
                        <td class="right num">@bs($p->precio_base)</td>
                        <td>@if ($p->descuento_porcentaje > 0) <span class="badge ok plain">−@pct($p->descuento_porcentaje)</span> <span class="muted">{{ $p->descuento_motivo }}</span> @else — @endif</td>
                        <td class="right num"><strong>@bs($p->total)</strong></td>
                        <td>{{ $p->metodo_pago }}</td>
                        <td class="muted">{{ $p->registradoPor?->name ?? '—' }}</td>
                        <td><a class="button button-secondary" href="{{ route('pagos.show', $p) }}">Comprobante</a></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="10">No hay pagos con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $pagos->count() }} pagos · Total cobrado: <strong>@bs($pagos->sum('total'))</strong></div>
    </div>
</div>
@endsection
