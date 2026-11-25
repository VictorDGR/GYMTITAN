<?php

/*
 * Avance del proyecto según el tablero Kanban.
 *
 * SPRINT_MODULOS en el .env lista los módulos que ya se muestran, separados por comas.
 * Si se deja vacío, el sistema muestra todos los módulos.
 *
 * Módulos y su historia de usuario:
 *   socios (HU-002), planes (HU-003), suscripciones (HU-004), pagos (HU-006),
 *   promociones (HU-010), puntos (HU-007), premios y canjes (HU-008), niveles (HU-009),
 *   grupos (HU-005), vencimientos (HU-011), portal (HU-012), usuarios (HU-001),
 *   reportes y configuracion (fuera del backlog).
 */

$modulos = trim((string) env('SPRINT_MODULOS', ''));

return [
    'modulos' => $modulos === '' ? null : array_map('trim', explode(',', $modulos)),
];
