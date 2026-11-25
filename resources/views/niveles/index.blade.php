@extends('layouts.app')

@section('title', 'Niveles de antigüedad')
@section('kicker', 'Programa de fidelización')
@section('heading', 'Niveles de antigüedad')

@section('actions')
    <a class="button button-primary" href="{{ route('niveles.create') }}">＋ Nuevo nivel</a>
@endsection

@section('content')
<div class="stack">
    <p class="note" style="margin:0">La antigüedad se cuenta con los días realmente pagados (meses de 30 días). Al alcanzar un nivel, el cliente recibe su bono de puntos una sola vez y, desde entonces, el descuento al renovar.</p>
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Nivel</th><th class="right">Desde</th><th class="right">Descuento al renovar</th><th class="right">Bono de puntos</th><th>Beneficios</th><th></th></tr></thead>
                <tbody>
                @forelse ($niveles as $n)
                    <tr>
                        <td><strong>{{ $n->nombre }}</strong></td>
                        <td class="right num">{{ $n->meses_minimos }} meses</td>
                        <td class="right num">@pct($n->descuento_porcentaje)</td>
                        <td class="right num pts-plus">{{ $n->bono_puntos ? '+'.number_format($n->bono_puntos, 0, ',', '.') : '—' }}</td>
                        <td>{{ $n->beneficios ?: '—' }}</td>
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('niveles.edit', $n) }}">Editar</a>
                            <form method="POST" action="{{ route('niveles.destroy', $n) }}" onsubmit="return window.confirm('¿Eliminar este nivel?')">
                                @csrf @method('DELETE')
                                <button class="icon-button danger" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="6">Crea niveles para premiar a los socios más antiguos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
