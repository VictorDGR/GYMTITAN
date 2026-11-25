<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('es');

        // @bs($monto) → "Bs 1.250,00"
        Blade::directive('bs', fn (string $monto) => "<?php echo 'Bs '.number_format((float) ($monto), 2, ',', '.'); ?>");

        // @pct(15.00) → "15%"
        Blade::directive('pct', fn (string $valor) => "<?php echo rtrim(rtrim(number_format((float) ($valor), 2, ',', ''), '0'), ',').'%'; ?>");

        // @modulo('niveles') ... @endmodulo → solo se muestra si el módulo ya está habilitado
        Blade::if('modulo', fn (string $modulo) => \App\Support\Sprint::activo($modulo));
    }
}
