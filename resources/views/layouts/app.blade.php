<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php($usuario = auth()->user())
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('inicio') }}">
            <span class="brand-mark">T</span>
            <span><strong>{{ config('app.name') }}</strong><small>Club de beneficios</small></span>
        </a>
        <nav class="side-nav" aria-label="Navegación principal">
            @if ($usuario->esCliente())
                <span class="nav-label">Mi cuenta</span>
                <a class="{{ request()->routeIs('portal') || request()->routeIs('portal.pagar*') ? 'active' : '' }}" href="{{ route('portal') }}"><span class="ico">⌂</span> Inicio</a>
                <a class="{{ request()->routeIs('portal.puntos') ? 'active' : '' }}" href="{{ route('portal.puntos') }}"><span class="ico">★</span> Puntos y premios</a>
                <a class="{{ request()->routeIs('portal.historial') ? 'active' : '' }}" href="{{ route('portal.historial') }}"><span class="ico">✪</span> Mi historial</a>
                <a class="{{ request()->routeIs('portal.membresia') || request()->routeIs('portal.comprobante') ? 'active' : '' }}" href="{{ route('portal.membresia') }}"><span class="ico">◉</span> Mi membresía</a>
                <a class="{{ request()->routeIs('portal.configuracion*') ? 'active' : '' }}" href="{{ route('portal.configuracion') }}"><span class="ico">⚙</span> Configuración</a>
            @else
                <span class="nav-label">Recepción</span>
                <a class="{{ request()->routeIs('panel') ? 'active' : '' }}" href="{{ route('panel') }}"><span class="ico">⌂</span> Inicio</a>
                <a class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}"><span class="ico">♙</span> Clientes</a>
                <a class="{{ request()->routeIs('suscripciones.*') ? 'active' : '' }}" href="{{ route('suscripciones.index') }}"><span class="ico">▤</span> Suscripciones</a>
                @modulo('pagos')
                <a class="{{ request()->routeIs('pagos.*') ? 'active' : '' }}" href="{{ route('pagos.index') }}"><span class="ico">$</span> Historial de pagos</a>
                @endmodulo
                @modulo('puntos')
                <a class="{{ request()->routeIs('puntos.*') ? 'active' : '' }}" href="{{ route('puntos.index') }}"><span class="ico">✪</span> Puntos</a>
                @endmodulo
                @modulo('grupos')
                <a class="{{ request()->routeIs('grupos.*') ? 'active' : '' }}" href="{{ route('grupos.index') }}"><span class="ico">⚇</span> Parejas y familias</a>
                @endmodulo
                @modulo('canjes')
                <a class="{{ request()->routeIs('canjes.*') ? 'active' : '' }}" href="{{ route('canjes.index') }}"><span class="ico">★</span> Canjes</a>
                @endmodulo
                @if ($usuario->esAdministrador())
                    <span class="nav-label">Programa de fidelización</span>
                    <a class="{{ request()->routeIs('planes.*') ? 'active' : '' }}" href="{{ route('planes.index') }}"><span class="ico">◫</span> Planes</a>
                    <a class="{{ request()->routeIs('promociones.*') ? 'active' : '' }}" href="{{ route('promociones.index') }}"><span class="ico">%</span> Promociones</a>
                    @modulo('premios')
                    <a class="{{ request()->routeIs('premios.*') ? 'active' : '' }}" href="{{ route('premios.index') }}"><span class="ico">✦</span> Premios</a>
                    @endmodulo
                    @modulo('niveles')
                    <a class="{{ request()->routeIs('niveles.*') ? 'active' : '' }}" href="{{ route('niveles.index') }}"><span class="ico">▲</span> Niveles de antigüedad</a>
                    @endmodulo
                    @if (\App\Support\Sprint::activo('usuarios') || \App\Support\Sprint::activo('reportes') || \App\Support\Sprint::activo('configuracion'))
                    <span class="nav-label">Administración</span>
                    @endif
                    @modulo('usuarios')
                    <a class="{{ request()->routeIs('recepcionistas.*') ? 'active' : '' }}" href="{{ route('recepcionistas.index') }}"><span class="ico">☏</span> Recepcionistas</a>
                    @endmodulo
                    @modulo('reportes')
                    <a class="{{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}"><span class="ico">▦</span> Reportes</a>
                    @endmodulo
                    @modulo('configuracion')
                    <a class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}" href="{{ route('configuracion.edit') }}"><span class="ico">⚙</span> Configuración</a>
                    @endmodulo
                @endif
                @modulo('usuarios')
                <a class="{{ request()->routeIs('perfil.*') ? 'active' : '' }}" href="{{ route('perfil.password') }}"><span class="ico">⚿</span> Cambiar contraseña</a>
                @endmodulo
            @endif
        </nav>
        <div class="sidebar-foot">
            <span class="avatar">{{ $usuario->iniciales() }}</span>
            <span class="grow"><strong>{{ $usuario->name }}</strong><small>{{ $usuario->rol }}</small></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-button" type="submit">Salir</button>
            </form>
        </div>
    </aside>
    <main class="main-area">
        <div class="page-heading">
            <div>
                <div class="kicker">@yield('kicker', now()->translatedFormat('l, d \d\e F \d\e Y'))</div>
                <h1>@yield('heading', 'Inicio')</h1>
            </div>
            <div class="actions">@yield('actions')</div>
        </div>
        <section class="page-content">
            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            @endif
            @yield('content')
        </section>
    </main>
</div>
@stack('scripts')
</body>
</html>
