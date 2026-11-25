@extends('layouts.app')

@section('title', 'Comprobante')
@section('kicker', 'Suscripciones')
@section('heading', 'Comprobante de pago #'.str_pad($suscripcion->id, 5, '0', STR_PAD_LEFT))

@section('actions')
    <a class="button button-secondary" href="{{ route('clientes.show', $suscripcion->cliente) }}">Ver cliente</a>
    <a class="button button-primary" href="{{ route('suscripciones.create') }}">Cobrar otro plan</a>
@endsection

@section('content')
    <div class="panel form-card">
        <div class="panel-head">
            <div><h2>{{ $suscripcion->cliente->nombre_completo }}</h2><p>CI {{ $suscripcion->cliente->ci }}</p></div>
            <span class="badge {{ $suscripcion->claseEstado() }}">{{ $suscripcion->estado() }}</span>
        </div>
        <div class="panel-body">
            <dl class="kv">
                <dt>Plan</dt><dd>{{ $suscripcion->plan->nombre }} ({{ $suscripcion->plan->duracion_dias }} días)</dd>
                <dt>Vigencia</dt><dd>{{ $suscripcion->fecha_inicio->format('d/m/Y') }} al {{ $suscripcion->fecha_fin->format('d/m/Y') }}</dd>
                <dt>Precio del plan</dt><dd>@bs($suscripcion->precio_base)</dd>
                <dt>Descuento</dt><dd>@if ($suscripcion->descuento_porcentaje > 0) −@pct($suscripcion->descuento_porcentaje) · {{ $suscripcion->descuento_motivo }} (−@bs($suscripcion->precio_base - $suscripcion->total)) @else Ninguno @endif</dd>
                <dt>Total pagado</dt><dd style="font-size:20px;font-weight:700">@bs($suscripcion->total)</dd>
                <dt>Método de pago</dt><dd>{{ $suscripcion->metodo_pago }}</dd>
                <dt>Puntos ganados</dt><dd class="pts-plus">+{{ number_format($suscripcion->puntos_ganados, 0, ',', '.') }}</dd>
                <dt>Saldo de puntos actual</dt><dd>{{ number_format($suscripcion->cliente->puntos, 0, ',', '.') }}</dd>
                <dt>Registrado por</dt><dd>{{ $suscripcion->registradoPor?->name ?? '—' }} · {{ $suscripcion->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
    </div>
@endsection
