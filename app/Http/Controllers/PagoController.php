<?php

namespace App\Http\Controllers;

use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HU-006 · Historial de pagos y comprobante (recepcionista y administrador). */
class PagoController extends Controller
{
    public function index(Request $request): View
    {
        $desde = $request->date('desde') ?? today()->startOfMonth();
        $hasta = $request->date('hasta') ?? today();
        $metodo = in_array($request->query('metodo'), Suscripcion::METODOS, true) ? $request->query('metodo') : null;
        $buscar = trim((string) $request->query('buscar', ''));

        $pagos = Suscripcion::with('cliente', 'plan', 'registradoPor', 'grupo')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)
            ->when($metodo, fn ($q) => $q->where('metodo_pago', $metodo))
            ->when($buscar !== '', fn ($q) => $q->whereHas('cliente', fn ($c) => $c
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('apellido', 'like', "%{$buscar}%")
                ->orWhere('ci', 'like', "%{$buscar}%")))
            ->latest()->latest('id')
            ->get();

        $porMetodo = collect(Suscripcion::METODOS)
            ->mapWithKeys(fn ($m) => [$m => $pagos->where('metodo_pago', $m)->sum('total')]);

        return view('pagos.index', [
            'pagos' => $pagos,
            'desde' => $desde,
            'hasta' => $hasta,
            'metodo' => $metodo,
            'buscar' => $buscar,
            'porMetodo' => $porMetodo,
            'descuentos' => $pagos->sum(fn ($p) => $p->precio_base - $p->total),
        ]);
    }

    public function show(int $pago): View
    {
        $pago = Suscripcion::with('cliente', 'plan', 'registradoPor', 'promocion')->findOrFail($pago);

        return view('pagos.show', ['pago' => $pago]);
    }
}
