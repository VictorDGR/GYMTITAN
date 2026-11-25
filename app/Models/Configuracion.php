<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Parámetros del programa de fidelización que el administrador puede cambiar. */
class Configuracion extends Model
{
    /** clave => [etiqueta, ayuda, valor por defecto] */
    public const PARAMETROS = [
        'puntos_referido' => ['Puntos por referido', 'Se suman al socio que recomendó cuando el nuevo cliente paga su primera suscripción.', 100],
        'dias_aviso_vencimiento' => ['Días de aviso de vencimiento', 'Con cuántos días de anticipación una membresía aparece como "por vencer".', 7],
    ];

    protected $table = 'configuraciones';

    protected $primaryKey = 'clave';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['clave', 'valor'];

    private static array $cache = [];

    public static function entero(string $clave): int
    {
        if (! array_key_exists($clave, self::$cache)) {
            self::$cache[$clave] = static::find($clave)?->valor;
        }

        return (int) (self::$cache[$clave] ?? self::PARAMETROS[$clave][2] ?? 0);
    }

    public static function guardar(string $clave, int $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => (string) $valor]);
        self::$cache[$clave] = (string) $valor;
    }
}
