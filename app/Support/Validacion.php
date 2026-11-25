<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Formatos de datos personales usados en los formularios (socios y recepcionistas). */
class Validacion
{
    /** Solo letras (con tildes y ñ), espacios, apóstrofo y guion. Mínimo 2 letras. */
    public const NOMBRE = "/^(?=(?:.*\\pL){2})[\\pL\\s'\\-]+$/u";

    /** Cédula boliviana: 5 a 10 dígitos y, si tiene, un complemento como 1A (ej.: 7845123 o 7845123-1A). */
    public const CI = '/^\d{5,10}(-[0-9A-Z]{1,2})?$/';

    /** Celular de 8 dígitos que empieza en 6 o 7, o fijo de 7 dígitos; se acepta el prefijo +591. */
    public const TELEFONO = '/^(\+591)?([67]\d{7}|[2-4]\d{6})$/';

    public const MENSAJES = [
        'nombre.regex' => 'El nombre solo puede tener letras y espacios.',
        'apellido.regex' => 'El apellido solo puede tener letras y espacios.',
        'name.regex' => 'El nombre solo puede tener letras y espacios.',
        'ci.regex' => 'El CI debe tener de 5 a 10 números (puede llevar complemento, ej.: 7845123-1A).',
        'telefono.regex' => 'Escribe un celular de 8 dígitos (empieza en 6 o 7) o un fijo de 7 dígitos, solo números.',
        'fecha_nacimiento.after' => 'Revisa la fecha de nacimiento.',
        'fecha_nacimiento.before' => 'El socio debe tener al menos 12 años.',
    ];

    /** Limpia espacios de más y los separadores del teléfono antes de validar. */
    public static function normalizar(Request $request): void
    {
        $limpio = [];
        foreach (['nombre', 'apellido', 'name'] as $campo) {
            if ($request->filled($campo)) {
                $limpio[$campo] = preg_replace('/\s+/u', ' ', trim((string) $request->input($campo)));
            }
        }
        if ($request->filled('ci')) {
            $limpio['ci'] = mb_strtoupper(preg_replace('/\s+/', '', (string) $request->input('ci')));
        }
        if ($request->filled('telefono')) {
            $limpio['telefono'] = preg_replace('/[\s\-().]/', '', (string) $request->input('telefono'));
        }
        $request->merge($limpio);
    }
}
