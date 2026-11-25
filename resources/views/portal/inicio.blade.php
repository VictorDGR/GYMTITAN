@extends('layouts.app')

@php
    $hora = now()->hour;
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
@endphp

@section('title', 'Inicio')
@section('kicker', $saludo.', '.$cliente->nombre)
@section('heading', 'Promociones y planes')

@section('content')
<div class="portal">
    @include('portal._barra')

    @if ($promociones->isNotEmpty())
        <section class="carrusel" id="carrusel" aria-roledescription="carrusel" aria-label="Promociones vigentes">
            <div class="carrusel-pista">
                @foreach ($promociones as $i => $promo)
                    <article class="slide c{{ $i % 4 }}" aria-roledescription="diapositiva" aria-label="{{ $i + 1 }} de {{ $promociones->count() }}">
                        <div class="slide-pct"><div><strong>@pct($promo->porcentaje)</strong><span>de descuento</span></div></div>
                        <div style="position:relative;z-index:1">
                            <div class="slide-kicker">{{ $promo->tipoTexto() }}@if ($promo->fecha_fin) · hasta el {{ $promo->fecha_fin->format('d/m/Y') }}@endif</div>
                            <h2>{{ $promo->nombre }}</h2>
                            <p>{{ $promo->descripcion ?: $promo->condicion() }}</p>
                            <p class="muted" style="font-size:12.5px">{{ $promo->condicion() }}</p>
                        </div>
                        <div class="acciones">
                            @if ($promo->aplica)<span class="badge ok">✓ Aplica para ti</span>@endif
                            @if ($promo->es_grupal)
                                <span class="badge neutral">Se paga en grupo en recepción</span>
                                <a class="button button-secondary" href="{{ route('portal.puntos') }}#promociones">Ver detalle</a>
                            @else
                                <a class="button button-primary" href="{{ route('portal.pagar', ['promo' => $promo->id]) }}">Elegir</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($promociones->count() > 1)
                <button class="carrusel-btn prev" type="button" aria-label="Promoción anterior">‹</button>
                <button class="carrusel-btn next" type="button" aria-label="Promoción siguiente">›</button>
                <div class="carrusel-puntos">
                    @foreach ($promociones as $i => $promo)<button type="button" aria-label="Ir a la promoción {{ $i + 1 }}"></button>@endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="stack">
        <div><h2 style="font-size:20px">Elige tu plan</h2><p class="muted" style="margin:2px 0 0">El precio ya incluye tu mejor descuento. Paga en línea con QR o tarjeta.</p></div>
        <div class="planes-socio">
            @foreach ($planes as $p)
                <article class="plan-socio">
                    @if ($p['porcentaje'] > 0)<span class="badge ok plain cinta">−@pct($p['porcentaje'])</span>@endif
                    <h3>{{ $p['plan']->nombre }}</h3>
                    <span class="muted">{{ $p['plan']->duracion_dias }} días de acceso</span>
                    <div>
                        @if ($p['porcentaje'] > 0)<div class="antes">@bs($p['precio_base'])</div>@endif
                        <div class="precio">@bs($p['total'])</div>
                        <div class="por-mes">≈ @bs($p['total'] / max(1, $p['plan']->duracion_dias / 30)) por mes</div>
                    </div>
                    @if ($p['motivo'])<span class="muted" style="font-size:12.5px">Con: {{ $p['motivo'] }}</span>@endif
                    <span class="teal" style="font-size:13px">Ganas {{ number_format($p['plan']->puntos, 0, ',', '.') }} puntos</span>
                    <a class="button button-primary" href="{{ route('portal.pagar', ['plan' => $p['plan']->id]) }}">Elegir</a>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const c = document.getElementById('carrusel');
    if (!c) return;
    const pista = c.querySelector('.carrusel-pista');
    const slides = c.querySelectorAll('.slide');
    const puntos = c.querySelectorAll('.carrusel-puntos button');
    if (slides.length < 2) return;
    let i = 0, timer = null;
    const quieto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const ir = (n) => {
        i = (n + slides.length) % slides.length;
        pista.style.transform = 'translateX(' + (-100 * i) + '%)';
        puntos.forEach((p, k) => p.classList.toggle('activo', k === i));
    };
    const iniciar = () => { if (!quieto) { clearInterval(timer); timer = setInterval(() => ir(i + 1), 5000); } };
    c.querySelector('.prev').addEventListener('click', () => { ir(i - 1); iniciar(); });
    c.querySelector('.next').addEventListener('click', () => { ir(i + 1); iniciar(); });
    puntos.forEach((p, k) => p.addEventListener('click', () => { ir(k); iniciar(); }));
    c.addEventListener('mouseenter', () => clearInterval(timer));
    c.addEventListener('mouseleave', iniciar);
    // Deslizar con el dedo en el celular
    let x0 = null;
    c.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    c.addEventListener('touchend', (e) => { if (x0 === null) return; const d = e.changedTouches[0].clientX - x0; if (Math.abs(d) > 40) { ir(i + (d < 0 ? 1 : -1)); iniciar(); } x0 = null; });
    ir(0); iniciar();
})();
</script>
@endpush
