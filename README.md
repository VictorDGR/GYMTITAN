# Gimnasio Titán · Sistema de fidelización

Sistema independiente para fidelizar clientes de un gimnasio. Incluye el registro de suscripciones, puntos canjeables, premios por antigüedad, descuentos por fechas especiales y planes para parejas y familias.

## Roles

| Rol | Qué hace |
|---|---|
| **Administrador** | Todo lo de recepción, más planes, promociones, premios, niveles de antigüedad, recepcionistas, configuración y reportes. |
| **Recepcionista** | Registra clientes, cobra planes (con el descuento aplicado automáticamente), arma parejas o familias y procesa canjes. |
| **Cliente** | Entra con su **correo o CI** y contraseña (la inicial es su CI). Solo consulta sus puntos, su nivel, sus días restantes, sus promociones, los premios y su historial. |

## Reglas del programa

- **Puntos:** cada plan otorga puntos al pagarse. Se canjean por premios del catálogo en recepción.
- **Referidos:** cuando un cliente recomendado paga su primera suscripción, quien lo recomendó gana puntos (por defecto 100, configurable).
- **Antigüedad:** se cuentan los días realmente pagados, en meses de 30 días. Cada nivel (Bronce, Plata, Oro, Platino) da un bono de puntos una sola vez y un descuento al renovar.
- **Promociones:** fecha especial (Navidad, Año Nuevo…), mes de cumpleaños, pareja y familiar. Se pueden limitar a un plan.
- **Descuentos no acumulables:** al cobrar, el sistema elige el mayor descuento entre las promociones vigentes y el nivel del cliente.
- **Renovación adelantada:** si el cliente aún tiene días pagados, el nuevo periodo empieza al día siguiente de su vencimiento.

## Instalación local (XAMPP)

1. Inicia MySQL en XAMPP (puerto 3307, como en la clínica) y crea la base `gimnasio_fidelizacion`.
2. En esta carpeta:
   ```bash
   composer install          # solo si falta la carpeta vendor
   php artisan migrate --seed
   php artisan serve
   ```
3. Abre http://127.0.0.1:8000

Si `.env` no existe, copia `.env.example` a `.env` y ejecuta `php artisan key:generate`.

### Usuarios de prueba

| Rol | Acceso | Contraseña |
|---|---|---|
| Administrador | admin@titangym.com | admin123 |
| Recepcionista | recepcion@titangym.com | recepcion123 |
| Cliente (María, nivel Oro) | 7845123 | 7845123 |
| Cliente (Carlos, Platino, vencido) | 4567890 | 4567890 |
| Cliente (Jorge, en pareja, por vencer) | 6123098 | 6123098 |

Para reiniciar los datos de ejemplo: `php artisan migrate:fresh --seed`.

## Avance por módulos (tablero Kanban)

`SPRINT_MODULOS` en el `.env` define qué módulos se muestran, según el avance del tablero. Los que no están en la lista se ocultan del menú y sus páginas responden "en desarrollo".

| Módulo | Historia |
|---|---|
| `socios` | HU-002 |
| `planes` | HU-003 |
| `suscripciones` | HU-004 |
| `pagos` | HU-006 |
| `promociones` | HU-010 |
| `puntos` | HU-007 |
| `premios`, `canjes` | HU-008 |
| `niveles` | HU-009 |
| `grupos` | HU-005 |
| `vencimientos` | HU-011 (listas de por vencer y vencidos) |
| `renovar-aviso` | HU-011 (botón Renovar desde el aviso, T-011-03) |
| `indicadores` | HU-011 (indicadores del inicio, T-011-04) |
| `portal` | HU-012 |
| `usuarios` | HU-001 |
| `reportes`, `configuracion` | fuera del backlog |

Al terminar una historia, agrega su módulo a la lista. Para mostrar todo el sistema, deja la variable vacía (`SPRINT_MODULOS=`). Después de cambiarla, si usaste `config:cache`, ejecuta `php artisan config:clear`.

## Despliegue en Render

Funciona igual que la clínica: `render.yaml` crea el servicio Docker y una base PostgreSQL. Al crear el servicio, define `APP_KEY` (genérala con `php artisan key:generate --show`) y `ADMIN_PASSWORD`.
