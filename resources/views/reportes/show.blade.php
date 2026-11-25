@extends('layouts.app')

@section('title', $titulo)
@section('kicker', 'Reportes')
@section('heading', $titulo)

@section('actions')
    <a class="button button-secondary" href="{{ route('reportes.index') }}">← Todos los reportes</a>
    <button class="button button-primary" type="button" onclick="window.print()">Imprimir</button>
@endsection

@section('content')
<div class="stack reporte">
    <div class="reporte-cabecera">
        <strong>Gimnasio Titán · {{ $titulo }}</strong>
        <span>{{ $subtitulo }}@if ($periodo) · del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}@endif · generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</span>
    </div>

    @if ($periodo || $filtros)
        <form class="panel panel-head inline-form filtros" method="GET">
            @foreach ($filtros as [$nombre, $etiqueta, $opciones, $valor])
                <div class="field">
                    <label for="f-{{ $nombre }}">{{ $etiqueta }}</label>
                    <select id="f-{{ $nombre }}" name="{{ $nombre }}">
                        <option value="">Todos</option>
                        @foreach ($opciones as $clave => $texto)
                            <option value="{{ $clave }}" @selected((string) $valor === (string) $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            @if ($periodo)
                <div class="field"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="{{ $desde->toDateString() }}"></div>
                <div class="field"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ $hasta->toDateString() }}"></div>
            @endif
            <button class="button button-secondary" type="submit">Aplicar</button>
        </form>
    @endif

    @if ($resumen)
        <div class="stats">
            @foreach ($resumen as [$etiqueta, $valor])
                <div class="stat"><div><strong>{{ $valor }}</strong><span>{{ $etiqueta }}</span></div></div>
            @endforeach
        </div>
    @endif

    @foreach ($tablas as $t)
        <div class="panel">
            <div class="panel-head"><h2>{{ $t['titulo'] }}</h2></div>
            <div class="table-scroll">
                <table>
                    <thead><tr>
                        @foreach ($t['columnas'] as $i => $col)<th class="{{ in_array($i, $t['derecha'] ?? [], true) ? 'right' : '' }}">{{ $col }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                    @forelse ($t['filas'] as $fila)
                        <tr>
                            @foreach ($fila as $i => $celda)
                                <td class="{{ in_array($i, $t['derecha'] ?? [], true) ? 'right num' : '' }}">
                                    @if (is_array($celda) && ($celda[0] ?? null) === 'badge')
                                        <span class="badge {{ $celda[2] }}">{{ $celda[1] }}</span>
                                    @elseif (is_array($celda))
                                        <a href="{{ $celda[1] }}">{{ $celda[0] }}</a>
                                    @else
                                        {{ $celda }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="{{ count($t['columnas']) }}">{{ $t['vacio'] ?? 'Sin datos.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if (! empty($t['pie']))<div class="table-footer">{{ $t['pie'] }}</div>@endif
        </div>
    @endforeach
</div>
<style>
    .reporte-cabecera{display:flex;flex-direction:column;gap:2px}
    .reporte-cabecera span{color:var(--muted,#9aa4a0);font-size:13px}
    @media print {
        .filtros{display:none !important}
        .reporte .panel, .reporte .stat{background:#fff !important;border:1px solid #999 !important;box-shadow:none !important}
        .reporte, .reporte *{color:#000 !important}
        .reporte .stats{display:grid;grid-template-columns:repeat(3,1fr)}
        .reporte a{text-decoration:none}
    }
</style>
@endsection
