<?php
/**
 * NÚCLEO · Cierre de sesión
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 */

declare(strict_types=1);

require_once __DIR__ . '/../nucleo/comun.php';

salir();

iniciar_sesion();
mensaje('info', 'Cerraste sesión.');
redirigir(RUTA_BASE . '/');
