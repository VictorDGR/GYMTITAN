@extends('layouts.app')

@section('title', 'Comprobante de pago')
@section('kicker', 'Mi membresía')
@section('heading', 'Comprobante de pago #'.str_pad($pago->id, 5, '0', STR_PAD_LEFT))

@section('actions')
    <a class="button button-secondary" href="{{ route('portal.membresia') }}">Ver mi membresía</a>
    <button class="button button-primary" type="button" onclick="window.print()">Imprimir</button>
@endsection

@section('content')
    <div class="panel form-card comprobante">
        <div class="panel-head">
            <div><h2>Gimnasio Titán</h2><p>Comprobante N.º {{ str_pad($pago->id, 5, '0', STR_PAD_LEFT) }} · {{ $pago->created_at->format('d/m/Y H:i') }}</p></div>
            <span class="badge ok">Pagado</span>
        </div>
        <div class="panel-body">
            <dl class="kv">
                <dt>Socio</dt><dd>{{ $cliente->nombre_completo }} · CI {{ $cliente->ci }}</dd>
                <dt>Concepto</dt><dd>Plan {{ $pago->plan->nombre }} ({{ $pago->plan->duracion_dias }} días)</dd>
                <dt>Vigencia</dt><dd>{{ $pago->fecha_inicio->format('d/m/Y') }} al {{ $pago->fecha_fin->format('d/m/Y') }}</dd>
                <dt>Precio del plan</dt><dd>@bs($pago->precio_base)</dd>
                <dt>Descuento</dt><dd>@if ($pago->descuento_porcentaje > 0) −@pct($pago->descuento_porcentaje) · {{ $pago->descuento_motivo }} (−@bs($pago->precio_base - $pago->total)) @else Ninguno @endif</dd>
                <dt>Total pagado</dt><dd style="font-size:20px;font-weight:700">@bs($pago->total)</dd>
                <dt>Método de pago</dt><dd>{{ $pago->metodo_pago }}{{ $pago->user_id === auth()->id() ? ' · en línea' : ' · en recepción' }}</dd>
                <dt>Puntos ganados</dt><dd class="pts-plus">+{{ number_format($pago->puntos_ganados, 0, ',', '.') }}</dd>
            </dl>
        </div>
    </div>
@endsection
