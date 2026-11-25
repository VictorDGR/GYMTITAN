@extends('layouts.app')

@section('title', 'Recepcionistas')
@section('kicker', 'Administración')
@section('heading', 'Recepcionistas')

@section('actions')
    <a class="button button-primary" href="{{ route('recepcionistas.create') }}">＋ Nueva recepcionista</a>
@endsection

@section('content')
    <div class="panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Nombre</th><th>Correo</th><th>CI</th><th></th></tr></thead>
                <tbody>
                @forelse ($recepcionistas as $r)
                    <tr>
                        <td><span class="person-cell"><span class="avatar">{{ $r->iniciales() }}</span>{{ $r->name }}</span></td>
                        <td>{{ $r->email }}</td>
                        <td class="num">{{ $r->ci ?: '—' }}</td>
                        <td><div class="action-group">
                            <a class="icon-button" href="{{ route('recepcionistas.edit', $r) }}">Editar</a>
                            <form method="POST" action="{{ route('recepcionistas.destroy', $r) }}" onsubmit="return window.confirm('¿Eliminar esta cuenta?')">
                                @csrf @method('DELETE')
                                <button class="icon-button danger" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="4">Aún no hay recepcionistas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
