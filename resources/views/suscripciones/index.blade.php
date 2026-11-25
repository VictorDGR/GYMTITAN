@extends('layouts.app')

@section('title', 'Suscripciones')
@section('kicker', 'Recepción')
@section('heading', 'Suscripciones')

@section('actions')
    @modulo('grupos')<a class="button button-secondary" href="{{ route('suscripciones.grupal') }}">⚇ Cobrar al grupo</a>@endmodulo
    <a class="button button-primary" href="{{ route('suscripciones.create') }}">＋ Cobrar plan</a>
@endsection

@section('content')
    <div class="panel">
        <form class="panel-head inline-form" method="GET">
            <div class="field"><label for="desde">Pagadas desde</label><input id="desde" name="desde" type="date" value="{{ $desde->toDateString() }}"></div>
            <div class="field"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ $hasta->toDateString() }}"></div>
            <button class="button button-secondary" type="submit">Filtrar</button>
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Fecha de pago</th><th>Cliente</th><th>Plan</th><th>Periodo</th><th>Descuento</th><th class="right">Total</th><th>Método</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse ($suscripciones as $s)
                    <tr>
                        <td class="num"><a href="{{ route('suscripciones.show', $s) }}">{{ $s->created_at->format('d/m/Y') }}</a></td>
                        <td><a href="{{ route('clientes.show', $s->cliente) }}">{{ $s->cliente->nombre_completo }}</a></td>
                        <td>{{ $s->plan->nombre }}</td>
                        <td class="num">{{ $s->fecha_inicio->format('d/m/Y') }} – {{ $s->fecha_fin->format('d/m/Y') }}</td>
                        <td>@if ($s->descuento_porcentaje > 0) <span class="badge ok plain">−@pct($s->descuento_porcentaje)</span> <span class="muted">{{ $s->descuento_motivo }}</span> @else — @endif</td>
                        <td class="right num">@bs($s->total)</td>
                        <td>{{ $s->metodo_pago }}</td>
                        <td><span class="badge {{ $s->claseEstado() }}">{{ $s->estado() }}</span></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="8">No hay pagos en estas fechas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $suscripciones->count() }} pagos · Total cobrado: <strong>@bs($suscripciones->sum('total'))</strong></div>
    </div>
@endsection
