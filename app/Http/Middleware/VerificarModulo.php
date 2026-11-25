<?php

namespace App\Http\Middleware;

use App\Support\Sprint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bloquea las rutas de un módulo que todavía no está habilitado en el avance del proyecto. */
class VerificarModulo
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        abort_unless(Sprint::activo($modulo), 404, 'Este módulo todavía está en desarrollo.');

        return $next($request);
    }
}
