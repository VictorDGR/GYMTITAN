<?php

namespace App\Http\Controllers;

use App\Models\Canje;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Grupo;
use App\Models\MovimientoPunto;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Premio;
use App\Models\Promocion;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Reportes del administrador: una salida (S) del diagrama de entradas y salidas por cada reporte.
 * Cada reporte arma un resumen y una o más tablas que se muestran con la misma vista.
 */
class ReporteController extends Controller
{
    /** clave => [título, salida del diagrama, ícono] */
    public const REPORTES = [
        'socios' => ['Reporte de socios', 'General · por estado', '♙'],
        'planes' => ['Reporte de planes', 'Suscripciones por plan', '◫'],
        'vencimientos' => ['Reporte de vencimientos', 'Por vencer · vencidos', '⏱'],
        'ingresos' => ['Reporte de ingresos', 'Por mes · por método', '$'],
        'descuentos' => ['Reporte de descuentos', 'Por promoción · por periodo', '%'],
        'puntos' => ['Historial de puntos', 'Por socio', '✪'],
        'premios' => ['Catálogo de premios', 'Disponibles · stock', '✦'],
        'canjes' => ['Reporte de canjes', 'Premios más canjeados', '★'],
        'niveles' => ['Reporte por nivel', 'Socios por nivel', '▲'],
        'grupos' => ['Reporte de grupos', 'Parejas · familias', '⚇'],
        'usuarios' => ['Reporte de usuarios', 'Por rol', '☏'],
    ];

    public function index(): View
    {
        return view('reportes.index', ['reportes' => self::REPORTES]);
    }

