@extends('layouts.app')

@section('title', 'Cobrar al grupo')
@section('kicker', 'Suscripciones')
@section('heading', 'Cobrar al grupo')

@section('content')
<div class="stack">
    <form class="panel" method="GET" action="{{ route('suscripciones.grupal') }}">
        <div class="panel-head inline-form">
            <div class="field" style="flex:3">
                <label for="grupo">Pareja o familia</label>
                <select id="grupo" name="grupo" onchange="this.form.submit()" required>
                    <option value="">Selecciona un grupo…</option>
                    @foreach ($grupos as $g)
                        <option value="{{ $g->id }}" @selected($grupo?->id === $g->id)>{{ $g->nombre }} · {{ $g->tipoTexto() }} ({{ $g->clientes_count }} integrantes)</option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="button button-secondary" type="submit">Ver planes</button></noscript>
            <a class="button button-secondary" href="{{ route('grupos.create') }}">＋ Nuevo grupo</a>
        </div>
    </form>

    @if (! $grupo)
        <div class="panel panel-body muted">Elige una pareja o familia para cobrarles el mismo plan en un solo pago con su promoción de grupo.</div>
    @elseif ($grupo->clientes->count() < 2)
        <div class="panel panel-body">El grupo <strong>{{ $grupo->nombre }}</strong> tiene un solo integrante. Agrega al menos otra persona en <a class="teal" href="{{ route('grupos.edit', $grupo) }}">Parejas y familias</a> para cobrar en grupo.</div>
    @else
        <form class="panel" method="POST" action="{{ route('suscripciones.grupal.store') }}">
            @csrf
            <input type="hidden" name="grupo_id" value="{{ $grupo->id }}">
            <div class="panel-head">
                <div>
                    <h2>{{ $grupo->nombre }}</h2>
                    <p><span class="badge {{ $grupo->tipo === 'pareja' ? 'info' : 'teal' }} plain">{{ $grupo->tipoTexto() }}</span> · {{ $grupo->clientes->count() }} integrantes · cada uno recibe su suscripción y sus puntos</p>
                </div>
            </div>
            <div class="panel-body stack">
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Integrante</th><th>CI</th><th>Estado</th><th>Su membresía empieza</th></tr></thead>
                        <tbody>
                        @foreach ($grupo->clientes as $c)
                            <tr>
                                <td>{{ $c->nombre_completo }}</td>
                                <td class="num">{{ $c->ci }}</td>
                                <td><span class="badge {{ $c->claseEstado() }}">{{ $c->estado() }}</span></td>
                                <td class="num">{{ $inicios[$c->id]->isToday() ? 'Hoy' : $inicios[$c->id]->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="muted" style="margin:0">Las promociones de pareja y familiar solo se aplican aquí, al cobrar al grupo completo. Se aplica una sola promoción, igual para todos.</p>
                <div class="plan-options">
                    @forelse ($opciones as $o)
                        <label class="plan-option">
                            <input type="radio" name="plan_id" value="{{ $o['plan']->id }}" data-plan="{{ $o['plan']->nombre }}" data-n="{{ $o['n'] }}" data-precio="{{ $o['precio_base'] }}"
                                   data-descuentos="{{ $o['opciones']->map(fn ($d) => ['clave' => $d['clave'], 'motivo' => $d['motivo'], 'porcentaje' => $d['porcentaje'], 'total' => $d['total']])->values()->toJson() }}"
                                   @checked((int) old('plan_id') === $o['plan']->id || ($loop->first && ! old('plan_id'))) required>
                            <h3>{{ $o['plan']->nombre }}</h3>
                            <span class="meta">{{ $o['plan']->duracion_dias }} días · {{ $o['n'] }} personas</span>
                            <div>
                                @if ($o['porcentaje'] > 0)<span class="old">@bs($o['precio_grupo'])</span>@endif
                                <div class="price num">@bs($o['total'] * $o['n'])</div>
                                <span class="meta">@bs($o['total']) por persona</span>
                            </div>
                            @if ($o['motivo'])
                                <span class="badge ok plain">−@pct($o['porcentaje']) · {{ $o['motivo'] }}</span>
                            @else
                                <span class="badge neutral plain">Sin promoción de grupo vigente</span>
                            @endif
                            <span class="meta teal">Cada uno gana {{ number_format($o['plan']->puntos, 0, ',', '.') }} puntos</span>
                        </label>
                    @empty
                        <p class="muted">No hay planes activos. El administrador debe crear al menos uno.</p>
                    @endforelse
                </div>
                <x-error field="plan_id" />
                <x-error field="grupo_id" />

                <div class="inline-form descuento-fila">
                    <div class="field" style="max-width:420px;flex:2">
                        <label for="descuento">Promoción de grupo a aplicar</label>
                        <select id="descuento" name="descuento"><option value="">La mayor disponible</option></select>
                    </div>
                    <div class="total-cobrar">
                        <span class="muted">Total a cobrar al grupo</span>
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
                    </div>
                </div>

                <div id="pago-qr" class="qr-pago" hidden>
                    <div class="qr-marco"><svg id="qr-svg" viewBox="0 0 29 29" shape-rendering="crispEdges" aria-label="Código QR de pago"></svg></div>
                    <div class="qr-info">
                        <span class="qr-kicker">Pago con QR · Gimnasio Titán</span>
                        <strong class="qr-monto">Bs <span id="qr-monto">0,00</span></strong>
                        <span class="muted">Plan <span id="qr-plan">—</span> · {{ $grupo->nombre }}</span>
                        <span class="muted">Escanea el código con la app de tu banco.</span>
                        <span id="qr-estado" class="qr-estado esperando"><i></i> Esperando el pago…</span>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="padding-top:0">
                <div class="form-actions" style="margin-top:0">
                    <a class="button button-secondary" href="{{ route('grupos.index') }}">Cancelar</a>
                    <button class="button button-primary" type="submit" @disabled($opciones->isEmpty())>Registrar pago del grupo</button>
                </div>
            </div>
        </form>
        <style>
            .descuento-fila{align-items:flex-end}
            .total-cobrar{display:flex;flex-direction:column;gap:2px;padding:10px 16px;border-radius:12px;background:rgba(20,184,166,.08);min-width:220px}
            .total-cobrar strong{font-size:24px;line-height:1.1}
            .qr-pago{display:flex;gap:22px;align-items:center;flex-wrap:wrap;padding:18px;border:1px solid var(--line, #2a3a3a);border-radius:14px;background:rgba(20,184,166,.06)}
            .qr-pago[hidden]{display:none}
            .qr-marco{background:#fff;padding:12px;border-radius:12px;width:190px;height:190px;flex:none}
            .qr-marco svg{width:100%;height:100%;display:block}
            .qr-info{display:flex;flex-direction:column;gap:6px;min-width:220px}
            .qr-kicker{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#14b8a6;font-weight:700}
            .qr-monto{font-size:30px;line-height:1.1}
            .qr-estado{display:inline-flex;align-items:center;gap:8px;margin-top:6px;font-weight:600}
            .qr-estado i{width:10px;height:10px;border-radius:50%;background:#f59e0b;animation:qrlatido 1s infinite}
            .qr-estado.recibido{color:#22c55e}.qr-estado.recibido i{background:#22c55e;animation:none}
            @keyframes qrlatido{50%{opacity:.25}}
        </style>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('descuento');
    const metodo = document.getElementById('metodo_pago');
    const caja = document.getElementById('pago-qr');
    if (!sel || !metodo) return;
    const bs = (n) => Number(n).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pct = (n) => String(+Number(n).toFixed(2)).replace('.', ',') + '%';
    const plan = () => document.querySelector('input[name=plan_id]:checked');
    let monto = '0,00', espera = null;

    function llenar() {
        const p = plan(); if (!p) return;
        const previo = sel.value, n = +p.dataset.n;
        const lista = JSON.parse(p.dataset.descuentos || '[]');
        sel.innerHTML = '';
        lista.forEach((d, i) => {
            const o = new Option(d.motivo + ' · −' + pct(d.porcentaje) + ' → Bs ' + bs(d.total * n) + (i === 0 ? ' (la mayor)' : ''), d.clave);
            o.dataset.total = d.total * n; o.dataset.persona = d.total; o.dataset.motivo = d.motivo; o.dataset.pct = d.porcentaje;
            sel.add(o);
        });
        const sin = new Option((lista.length ? 'Sin promoción' : 'No hay promoción de grupo vigente') + ' → Bs ' + bs(p.dataset.precio * n), 'ninguno');
        sin.dataset.total = p.dataset.precio * n; sin.dataset.persona = p.dataset.precio; sin.dataset.motivo = ''; sin.dataset.pct = 0;
        sel.add(sin);
        if ([...sel.options].some((o) => o.value === previo)) sel.value = previo;
        total();
    }

    function total() {
        const p = plan(), o = sel.selectedOptions[0]; if (!p || !o) return;
        monto = bs(o.dataset.total);
        document.getElementById('total-cobrar').textContent = 'Bs ' + monto;
        document.getElementById('detalle-cobrar').textContent = p.dataset.n + ' × Bs ' + bs(o.dataset.persona) + (o.dataset.motivo ? ' · ' + o.dataset.motivo + ' (−' + pct(o.dataset.pct) + ')' : ' · sin promoción');
        qr();
    }

    function dibujar(semilla) {
        let h = 2166136261;
        for (const c of semilla) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); }
        const azar = () => { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return (h >>> 0) / 4294967296; };
        const N = 29;
        const reservado = (x, y) => (x < 8 && y < 8) || (x > N - 9 && y < 8) || (x < 8 && y > N - 9) || (x > 19 && x < 25 && y > 19 && y < 25);
        let d = '';
        for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) if (azar() < 0.5 && !reservado(x, y)) d += 'M' + x + ' ' + y + 'h1v1h-1z';
        const ojo = (x, y) => 'M' + x + ' ' + y + 'h7v7h-7zM' + (x + 1) + ' ' + (y + 1) + 'v5h5v-5zM' + (x + 2) + ' ' + (y + 2) + 'h3v3h-3z';
        d += ojo(0, 0) + ojo(N - 7, 0) + ojo(0, N - 7) + 'M20 20h5v5h-5zM21 21v3h3v-3zM22 22h1v1h-1z';
        document.getElementById('qr-svg').innerHTML = '<rect width="29" height="29" fill="#fff"/><path fill="#111" fill-rule="evenodd" d="' + d + '"/>';
    }

    function qr() {
        clearTimeout(espera);
        if (metodo.value !== 'QR') { caja.hidden = true; return; }
        const p = plan();
        document.getElementById('qr-monto').textContent = monto;
        document.getElementById('qr-plan').textContent = p ? p.dataset.plan : '—';
        dibujar(monto + Date.now());
        caja.hidden = false;
        const est = document.getElementById('qr-estado');
        est.className = 'qr-estado esperando'; est.innerHTML = '<i></i> Esperando el pago…';
        espera = setTimeout(() => { est.className = 'qr-estado recibido'; est.innerHTML = '<i></i> Pago recibido ✓ Ya puedes registrar el pago.'; }, 6000);
    }

    document.querySelectorAll('input[name=plan_id]').forEach((r) => r.addEventListener('change', llenar));
    sel.addEventListener('change', total);
    metodo.addEventListener('change', qr);
    llenar();
})();
</script>
@endpush
