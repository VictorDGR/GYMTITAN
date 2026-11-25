@extends('layouts.app')

@section('title', 'Comprobante grupal')
@section('kicker', 'Suscripciones')
@section('heading', 'Comprobante de pago grupal')

@section('actions')
    <a class="button button-secondary" href="{{ route('grupos.index') }}">Parejas y familias</a>
    <button class="button button-primary" type="button" onclick="window.print()">Imprimir comprobante</button>
@endsection

@section('content')
    <div class="panel comprobante">
        <div class="panel-head">
            <div>
                <h2>Gimnasio Titán · {{ $primera->grupo?->nombre ?? 'Grupo' }}</h2>
                <p>{{ $primera->grupo?->tipoTexto() }} · Plan {{ $primera->plan->nombre }} ({{ $primera->plan->duracion_dias }} días) · {{ $primera->created_at->format('d/m/Y H:i') }} · {{ $primera->metodo_pago }}</p>
            </div>
            <span class="badge ok">Pagado</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>N.º</th><th>Integrante</th><th>CI</th><th>Vigencia</th><th class="right">Precio</th><th>Promoción</th><th class="right">Pagó</th><th class="right">Puntos</th></tr></thead>
                <tbody>
                @foreach ($suscripciones as $s)
                    <tr>
                        <td class="num"><a href="{{ route('suscripciones.show', $s) }}">#{{ str_pad($s->id, 5, '0', STR_PAD_LEFT) }}</a></td>
                        <td>{{ $s->cliente->nombre_completo }}</td>
                        <td class="num">{{ $s->cliente->ci }}</td>
                        <td class="num">{{ $s->fecha_inicio->format('d/m/Y') }} – {{ $s->fecha_fin->format('d/m/Y') }}</td>
                        <td class="right num">@bs($s->precio_base)</td>
                        <td>@if ($s->descuento_porcentaje > 0) −@pct($s->descuento_porcentaje) · {{ $s->descuento_motivo }} @else — @endif</td>
                        <td class="right num">@bs($s->total)</td>
                        <td class="right num pts-plus">+{{ $s->puntos_ganados }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            {{ $suscripciones->count() }} integrantes · Ahorro del grupo: <strong>@bs($suscripciones->sum('precio_base') - $suscripciones->sum('total'))</strong> ·
            Total pagado: <strong style="font-size:18px">@bs($suscripciones->sum('total'))</strong> · Atendido por {{ $primera->registradoPor?->name ?? '—' }}
        </div>
    </div>
@endsection
