@extends('layouts.app')

@section('title', 'Promociones')
@section('kicker', 'Programa de fidelización')
@section('heading', 'Promociones y descuentos')

@section('actions')
    <a class="button button-primary" href="{{ route('promociones.create') }}">＋ Nueva promoción</a>
@endsection

@section('content')
<div class="stack">
    <p class="note" style="margin:0">Al cobrar un plan el sistema revisa todas las promociones vigentes y el descuento por nivel de antigüedad, y aplica solo el mayor.</p>
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Promoción</th><th>Tipo</th><th class="right">Descuento</th><th>Cuándo aplica</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($promociones as $p)
                    @php($vigente = in_array($p->id, $vigentes, true))
                    <tr>
                        <td><strong>{{ $p->nombre }}</strong>@if ($p->descripcion)<br><small class="muted">{{ $p->descripcion }}</small>@endif</td>
                        <td><span class="badge neutral plain">{{ $p->tipoTexto() }}</span></td>
                        <td class="right num"><strong>@pct($p->porcentaje)</strong></td>
                        <td>{{ $p->condicion() }}</td>
                        <td>
                            @if (! $p->activo)<span class="badge neutral">Desactivada</span>
                            @elseif ($vigente)<span class="badge ok">Vigente</span>
                            @elseif ($p->fecha_inicio?->isFuture())<span class="badge info">Programada</span>
                            @else<span class="badge neutral">Finalizada</span>
                            @endif
                        </td>
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('promociones.edit', $p) }}">Editar</a>
                            <form method="POST" action="{{ route('promociones.destroy', $p) }}" onsubmit="return window.confirm('¿Eliminar esta promoción?')">
                                @csrf @method('DELETE')
                                <button class="icon-button danger" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="6">Aún no hay promociones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
