@extends('layouts.app')

@section('title', 'Reportes')
@section('kicker', 'Administración')
@section('heading', 'Reportes')

@section('content')
<div class="stack">
    <p class="note" style="margin:0">Cada reporte corresponde a una salida del sistema. Ábrelo, ajusta los filtros y usa <strong>Imprimir</strong> para guardarlo en PDF. Los comprobantes de pago y de suscripción están en Historial de pagos, y el portal del socio lo ve cada socio con su cuenta.</p>
    <div class="reportes-grid">
        @foreach ($reportes as $clave => [$titulo, $salida, $icono])
            <a class="reporte-card" href="{{ route('reportes.show', $clave) }}">
                <span class="stat-icon">{{ $icono }}</span>
                <div><strong>{{ $titulo }}</strong><small>{{ $salida }}</small></div>
            </a>
        @endforeach
    </div>
</div>
<style>
    .reportes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
    .reporte-card{display:flex;align-items:center;gap:14px;padding:18px;border:1px solid var(--line,#2a3434);border-radius:14px;background:var(--panel,#171c1c);color:inherit;text-decoration:none;transition:border-color .15s,transform .15s}
    .reporte-card:hover{border-color:#14b8a6;transform:translateY(-1px)}
    .reporte-card strong{display:block}
    .reporte-card small{color:var(--muted,#9aa4a0)}
</style>
@endsection
