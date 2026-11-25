@extends('layouts.app')

@section('title', 'Canjes')
@section('kicker', 'Recepción')
@section('heading', 'Canjes de puntos')

@section('actions')
    <a class="button button-primary" href="{{ route('canjes.create') }}">★ Nuevo canje</a>
@endsection

@section('content')
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Fecha</th><th>Cliente</th><th>Premio</th><th class="right">Puntos</th><th>Entregado por</th></tr></thead>
                <tbody>
                @forelse ($canjes as $c)
                    <tr>
                        <td class="num">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('clientes.show', $c->cliente) }}">{{ $c->cliente->nombre_completo }}</a></td>
                        <td>{{ $c->premio->nombre }}</td>
                        <td class="right num pts-minus">−{{ number_format($c->puntos, 0, ',', '.') }}</td>
                        <td>{{ $c->registradoPor?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="5">Aún no hay canjes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
