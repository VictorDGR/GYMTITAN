@extends('layouts.app')

@section('title', 'Pagar mi plan')
@section('kicker', 'Pago en línea')
@section('heading', 'Pagar mi plan')

@section('content')
<div class="portal">
    @if ($aviso)<div class="alert">{{ $aviso }}</div>@endif

    <form id="form-pago" method="POST" action="{{ route('portal.pagar.store') }}" class="pago-grid">
        @csrf
        <div class="stack">
            <section class="panel panel-body stack">
                <div class="paso"><b>1</b> Elige tu plan</div>
                <div class="plan-options">
                    @foreach ($planes as $p)
                        <label class="plan-option">
                            <input type="radio" name="plan_id" value="{{ $p['plan']->id }}" data-plan="{{ $p['plan']->nombre }}" data-dias="{{ $p['plan']->duracion_dias }}" data-precio="{{ $p['precio_base'] }}" data-puntos="{{ $p['plan']->puntos }}"
                                   data-descuentos="{{ $p['opciones']->map(fn ($d) => ['clave' => $d['clave'], 'motivo' => $d['motivo'], 'porcentaje' => $d['porcentaje'], 'total' => $d['total']])->values()->toJson() }}"
                                   @checked((int) old('plan_id', $planId) === $p['plan']->id) required>
                            <h3>{{ $p['plan']->nombre }}</h3>
                            <span class="meta">{{ $p['plan']->duracion_dias }} días</span>
                            <div>
                                @if ($p['porcentaje'] > 0)<span class="old">@bs($p['precio_base'])</span>@endif
                                <div class="price num">@bs($p['total'])</div>
                            </div>
                            <span class="meta teal">+{{ number_format($p['plan']->puntos, 0, ',', '.') }} puntos</span>
                        </label>
                    @endforeach
                </div>
                <x-error field="plan_id" />
            </section>

            <section class="panel panel-body stack">
                <div class="paso"><b>2</b> Elige tu promoción</div>
                <div class="field" style="margin:0">
                    <label for="descuento">Promoción o descuento</label>
                    <select id="descuento" name="descuento"></select>
                    <span class="hint">Solo se aplica un descuento. Las promociones de pareja y familia se pagan en grupo en recepción.</span>
                </div>
            </section>

            <section class="panel panel-body stack">
                <div class="paso"><b>3</b> Elige cómo pagar</div>
                <div class="metodos">
                    <label class="metodo"><input type="radio" name="metodo_pago" value="QR" @checked(old('metodo_pago', 'QR') === 'QR')> ▦ Pagar con QR</label>
                    <label class="metodo"><input type="radio" name="metodo_pago" value="Tarjeta" @checked(old('metodo_pago') === 'Tarjeta')> ▭ Tarjeta de débito o crédito</label>
                </div>
                <x-error field="metodo_pago" />

                <div id="pago-qr" class="qr-pago">
                    <div class="qr-marco"><svg id="qr-svg" viewBox="0 0 29 29" shape-rendering="crispEdges" aria-label="Código QR de pago"></svg></div>
                    <div class="qr-info">
                        <span class="qr-kicker">Pago con QR · Gimnasio Titán</span>
                        <strong class="qr-monto">Bs <span id="qr-monto">0,00</span></strong>
                        <span class="muted">Escanea el código con la app de tu banco.</span>
                        <span id="qr-estado" class="qr-estado"><i></i> Esperando el pago…</span>
                    </div>
                </div>

                {{-- Pago con tarjeta: demostrativo, los datos de la tarjeta no se envían ni se guardan --}}
                <div id="pago-tarjeta" class="tarjeta-pago stack" hidden>
                    <div class="tarjeta-visual" aria-hidden="true">
                        <div class="chip-oro"></div>
                        <div class="numero" id="tv-numero">•••• •••• •••• ••••</div>
                        <div class="pie"><span id="tv-nombre">{{ $cliente->nombre_completo }}</span><span id="tv-vence">MM/AA</span></div>
                    </div>
                    <div class="form-grid">
                        <div class="field full"><label for="t-numero">Número de tarjeta</label><input id="t-numero" inputmode="numeric" autocomplete="off" maxlength="19" placeholder="0000 0000 0000 0000"></div>
                        <div class="field full"><label for="t-nombre">Nombre en la tarjeta</label><input id="t-nombre" autocomplete="off" value="{{ mb_strtoupper($cliente->nombre_completo) }}"></div>
                        <div class="field"><label for="t-vence">Vencimiento</label><input id="t-vence" inputmode="numeric" autocomplete="off" maxlength="5" placeholder="MM/AA"></div>
                        <div class="field"><label for="t-cvv">CVV</label><input id="t-cvv" inputmode="numeric" autocomplete="off" maxlength="4" placeholder="123"></div>
                    </div>
                    <small class="field-error" id="t-error" hidden></small>
                    <span class="pago-seguro">🔒 Pago de demostración: los datos de la tarjeta no se envían ni se guardan.</span>
                </div>
            </section>
        </div>

        <aside class="panel panel-body stack pago-resumen">
            <h2 style="font-size:17px">Resumen de tu pago</h2>
            <dl class="kv" style="margin:0">
                <dt>Plan</dt><dd id="r-plan">—</dd>
                <dt>Vigencia</dt><dd id="r-vigencia">—</dd>
                <dt>Precio</dt><dd id="r-precio">—</dd>
                <dt>Descuento</dt><dd id="r-descuento">—</dd>
                <dt>Ganas</dt><dd id="r-puntos" class="teal">—</dd>
            </dl>
            <div><span class="muted">Total a pagar</span><div class="total" id="r-total">—</div></div>
            <button class="button button-primary" id="btn-pagar" type="submit" disabled>Pagar</button>
            <small class="muted" id="r-ayuda">Escanea el QR para continuar.</small>
            <a class="button button-secondary" href="{{ route('portal') }}">Cancelar</a>
        </aside>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('form-pago');
    const sel = document.getElementById('descuento');
    const btn = document.getElementById('btn-pagar');
    const ayuda = document.getElementById('r-ayuda');
    const inicio = new Date({{ \Illuminate\Support\Js::from($inicio->toDateString()) }} + 'T00:00:00');
    const claveInicial = {{ \Illuminate\Support\Js::from(old('descuento', $clave)) }};
    const bs = (n) => 'Bs ' + Number(n).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pct = (n) => String(+Number(n).toFixed(2)).replace('.', ',') + '%';
    const fecha = (d) => d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const plan = () => document.querySelector('input[name=plan_id]:checked');
    const metodo = () => document.querySelector('input[name=metodo_pago]:checked')?.value;
    let total = 0, qrListo = false, espera = null, primera = true;

    function llenar() {
        const p = plan(); if (!p) return;
        const previo = primera ? claveInicial : sel.value;
        primera = false;
        const lista = JSON.parse(p.dataset.descuentos || '[]');
        sel.innerHTML = '';
        lista.forEach((d, i) => {
            const o = new Option(d.motivo + ' · −' + pct(d.porcentaje) + ' → ' + bs(d.total) + (i === 0 ? ' (el mejor)' : ''), d.clave);
            Object.assign(o.dataset, { total: d.total, motivo: d.motivo, pct: d.porcentaje });
            sel.add(o);
        });
        const sin = new Option((lista.length ? 'Sin descuento' : 'No tienes descuentos disponibles') + ' → ' + bs(p.dataset.precio), 'ninguno');
        Object.assign(sin.dataset, { total: p.dataset.precio, motivo: '', pct: 0 });
        sel.add(sin);
        if (previo && [...sel.options].some((o) => o.value === previo)) sel.value = previo;
        resumen();
    }

    function resumen() {
        const p = plan(), o = sel.selectedOptions[0]; if (!p || !o) return;
        total = +o.dataset.total;
        const fin = new Date(inicio); fin.setDate(fin.getDate() + (+p.dataset.dias) - 1);
        document.getElementById('r-plan').textContent = p.dataset.plan + ' (' + p.dataset.dias + ' días)';
        document.getElementById('r-vigencia').textContent = fecha(inicio) + ' al ' + fecha(fin);
        document.getElementById('r-precio').textContent = bs(p.dataset.precio);
        document.getElementById('r-descuento').textContent = o.dataset.motivo ? '−' + bs(p.dataset.precio - total) + ' · ' + o.dataset.motivo : 'Ninguno';
        document.getElementById('r-puntos').textContent = '+' + Number(p.dataset.puntos).toLocaleString('es-BO') + ' puntos';
        document.getElementById('r-total').textContent = bs(total);
        btn.textContent = 'Pagar ' + bs(total);
        cambiarMetodo();
    }

    // ---- QR (demostrativo): a los pocos segundos el pago aparece como recibido
    function dibujarQR(semilla) {
        let h = 2166136261;
        for (const c of semilla) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); }
        const azar = () => { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return (h >>> 0) / 4294967296; };
        const N = 29, libre = (x, y) => !((x < 8 && y < 8) || (x > N - 9 && y < 8) || (x < 8 && y > N - 9) || (x > 19 && x < 25 && y > 19 && y < 25));
        let d = '';
        for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) if (azar() < 0.5 && libre(x, y)) d += 'M' + x + ' ' + y + 'h1v1h-1z';
        const ojo = (x, y) => 'M' + x + ' ' + y + 'h7v7h-7zM' + (x + 1) + ' ' + (y + 1) + 'v5h5v-5zM' + (x + 2) + ' ' + (y + 2) + 'h3v3h-3z';
        d += ojo(0, 0) + ojo(N - 7, 0) + ojo(0, N - 7) + 'M20 20h5v5h-5zM21 21v3h3v-3zM22 22h1v1h-1z';
        document.getElementById('qr-svg').innerHTML = '<rect width="29" height="29" fill="#fff"/><path fill="#111" fill-rule="evenodd" d="' + d + '"/>';
    }

    function cambiarMetodo() {
        const esQR = metodo() === 'QR';
        document.getElementById('pago-qr').hidden = !esQR;
        document.getElementById('pago-tarjeta').hidden = esQR;
        clearTimeout(espera);
        if (esQR) {
            qrListo = false;
            document.getElementById('qr-monto').textContent = bs(total).replace('Bs ', '');
            dibujarQR(String(total) + Date.now());
            const est = document.getElementById('qr-estado');
            est.className = 'qr-estado'; est.innerHTML = '<i></i> Esperando el pago…';
            btn.disabled = true; ayuda.textContent = 'Escanea el QR con la app de tu banco para continuar.';
            espera = setTimeout(() => {
                qrListo = true;
                est.className = 'qr-estado recibido'; est.innerHTML = '<i></i> Pago recibido ✓';
                btn.disabled = false; btn.textContent = 'Confirmar pago de ' + bs(total);
                ayuda.textContent = 'Recibimos tu pago. Confírmalo para activar tu plan.';
            }, 6000);
        } else {
            btn.disabled = false; btn.textContent = 'Pagar ' + bs(total);
            ayuda.textContent = 'Completa los datos de tu tarjeta.';
        }
    }

    // ---- Tarjeta (demostrativa): formato y vista previa
    const tn = document.getElementById('t-numero'), tv = document.getElementById('t-vence'), tc = document.getElementById('t-cvv'), tnom = document.getElementById('t-nombre');
    tn.addEventListener('input', () => { tn.value = tn.value.replace(/\D/g, '').slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 '); document.getElementById('tv-numero').textContent = (tn.value + ' •••• •••• •••• ••••').slice(0, 19); });
    tv.addEventListener('input', () => { let v = tv.value.replace(/\D/g, '').slice(0, 4); if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2); tv.value = v; document.getElementById('tv-vence').textContent = v || 'MM/AA'; });
    tc.addEventListener('input', () => { tc.value = tc.value.replace(/\D/g, '').slice(0, 4); });
    tnom.addEventListener('input', () => { document.getElementById('tv-nombre').textContent = tnom.value || ' '; });
    [tn, tv, tc, tnom].forEach((i) => i.addEventListener('input', () => { document.getElementById('t-error').hidden = true; }));

    form.addEventListener('submit', (e) => {
        if (metodo() === 'QR') { if (!qrListo) e.preventDefault(); return; }
        e.preventDefault();
        const err = document.getElementById('t-error');
        const mm = +tv.value.slice(0, 2);
        const malo = tn.value.replace(/\s/g, '').length !== 16 ? 'El número de tarjeta debe tener 16 dígitos.'
            : (!/^\d{2}\/\d{2}$/.test(tv.value) || mm < 1 || mm > 12) ? 'Escribe el vencimiento como MM/AA.'
            : tc.value.length < 3 ? 'El CVV debe tener 3 o 4 dígitos.'
            : tnom.value.trim().length < 3 ? 'Escribe el nombre que figura en la tarjeta.' : '';
        err.hidden = !malo; err.textContent = malo;
        if (malo) return;
        btn.disabled = true; btn.textContent = 'Procesando el pago…';
        setTimeout(() => form.submit(), 1500);
    });

    document.querySelectorAll('input[name=plan_id]').forEach((r) => r.addEventListener('change', llenar));
    document.querySelectorAll('input[name=metodo_pago]').forEach((r) => r.addEventListener('change', cambiarMetodo));
    sel.addEventListener('change', resumen);
    llenar();
})();
</script>
@endpush
