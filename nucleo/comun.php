<?php
/**
 * NÚCLEO · Punto de entrada único
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * TODA página de la plataforma empieza con esta línea y ninguna otra:
 *
 *     <?php require_once __DIR__ . '/../../nucleo/comun.php';
 *
 * (ajustando los ../ según la profundidad de la página).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/archivos.php';
require_once __DIR__ . '/consultas.php';
require_once __DIR__ . '/modulos.php';
require_once __DIR__ . '/plantilla.php';

iniciar_sesion();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
