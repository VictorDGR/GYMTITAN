<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="login-page">
    <section class="login-hero">
        <div class="big">Entrena.<br>Suma puntos.<br><span>Gana premios.</span></div>
        <p>El club de beneficios de {{ config('app.name') }}: cada mes que entrenas con nosotros vale más.</p>
        <ul>
            <li>Puntos canjeables</li>
            <li>Premios por antigüedad</li>
            <li>Descuentos en fechas especiales</li>
            <li>Planes pareja y familiar</li>
        </ul>
    </section>
    <section class="login-side">
        <div class="login-card">
            <div class="brand" style="padding:0">
                <span class="brand-mark">T</span>
                <span><strong>{{ config('app.name') }}</strong><small>Club de beneficios</small></span>
            </div>
            <div>
                <h1>Ingresar</h1>
                <p class="sub">Usa tu correo o tu CI. Si eres cliente nuevo, tu contraseña es tu CI.</p>
            </div>
            <form method="POST" action="{{ route('login') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="acceso">Correo o CI</label>
                    <input id="acceso" name="acceso" value="{{ old('acceso') }}" required autofocus autocomplete="username" placeholder="tucorreo@gmail.com o 7845123">
                    <x-error field="acceso" />
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                    <x-error field="password" />
                </div>
                <label class="check"><input type="checkbox" name="recordar" value="1"> Mantener la sesión iniciada</label>
                <button class="button button-primary button-block" type="submit">Ingresar</button>
            </form>
            @if (app()->environment('local'))
                <div class="demo-users">
                    <span><strong>Administrador:</strong> admin@titangym.com · admin123</span>
                    <span><strong>Recepcionista:</strong> recepcion@titangym.com · recepcion123</span>
                    @modulo('portal')<span><strong>Cliente:</strong> CI 7845123 · contraseña 7845123</span>@endmodulo
                </div>
            @endif
        </div>
    </section>
</main>
</body>
</html>
