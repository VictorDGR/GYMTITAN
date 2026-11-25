<?php

namespace App\Http\Controllers;

use App\Models\Canje;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Promocion;
use App\Models\Suscripcion;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $clientes = Cliente::with('suscripciones')->get();
        $aviso = Configuracion::entero('dias_aviso_vencimiento');

        $porVencer = $clientes
            ->filter(fn (Cliente $c) => $c->estado() === 'Por vencer')
            ->sortBy(fn (Cliente $c) => $c->diasRestantes())
            ->values();

        $vencidosRecientes = $clientes
            ->filter(fn (Cliente $c) => $c->estado() === 'Vencido' && $c->pagadoHasta()->gte(today()->subDays(30)))
            ->sortByDesc(fn (Cliente $c) => $c->pagadoHasta())
            ->values();

        $inicioMes = today()->startOfMonth();

        return view('dashboard', [
            'activos' => $clientes->filter(fn (Cliente $c) => in_array($c->estado(), ['Activo', 'Por vencer'], true))->count(),
            'totalClientes' => $clientes->count(),
            'porVencer' => $porVencer,
            'vencidosRecientes' => $vencidosRecientes,
            'aviso' => $aviso,
            'ingresosMes' => (float) Suscripcion::whereDate('created_at', '>=', $inicioMes)->sum('total'),
            'descuentosMes' => (float) Suscripcion::whereDate('created_at', '>=', $inicioMes)->selectRaw('COALESCE(SUM(precio_base - total), 0) as d')->value('d'),
            'nuevosMes' => Cliente::whereDate('created_at', '>=', $inicioMes)->count(),
            'canjesMes' => Canje::whereDate('created_at', '>=', $inicioMes)->count(),
            'promociones' => Promocion::vigentes()->with('plan')->orderByDesc('porcentaje')->get(),
            'ultimasSuscripciones' => Suscripcion::with('cliente', 'plan')->latest()->latest('id')->limit(6)->get(),
        ]);
    }
}
