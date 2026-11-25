<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MovimientoPunto;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HU-007 · Saldo e historial de puntos de todos los socios (recepcionista y administrador). */
class PuntoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $tipo = array_key_exists((string) $request->query('tipo'), MovimientoPunto::TIPOS) ? $request->query('tipo') : null;

        $socios = Cliente::query()
            ->withSum(['movimientos as ganados' => fn ($q) => $q->where('puntos', '>', 0)], 'puntos')
            ->withSum(['movimientos as usados' => fn ($q) => $q->where('puntos', '<', 0)], 'puntos')
            ->withMax('movimientos as ultimo_movimiento', 'created_at')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($c) => $c
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('apellido', 'like', "%{$buscar}%")
                ->orWhere('ci', 'like', "%{$buscar}%")))
            ->orderByDesc('puntos')->orderBy('apellido')
            ->get();

        $movimientos = MovimientoPunto::with('cliente')
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->latest()->latest('id')
            ->limit(30)
            ->get();

        $delMes = MovimientoPunto::whereDate('created_at', '>=', today()->startOfMonth());

        return view('puntos.index', [
            'socios' => $socios,
            'movimientos' => $movimientos,
            'buscar' => $buscar,
            'tipo' => $tipo,
            'enCirculacion' => Cliente::sum('puntos'),
            'otorgadosMes' => (clone $delMes)->where('puntos', '>', 0)->sum('puntos'),
            'usadosMes' => abs((clone $delMes)->where('puntos', '<', 0)->sum('puntos')),
            'conPuntos' => Cliente::where('puntos', '>', 0)->count(),
        ]);
    }
}