    public function show(Request $request, string $tipo): View
    {
        abort_unless(array_key_exists($tipo, self::REPORTES), 404);

        $desde = $request->date('desde') ?? today()->startOfMonth()->subMonths(2);
        $hasta = $request->date('hasta') ?? today();
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $datos = $this->{$tipo}($request, $desde, $hasta) + ['periodo' => false, 'filtros' => [], 'resumen' => [], 'tablas' => []];

        return view('reportes.show', $datos + [
            'tipo' => $tipo,
            'titulo' => self::REPORTES[$tipo][0],
            'subtitulo' => self::REPORTES[$tipo][1],
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    // ---------------------------------------------------------------- reportes

    private function socios(Request $request): array
    {
        $estados = ['Activo', 'Por vencer', 'Vencido', 'Sin suscripción'];
        $estado = in_array($request->query('estado'), $estados, true) ? $request->query('estado') : null;
        $clientes = Cliente::with('suscripciones')->orderBy('apellido')->orderBy('nombre')->get();
        $lista = $estado ? $clientes->filter(fn ($c) => $c->estado() === $estado) : $clientes;

        return [
            'filtros' => [['estado', 'Estado', array_combine($estados, $estados), $estado]],
            'resumen' => collect($estados)->map(fn ($e) => [$e, $clientes->filter(fn ($c) => $c->estado() === $e)->count()])
                ->prepend(['Total de socios', $clientes->count()])->all(),
            'tablas' => [[
                'titulo' => $estado ? 'Socios en estado: '.mb_strtolower($estado) : 'Listado general de socios',
                'columnas' => ['Socio', 'CI', 'Teléfono', 'Correo', 'Estado', 'Pagado hasta', 'Puntos'],
                'derecha' => [6],
                'filas' => $lista->map(fn ($c) => [
                    [$c->nombre_completo, route('clientes.show', $c)], $c->ci, $c->telefono ?: '—', $c->email,
                    ['badge', $c->estado(), $c->claseEstado()], $c->pagadoHasta()?->format('d/m/Y') ?? '—', $this->n($c->puntos),
                ])->values()->all(),
                'pie' => $lista->count().' socios',
            ]],
        ];
    }

    private function planes(Request $request, Carbon $desde, Carbon $hasta): array
    {
        $subs = $this->suscripciones($desde, $hasta);
        $planes = Plan::orderBy('duracion_dias')->get();

        return [
            'periodo' => true,
            'resumen' => [['Suscripciones vendidas', $subs->count()], ['Ingresos', $this->bs($subs->sum('total'))], ['Planes activos', $planes->where('activo', true)->count()]],
            'tablas' => [[
                'titulo' => 'Suscripciones por plan',
                'columnas' => ['Plan', 'Duración', 'Precio', 'Estado', 'Suscripciones', '% del total', 'Ingresos'],
                'derecha' => [2, 4, 5, 6],
                'filas' => $planes->map(function ($p) use ($subs) {
                    $de = $subs->where('plan_id', $p->id);

                    return [$p->nombre, $p->duracion_dias.' días', $this->bs($p->precio), ['badge', $p->activo ? 'Activo' : 'Inactivo', $p->activo ? 'ok' : 'neutral'],
                        $de->count(), $this->pct($subs->count() ? $de->count() / $subs->count() * 100 : 0), $this->bs($de->sum('total'))];
                })->all(),
                'pie' => 'Total: '.$subs->count().' suscripciones · '.$this->bs($subs->sum('total')),
            ]],
        ];
    }

    private function vencimientos(): array
    {
        $aviso = Configuracion::entero('dias_aviso_vencimiento');
        $clientes = Cliente::with('suscripciones.plan')->get();
        $porVencer = $clientes->filter(fn ($c) => $c->estado() === 'Por vencer')->sortBy(fn ($c) => $c->diasRestantes());
        $vencidos = $clientes->filter(fn ($c) => $c->estado() === 'Vencido')->sortByDesc(fn ($c) => $c->pagadoHasta());
        $plan = fn ($c) => $c->suscripciones->sortByDesc('fecha_fin')->first()?->plan->nombre ?? '—';
        $fila = fn ($c, $dato) => [[$c->nombre_completo, route('clientes.show', $c)], $c->ci, $c->telefono ?: '—', $plan($c), $c->pagadoHasta()?->format('d/m/Y') ?? '—', $dato];

        return [
            'resumen' => [
                ['Por vencer (próximos '.$aviso.' días)', $porVencer->count()],
                ['Vencidos', $vencidos->count()],
                ['Vencidos en los últimos 30 días', $vencidos->filter(fn ($c) => $c->pagadoHasta()->gte(today()->subDays(30)))->count()],
            ],
            'tablas' => [
                ['titulo' => 'Socios por vencer', 'columnas' => ['Socio', 'CI', 'Teléfono', 'Plan', 'Vence', 'Días restantes'], 'derecha' => [5],
                    'filas' => $porVencer->map(fn ($c) => $fila($c, $c->diasRestantes()))->values()->all(), 'vacio' => 'Ningún socio está por vencer.'],
                ['titulo' => 'Socios vencidos', 'columnas' => ['Socio', 'CI', 'Teléfono', 'Último plan', 'Venció', 'Días vencido'], 'derecha' => [5],
                    'filas' => $vencidos->map(fn ($c) => $fila($c, (int) $c->pagadoHasta()->diffInDays(today())))->values()->all(), 'vacio' => 'No hay socios vencidos.'],
            ],
        ];
    }

    private function ingresos(Request $request, Carbon $desde, Carbon $hasta): array
    {
        $subs = $this->suscripciones($desde, $hasta);
        $total = (float) $subs->sum('total');

        return [
            'periodo' => true,
            'resumen' => [['Ingresos del periodo', $this->bs($total)], ['Pagos', $subs->count()], ['Pago promedio', $this->bs($subs->count() ? $total / $subs->count() : 0)]],
            'tablas' => [
                ['titulo' => 'Ingresos por mes', 'columnas' => ['Mes', 'Pagos', 'Precio de lista', 'Descuentos', 'Ingresos'], 'derecha' => [1, 2, 3, 4],
                    'filas' => $subs->groupBy(fn ($s) => $s->created_at->format('Y-m'))->sortKeys()
                        ->map(fn ($g, $mes) => [$this->mes($mes), $g->count(), $this->bs($g->sum('precio_base')), ($g->sum('precio_base') - $g->sum('total')) > 0 ? '−'.$this->bs($g->sum('precio_base') - $g->sum('total')) : '—', $this->bs($g->sum('total'))])->values()->all(),
                    'pie' => 'Total: '.$this->bs($total), 'vacio' => 'No hubo pagos en este periodo.'],
                ['titulo' => 'Ingresos por método de pago', 'columnas' => ['Método', 'Pagos', '% de los ingresos', 'Ingresos'], 'derecha' => [1, 2, 3],
                    'filas' => collect(Suscripcion::METODOS)->map(fn ($m) => [$m, $subs->where('metodo_pago', $m)->count(), $this->pct($total ? $subs->where('metodo_pago', $m)->sum('total') / $total * 100 : 0), $this->bs($subs->where('metodo_pago', $m)->sum('total'))])->all()],
                ['titulo' => 'Detalle de pagos (abre el comprobante)', 'columnas' => ['N.º', 'Fecha', 'Socio', 'Plan', 'Método', 'Total'], 'derecha' => [5],
                    'filas' => $subs->sortByDesc('created_at')->map(fn ($s) => [['#'.str_pad($s->id, 5, '0', STR_PAD_LEFT), route('pagos.show', $s->id)], $s->created_at->format('d/m/Y'), $s->cliente->nombre_completo, $s->plan->nombre, $s->metodo_pago, $this->bs($s->total)])->values()->all(),
                    'vacio' => 'Sin pagos.'],
            ],
        ];
    }

    private function descuentos(Request $request, Carbon $desde, Carbon $hasta): array
    {
        $subs = $this->suscripciones($desde, $hasta)->filter(fn ($s) => $s->descuento_porcentaje > 0);
        $ahorro = fn ($g) => $g->sum(fn ($s) => $s->precio_base - $s->total);

        return [
            'periodo' => true,
            'resumen' => [['Descuentos otorgados', $this->bs($ahorro($subs))], ['Pagos con descuento', $subs->count()], ['Promociones vigentes hoy', Promocion::vigentes()->count()]],
            'tablas' => [
                ['titulo' => 'Descuentos por promoción', 'columnas' => ['Promoción o motivo', 'Veces aplicada', 'Monto descontado'], 'derecha' => [1, 2],
                    'filas' => $subs->groupBy('descuento_motivo')->map(fn ($g, $m) => [$m, $g->count(), $this->bs($ahorro($g))])->sortByDesc(fn ($f) => $f[1])->values()->all(),
                    'vacio' => 'No se aplicaron descuentos en este periodo.'],
                ['titulo' => 'Descuentos por periodo (mes)', 'columnas' => ['Mes', 'Pagos con descuento', 'Monto descontado'], 'derecha' => [1, 2],
                    'filas' => $subs->groupBy(fn ($s) => $s->created_at->format('Y-m'))->sortKeys()->map(fn ($g, $mes) => [$this->mes($mes), $g->count(), $this->bs($ahorro($g))])->values()->all(),
                    'vacio' => 'Sin datos.'],
            ],
        ];
    }

    private function puntos(Request $request, Carbon $desde, Carbon $hasta): array
    {
        $socios = Cliente::orderBy('apellido')->orderBy('nombre')->get();
        $socio = $request->filled('socio') ? $socios->firstWhere('id', $request->integer('socio')) : null;
        $movs = MovimientoPunto::with('cliente')->whereDate('created_at', '>=', $desde)->whereDate('created_at', '<=', $hasta)
            ->when($socio, fn ($q) => $q->where('cliente_id', $socio->id))->latest()->latest('id')->get();

        $tablas = [];
        if (! $socio) {
            $tablas[] = ['titulo' => 'Saldo de puntos por socio (toca un nombre para ver su historial)', 'columnas' => ['Socio', 'CI', 'Ganados en el periodo', 'Usados en el periodo', 'Saldo actual'], 'derecha' => [2, 3, 4],
                'filas' => $socios->sortByDesc('puntos')->map(fn ($c) => [
                    [$c->nombre_completo, route('reportes.show', ['puntos', 'socio' => $c->id, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()])], $c->ci,
                    '+'.$this->n($movs->where('cliente_id', $c->id)->where('puntos', '>', 0)->sum('puntos')),
                    $this->n($movs->where('cliente_id', $c->id)->where('puntos', '<', 0)->sum('puntos')),
                    $this->n($c->puntos),
                ])->values()->all()];
        }

        $tablas[] = [
            'titulo' => $socio ? 'Historial de puntos de '.$socio->nombre_completo.' · saldo actual: '.$this->n($socio->puntos).' puntos' : 'Movimientos del periodo',
            'columnas' => $socio ? ['Fecha', 'Tipo', 'Detalle', 'Puntos'] : ['Fecha', 'Socio', 'Tipo', 'Detalle', 'Puntos'],
            'derecha' => [$socio ? 3 : 4],
            'filas' => $movs->map(function ($m) use ($socio) {
                $pts = ($m->puntos >= 0 ? '+' : '').$this->n($m->puntos);

                return $socio
                    ? [$m->created_at->format('d/m/Y'), $m->tipoTexto(), $m->descripcion, $pts]
                    : [$m->created_at->format('d/m/Y'), $m->cliente->nombre_completo, $m->tipoTexto(), $m->descripcion, $pts];
            })->all(),
            'vacio' => 'Sin movimientos en este periodo.',
        ];

        return [
            'periodo' => true,
            'filtros' => [['socio', 'Socio', $socios->mapWithKeys(fn ($c) => [$c->id => $c->apellido.', '.$c->nombre])->all(), $socio?->id]],
            'resumen' => [
                ['Puntos otorgados en el periodo', '+'.$this->n($movs->where('puntos', '>', 0)->sum('puntos'))],
                ['Puntos usados en el periodo', $this->n($movs->where('puntos', '<', 0)->sum('puntos'))],
                [$socio ? 'Saldo actual del socio' : 'Saldo actual de todos los socios', $this->n($socio ? $socio->puntos : $socios->sum('puntos'))],
            ],
            'tablas' => $tablas,
        ];
    }

    private function premios(): array
    {
        $premios = Premio::withCount('canjes')->orderBy('puntos')->get();

        return [
            'resumen' => [['Premios en catálogo', $premios->count()], ['Disponibles', $premios->filter->disponible()->count()], ['Agotados o inactivos', $premios->reject->disponible()->count()]],
            'tablas' => [['titulo' => 'Catálogo de premios', 'columnas' => ['Premio', 'Descripción', 'Puntos', 'Stock', 'Veces canjeado', 'Estado'], 'derecha' => [2, 3, 4],
                'filas' => $premios->map(fn ($p) => [$p->nombre, $p->descripcion ?: '—', $this->n($p->puntos), $p->stock === null ? 'Sin límite' : $p->stock, $p->canjes_count,
                    ['badge', $p->disponible() ? 'Disponible' : ($p->activo ? 'Agotado' : 'Inactivo'), $p->disponible() ? 'ok' : 'neutral']])->all()]],
        ];
    }

    private function canjes(Request $request, Carbon $desde, Carbon $hasta): array
    {
        $canjes = Canje::with('premio', 'cliente')->whereDate('created_at', '>=', $desde)->whereDate('created_at', '<=', $hasta)->latest()->get();

        return [
            'periodo' => true,
            'resumen' => [['Canjes', $canjes->count()], ['Puntos canjeados', $this->n($canjes->sum('puntos'))], ['Socios que canjearon', $canjes->pluck('cliente_id')->unique()->count()]],
            'tablas' => [
                ['titulo' => 'Premios más canjeados', 'columnas' => ['Puesto', 'Premio', 'Veces canjeado', 'Puntos usados'], 'derecha' => [2, 3],
                    'filas' => $canjes->groupBy('premio_id')->sortByDesc(fn ($g) => $g->count())->values()
                        ->map(fn ($g, $i) => [($i + 1).'.º', $g->first()->premio->nombre, $g->count(), $this->n($g->sum('puntos'))])->all(),
                    'vacio' => 'No hubo canjes en este periodo.'],
                ['titulo' => 'Detalle de canjes', 'columnas' => ['Fecha', 'Socio', 'Premio', 'Puntos'], 'derecha' => [3],
                    'filas' => $canjes->map(fn ($c) => [$c->created_at->format('d/m/Y'), $c->cliente->nombre_completo, $c->premio->nombre, $this->n($c->puntos)])->all(),
                    'vacio' => 'Sin canjes.'],
            ],
        ];
    }

    private function niveles(): array
    {
        $clientes = Cliente::with('suscripciones')->get();
        $niveles = Nivel::orderBy('meses_minimos')->get();
        $cuantos = fn ($n) => $clientes->filter(fn ($c) => $c->nivel()?->id === $n->id)->count();

        return [
            'resumen' => $niveles->map(fn ($n) => ['Nivel '.$n->nombre, $cuantos($n)])->all(),
            'tablas' => [
                ['titulo' => 'Socios por nivel', 'columnas' => ['Nivel', 'Desde', 'Descuento', 'Bono', 'Socios', '% de socios'], 'derecha' => [4, 5],
                    'filas' => $niveles->map(fn ($n) => [$n->nombre, $n->meses_minimos.' meses', $this->pct($n->descuento_porcentaje), $this->n($n->bono_puntos).' pts', $cuantos($n), $this->pct($clientes->count() ? $cuantos($n) / $clientes->count() * 100 : 0)])->all()],
                ['titulo' => 'Antigüedad de cada socio', 'columnas' => ['Socio', 'Meses de antigüedad', 'Nivel', 'Siguiente nivel'], 'derecha' => [1],
                    'filas' => $clientes->sortByDesc(fn ($c) => $c->mesesAntiguedad())->map(function ($c) {
                        $sig = $c->siguienteNivel();

                        return [$c->nombre_completo, $c->mesesAntiguedad(), $c->nivel()?->nombre ?? '—', $sig ? $sig->nombre.' (faltan '.($sig->meses_minimos - $c->mesesAntiguedad()).' meses)' : 'Nivel máximo'];
                    })->values()->all()],
            ],
        ];
    }

    private function grupos(): array
    {
        $grupos = Grupo::with('clientes.suscripciones')->orderBy('nombre')->get();
        $pagos = Suscripcion::whereNotNull('lote')->get()->groupBy('grupo_id');

        return [
            'resumen' => [['Parejas', $grupos->where('tipo', 'pareja')->count()], ['Familias', $grupos->where('tipo', 'familiar')->count()], ['Socios en grupos', $grupos->sum(fn ($g) => $g->clientes->count())]],
            'tablas' => collect(['pareja' => 'Parejas', 'familiar' => 'Familias'])->map(fn ($titulo, $tipo) => [
                'titulo' => $titulo,
                'columnas' => ['Grupo', 'Integrantes', 'Cantidad', 'Pagos en grupo', 'Total pagado en grupo'],
                'derecha' => [2, 3, 4],
                'filas' => $grupos->where('tipo', $tipo)->map(fn ($g) => [
                    $g->nombre, $g->clientes->map(fn ($c) => $c->nombre_completo.' ('.mb_strtolower($c->estado()).')')->join(', '), $g->clientes->count(),
                    ($pagos[$g->id] ?? collect())->pluck('lote')->unique()->count(), $this->bs(($pagos[$g->id] ?? collect())->sum('total')),
                ])->values()->all(),
                'vacio' => 'No hay '.mb_strtolower($titulo).' registradas.',
            ])->values()->all(),
        ];
    }

    private function usuarios(): array
    {
        $usuarios = User::orderBy('name')->get();
        $roles = ['administrador' => 'Administradores', 'recepcionista' => 'Recepcionistas', 'cliente' => 'Socios (acceso al portal)'];

        return [
            'resumen' => collect($roles)->map(fn ($t, $r) => [$t, $usuarios->where('rol', $r)->count()])->values()->prepend(['Total de usuarios', $usuarios->count()])->all(),
            'tablas' => collect($roles)->map(fn ($t, $r) => [
                'titulo' => $t,
                'columnas' => ['Nombre', 'Correo', 'CI', 'Registrado el'],
                'filas' => $usuarios->where('rol', $r)->map(fn ($u) => [$u->name, $u->email, $u->ci ?: '—', $u->created_at?->format('d/m/Y') ?? '—'])->values()->all(),
                'vacio' => 'Sin usuarios con este rol.',
            ])->values()->all(),
        ];
    }

    // ---------------------------------------------------------------- ayudas

    private function suscripciones(Carbon $desde, Carbon $hasta)
    {
        return Suscripcion::with('plan', 'cliente')->whereDate('created_at', '>=', $desde)->whereDate('created_at', '<=', $hasta)->get();
    }

    private function mes(string $ym): string
    {
        return ucfirst(Carbon::createFromFormat('Y-m-d', $ym.'-01')->translatedFormat('F Y'));
    }

    private function bs($v): string
    {
        return 'Bs '.number_format((float) $v, 2, ',', '.');
    }

    private function n($v): string
    {
        return number_format((int) $v, 0, ',', '.');
    }

    private function pct($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',').'%';
    }
}
