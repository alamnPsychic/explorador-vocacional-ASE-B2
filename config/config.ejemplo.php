<?php
/**
 * CONFIGURACIÓN DEL AMBIENTE
 * Explorador Vocacional UTBB
 *
 * Copia este archivo como config.php y ajusta los valores a tu equipo.
 *
 *     cp config/config.ejemplo.php config/config.php
 *
 * config.php NO se sube al repositorio: cada quien tiene el suyo.
 * Está incluido en .gitignore.
 */

declare(strict_types=1);

/* --- Base de datos --- */
const BD_SERVIDOR         = 'localhost';
const BD_PUERTO           = 3306;
const BD_NOMBRE           = 'explorador_vocacional';
const BD_USUARIO          = 'root';
const BD_CONTRASENA       = '';          // en XAMPP local suele ir vacía
const BD_JUEGO_CARACTERES = 'utf8mb4';

/**
 * Ruta base de la aplicación dentro del servidor, SIN diagonal final.
 * Si el proyecto está en htdocs/explorador-vocacional, el valor es
 * '/explorador-vocacional'. Si está en la raíz del servidor, cadena vacía.
 */
const RUTA_BASE = '/explorador-vocacional';

/**
 * true en tu equipo, false en el servidor publicado.
 * En desarrollo muestra los errores; en producción los oculta y los registra.
 */
const DESARROLLO = true;

if (DESARROLLO) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

date_default_timezone_set('America/Mexico_City');
mb_internal_encoding('UTF-8');
