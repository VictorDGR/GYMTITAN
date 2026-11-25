{{-- Resumen corto del socio, arriba de cada apartado del portal --}}
<div class="portal-top">
    <div class="grow"><strong>{{ $cliente->nombre_completo }}</strong> <span class="muted">· CI {{ $cliente->ci }}</span></div>
    <span class="chip">★ <strong>{{ number_format($cliente->puntos, 0, ',', '.') }}</strong> puntos</span>
    <span class="chip">▲ Nivel <strong>{{ $cliente->nivel()?->nombre ?? 'Bronce' }}</strong></span>
    <span class="chip"><span class="badge {{ $cliente->claseEstado() }} plain">{{ $cliente->estado() }}</span>
        @if ($cliente->diasRestantes() > 0)<strong>{{ $cliente->diasRestantes() }}</strong> días restantes @endif
    </span>
</div>
