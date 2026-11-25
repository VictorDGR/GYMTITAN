<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CanjeController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\NivelController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PremioController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\PuntoController;
use App\Http\Controllers\RecepcionistaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SuscripcionController;
use Illuminate\Support\Facades\Route;
foreach (['cliente', 'suscripcion', 'grupo', 'plan', 'promocion', 'premio', 'nivel', 'recepcionista', 'pago'] as $parametro) {
    Route::pattern($parametro, '[0-9]+');
}

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

    // Envía a cada rol a su pantalla de inicio.
    Route::get('/', fn () => auth()->user()->esCliente() ? redirect()->route('portal') : redirect()->route('panel'))->name('inicio');

    Route::get('contrasena', [PerfilController::class, 'edit'])->middleware('modulo:usuarios')->name('perfil.password');
    Route::put('contrasena', [PerfilController::class, 'update'])->middleware('modulo:usuarios');

    // Cliente: solo consulta sus beneficios
    Route::middleware(['rol:cliente', 'modulo:portal'])->prefix('mi-cuenta')->name('portal')->group(function (): void {
        Route::get('/', [PortalController::class, 'inicio'])->name('');
        Route::get('puntos', [PortalController::class, 'puntos'])->name('.puntos');
        Route::get('historial', [PortalController::class, 'historial'])->name('.historial');
        Route::get('membresia', [PortalController::class, 'membresia'])->name('.membresia');
        Route::get('pagar', [PortalController::class, 'pagar'])->name('.pagar');
        Route::post('pagar', [PortalController::class, 'pagarStore'])->name('.pagar.store');
        Route::get('comprobante/{pago}', [PortalController::class, 'comprobante'])->whereNumber('pago')->name('.comprobante');
        Route::get('configuracion', [PortalController::class, 'configuracion'])->name('.configuracion');
        Route::put('configuracion/datos', [PortalController::class, 'guardarDatos'])->name('.configuracion.datos');
        Route::put('configuracion/contrasena', [PortalController::class, 'guardarContrasena'])->name('.configuracion.contrasena');
    });

    // Recepcionista (y administrador)
    Route::middleware('rol:recepcionista')->group(function (): void {
        Route::get('panel', DashboardController::class)->name('panel');
        Route::resource('clientes', ClienteController::class)->parameters(['clientes' => 'cliente']);
        Route::post('clientes/{cliente}/puntos', [ClienteController::class, 'ajustarPuntos'])->name('clientes.puntos');
        Route::post('clientes/{cliente}/restablecer', [ClienteController::class, 'restablecerContrasena'])->name('clientes.restablecer');
        Route::middleware('modulo:grupos')->group(function (): void {
            Route::get('suscripciones/grupal', [SuscripcionController::class, 'createGrupal'])->name('suscripciones.grupal');
            Route::post('suscripciones/grupal', [SuscripcionController::class, 'storeGrupal'])->name('suscripciones.grupal.store');
            Route::get('suscripciones/grupal/{lote}', [SuscripcionController::class, 'showGrupal'])->whereUuid('lote')->name('suscripciones.grupal.show');
        });
        Route::resource('suscripciones', SuscripcionController::class)->only(['index', 'create', 'store', 'show'])->parameters(['suscripciones' => 'suscripcion']);
        Route::get('puntos', [PuntoController::class, 'index'])->middleware('modulo:puntos')->name('puntos.index');
        Route::get('pagos', [PagoController::class, 'index'])->middleware('modulo:pagos')->name('pagos.index');
        Route::get('pagos/{pago}', [PagoController::class, 'show'])->middleware('modulo:pagos')->name('pagos.show');
        Route::resource('grupos', GrupoController::class)->except('show')->parameters(['grupos' => 'grupo'])->middleware('modulo:grupos');
        Route::resource('canjes', CanjeController::class)->only(['index', 'create', 'store'])->middleware('modulo:canjes');
    });

    // Solo administrador
    Route::middleware('rol:administrador')->group(function (): void {
        Route::resource('planes', PlanController::class)->except('show')->parameters(['planes' => 'plan']);
        Route::resource('promociones', PromocionController::class)->except('show')->parameters(['promociones' => 'promocion']);
        Route::resource('premios', PremioController::class)->except('show')->parameters(['premios' => 'premio'])->middleware('modulo:premios');
        Route::resource('niveles', NivelController::class)->except('show')->parameters(['niveles' => 'nivel'])->middleware('modulo:niveles');
        Route::resource('recepcionistas', RecepcionistaController::class)->except('show')->parameters(['recepcionistas' => 'recepcionista'])->middleware('modulo:usuarios');
        Route::get('configuracion', [ConfiguracionController::class, 'edit'])->middleware('modulo:configuracion')->name('configuracion.edit');
        Route::put('configuracion', [ConfiguracionController::class, 'update'])->middleware('modulo:configuracion')->name('configuracion.update');
        Route::get('reportes', [ReporteController::class, 'index'])->middleware('modulo:reportes')->name('reportes.index');
        Route::get('reportes/{tipo}', [ReporteController::class, 'show'])->middleware('modulo:reportes')->whereAlpha('tipo')->name('reportes.show');
    });
});
