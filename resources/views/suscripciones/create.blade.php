@extends('layouts.app')

@section('title', 'Cobrar plan')
@section('kicker', 'Suscripciones')
@section('heading', 'Cobrar plan')

@section('content')
<div class="stack">
    <form class="panel" method="GET" action="{{ route('suscripciones.create') }}">
        <div class="panel-head inline-form">
            <div class="field" style="flex:3">
                <label for="cliente">Cliente</label>
                <select id="cliente" name="cliente" onchange="this.form.submit()" required>
                    <option value="">Selecciona un cliente…</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id }}" @selected($cliente?->id === $c->id)>{{ $c->apellido }}, {{ $c->nombre }} · CI {{ $c->ci }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="button button-secondary" type="submit">Ver planes</button></noscript>
            <a class="button button-secondary" href="{{ route('clientes.create') }}">＋ Cliente nuevo</a>
        </div>
    </form>

    @if ($cliente)
        <form class="panel" method="POST" action="{{ route('suscripciones.store') }}">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            <div class="panel-head">
                <div>
                    <h2>{{ $cliente->nombre_completo }}</h2>
                    <p>
                        <span class="badge {{ $cliente->claseEstado() }}">{{ $cliente->estado() }}</span>
                        @modulo('niveles') Nivel {{ $nivel?->nombre ?? '—' }} · @endmodulo{{ number_format($cliente->puntos, 0, ',', '.') }} puntos
                        @if ($cliente->grupo && \App\Support\Sprint::activo('grupos')) · Grupo {{ mb_strtolower($cliente->grupo->tipoTexto()) }}: {{ $cliente->grupo->nombre }} ({{ $cliente->grupo->clientes->count() }} personas) @endif
                    </p>
                </div>
                <div class="note">
                    @if ($inicio->isToday())
                        La membresía empieza <strong>hoy</strong>.
                    @else
                        Aún tiene días pagados: la renovación empieza el <strong>{{ $inicio->format('d/m/Y') }}</strong>.
                    @endif
                </div>
            </div>
            <div class="panel-body stack">
                <p class="muted" style="margin:0">El sistema propone el mayor descuento al que el cliente tiene derecho; puedes elegir otra promoción abajo. Los descuentos no se acumulan.@if ($cliente->grupo && \App\Support\Sprint::activo('grupos')) Para la promoción de {{ mb_strtolower($cliente->grupo->tipoTexto()) }}, <a class="teal" href="{{ route('suscripciones.grupal', ['grupo' => $cliente->grupo_id]) }}">cobra al grupo completo</a>.@endif</p>
                <div class="plan-options">
                    @forelse ($opciones as $o)
                        <label class="plan-option">
                            <input type="radio" name="plan_id" value="{{ $o['plan']->id }}" data-total="{{ number_format($o['total'], 2, ',', '.') }}" data-plan="{{ $o['plan']->nombre }}" data-precio="{{ $o['precio_base'] }}" data-descuentos="{{ $o['opciones']->map(fn ($d) => ['clave' => $d['clave'], 'motivo' => $d['motivo'], 'porcentaje' => $d['porcentaje'], 'total' => $d['total']])->values()->toJson() }}" @checked((int) old('plan_id') === $o['plan']->id || ($loop->first && ! old('plan_id'))) required>
                            <h3>{{ $o['plan']->nombre }}</h3>
                            <span class="meta">{{ $o['plan']->duracion_dias }} días · hasta el {{ $inicio->copy()->addDays($o['plan']->duracion_dias - 1)->format('d/m/Y') }}</span>
                            <div>
                                @if ($o['porcentaje'] > 0)<span class="old">@bs($o['precio_base'])</span>@endif
                                <div class="price num">@bs($o['total'])</div>
                            </div>
                            @if ($o['motivo'])
                                <span class="badge ok plain">−@pct($o['porcentaje']) · {{ $o['motivo'] }}</span>
                            @else
                                <span class="badge neutral plain">Sin descuento</span>
                            @endif
                            @if ($o['otros']->isNotEmpty())
                                <span class="meta">También califica: {{ $o['otros']->map(fn ($x) => $x['motivo'].' ('.rtrim(rtrim(number_format($x['porcentaje'], 2), '0'), '.').'%)')->join(', ') }}</span>
                            @endif
                            <span class="meta teal">Gana {{ number_format($o['plan']->puntos, 0, ',', '.') }} puntos</span>
                        </label>
                    @empty
                        <p class="muted">No hay planes activos. El administrador debe crear al menos uno.</p>
                    @endforelse
                </div>
                <x-error field="plan_id" />

                <div class="inline-form descuento-fila">
                    <div class="field" style="max-width:420px;flex:2">
                        <label for="descuento">Promoción o descuento a aplicar</label>
                        <select id="descuento" name="descuento">
                            <option value="">El mayor descuento disponible</option>
                        </select>
                        <x-error field="descuento" />
                    </div>
                    <div class="total-cobrar">
                        <span class="muted">Total a cobrar</span>
                        <strong id="total-cobrar">—</strong>
                        <span id="detalle-cobrar" class="muted"></span>
                    </div>
                </div>

                <div class="inline-form">
                    <div class="field" style="max-width:280px">
                        <label for="metodo_pago">Método de pago</label>
                        <select id="metodo_pago" name="metodo_pago" required>
                            @foreach (\App\Models\Suscripcion::METODOS as $m)
                                <option @selected(old('metodo_pago') === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                        <x-error field="metodo_pago" />
                    </div>
                    @if ($cliente->suscripciones->isEmpty() && $cliente->referidoPor)
                        <div class="note">Es su primera suscripción: {{ $cliente->referidoPor->nombre_completo }} recibirá {{ \App\Models\Configuracion::entero('puntos_referido') }} puntos por recomendarlo.</div>
                    @endif
                </div>

                {{-- Pasarela QR (demostrativa): aparece al elegir el método QR --}}
                <div id="pago-qr" class="qr-pago" hidden>
                    <div class="qr-marco"><svg id="qr-svg" viewBox="0 0 29 29" shape-rendering="crispEdges" aria-label="Código QR de pago"></svg></div>
                    <div class="qr-info">
                        <span class="qr-kicker">Pago con QR · Gimnasio Titán</span>
                        <strong class="qr-monto">Bs <span id="qr-monto">0,00</span></strong>
                        <span class="muted">Plan <span id="qr-plan">—</span> · {{ $cliente->nombre_completo }}</span>
                        <span class="muted">Escanea el código con la app de tu banco.</span>
                        <span id="qr-estado" class="qr-estado esperando"><i></i> Esperando el pago… <span id="qr-tiempo">05:00</span></span>
                    </div>
                </div>
            </div>
            <style>
                .descuento-fila{align-items:flex-end}
                .total-cobrar{display:flex;flex-direction:column;gap:2px;padding:10px 16px;border-radius:12px;background:rgba(20,184,166,.08);min-width:220px}
                .total-cobrar strong{font-size:24px;line-height:1.1}
                .qr-pago{display:flex;gap:22px;align-items:center;flex-wrap:wrap;padding:18px;border:1px solid var(--line, #2a3a3a);border-radius:14px;background:rgba(20,184,166,.06)}
                .qr-pago[hidden]{display:none}
                .qr-marco{background:#fff;padding:12px;border-radius:12px;width:190px;height:190px;flex:none;box-shadow:0 6px 24px rgba(0,0,0,.25)}
                .qr-marco svg{width:100%;height:100%;display:block}
                .qr-info{display:flex;flex-direction:column;gap:6px;min-width:220px}
                .qr-kicker{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#14b8a6;font-weight:700}
                .qr-monto{font-size:30px;line-height:1.1}
                .qr-estado{display:inline-flex;align-items:center;gap:8px;margin-top:6px;font-weight:600}
                .qr-estado i{width:10px;height:10px;border-radius:50%;background:#f59e0b;animation:qrlatido 1s infinite}
                .qr-estado.recibido{color:#22c55e}.qr-estado.recibido i{background:#22c55e;animation:none}
                @keyframes qrlatido{50%{opacity:.25}}
            </style>
            <div class="panel-body" style="padding-top:0">
                <div class="form-actions" style="margin-top:0">
                    <a class="button button-secondary" href="{{ route('clientes.show', $cliente) }}">Cancelar</a>
                    <button class="button button-primary" type="submit" @disabled($opciones->isEmpty())>Registrar pago</button>
                </div>
            </div>
        </form>
    @else
        <div class="panel panel-body muted">Elige un cliente para ver los planes con sus descuentos.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const metodo = document.getElementById('metodo_pago');
    const caja = document.getElementById('pago-qr');
    if (!metodo || !caja) return;
    const svg = document.getElementById('qr-svg');
    const estado = document.getElementById('qr-estado');
    const tiempo = document.getElementById('qr-tiempo');
    let reloj = null, confirmar = null;
    const selDesc = document.getElementById('descuento');
    const bs = (n) => Number(n).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pct = (n) => String(+Number(n).toFixed(2)).replace('.', ',') + '%';
    let montoActual = '0,00';

    // Llena "Promoción o descuento a aplicar" con las opciones del plan elegido.
    function llenarDescuentos() {
        const plan = document.querySelector('input[name=plan_id]:checked');
        if (!plan || !selDesc) return;
        const previo = selDesc.value;
        const lista = JSON.parse(plan.dataset.descuentos || '[]');
        selDesc.innerHTML = '';
        lista.forEach((d, i) => {
            const o = new Option(d.motivo + ' · −' + pct(d.porcentaje) + ' → Bs ' + bs(d.total) + (i === 0 ? ' (el mayor)' : ''), d.clave);
            o.dataset.total = d.total; o.dataset.motivo = d.motivo; o.dataset.pct = d.porcentaje;
            selDesc.add(o);
        });
        const sin = new Option(lista.length ? 'Sin descuento → Bs ' + bs(plan.dataset.precio) : 'No tiene descuentos disponibles → Bs ' + bs(plan.dataset.precio), 'ninguno');
        sin.dataset.total = plan.dataset.precio; sin.dataset.motivo = ''; sin.dataset.pct = 0;
        selDesc.add(sin);
        if ([...selDesc.options].some((o) => o.value === previo)) selDesc.value = previo;
        mostrarTotal();
    }

    function mostrarTotal() {
        const plan = document.querySelector('input[name=plan_id]:checked');
        const op = selDesc && selDesc.selectedOptions[0];
        if (!plan || !op) return;
        montoActual = bs(op.dataset.total);
        document.getElementById('total-cobrar').textContent = 'Bs ' + montoActual;
        document.getElementById('detalle-cobrar').textContent = 'Plan ' + plan.dataset.plan + (op.dataset.motivo ? ' · ' + op.dataset.motivo + ' (−' + pct(op.dataset.pct) + ')' : ' · sin descuento');
    }

    // Dibuja un código con aspecto de QR (solo visual) a partir del plan y el monto.
    function dibujar(semilla) {
        let h = 2166136261;
        for (const c of semilla) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); }
        const azar = () => { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return (h >>> 0) / 4294967296; };
        const N = 29, m = [];
        for (let y = 0; y < N; y++) { m.push([]); for (let x = 0; x < N; x++) m[y].push(azar() < 0.5); }
        const reservado = (x, y) => (x < 8 && y < 8) || (x > N - 9 && y < 8) || (x < 8 && y > N - 9) || (x > 19 && x < 25 && y > 19 && y < 25);
        let d = '';
        for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) if (m[y][x] && !reservado(x, y)) d += 'M' + x + ' ' + y + 'h1v1h-1z';
        const ojo = (x, y) => 'M' + x + ' ' + y + 'h7v7h-7zM' + (x + 1) + ' ' + (y + 1) + 'v5h5v-5zM' + (x + 2) + ' ' + (y + 2) + 'h3v3h-3z';
        d += ojo(0, 0) + ojo(N - 7, 0) + ojo(0, N - 7) + 'M20 20h5v5h-5zM21 21v3h3v-3zM22 22h1v1h-1z';
        svg.innerHTML = '<rect width="29" height="29" fill="#fff"/><path fill="#111" fill-rule="evenodd" d="' + d + '"/>';
    }

    function actualizar() {
        clearInterval(reloj); clearTimeout(confirmar);
        if (metodo.value !== 'QR') { caja.hidden = true; return; }
        const plan = document.querySelector('input[name=plan_id]:checked');
        document.getElementById('qr-monto').textContent = montoActual;
        document.getElementById('qr-plan').textContent = plan ? plan.dataset.plan : '—';
        dibujar((plan ? plan.value + montoActual : '') + Date.now());
        caja.hidden = false;
        estado.className = 'qr-estado esperando';
        estado.innerHTML = '<i></i> Esperando el pago… <span id="qr-tiempo">05:00</span>';
        let resta = 300;
        reloj = setInterval(() => {
            resta--;
            const t = document.getElementById('qr-tiempo');
            if (t) t.textContent = String(Math.floor(resta / 60)).padStart(2, '0') + ':' + String(resta % 60).padStart(2, '0');
            if (resta <= 0) clearInterval(reloj);
        }, 1000);
        // Demostración: a los pocos segundos el pago aparece como recibido.
        confirmar = setTimeout(() => {
            clearInterval(reloj);
            estado.className = 'qr-estado recibido';
            estado.innerHTML = '<i></i> Pago recibido ✓ Ya puedes registrar el pago.';
        }, 6000);
    }

    metodo.addEventListener('change', actualizar);
    document.querySelectorAll('input[name=plan_id]').forEach((r) => r.addEventListener('change', () => { llenarDescuentos(); actualizar(); }));
    if (selDesc) selDesc.addEventListener('change', () => { mostrarTotal(); actualizar(); });
    llenarDescuentos();
    const anterior = {{ \Illuminate\Support\Js::from(old('descuento')) }};
    if (anterior && selDesc) { selDesc.value = anterior; mostrarTotal(); }
    actualizar();
})();
</script>
@endpush
