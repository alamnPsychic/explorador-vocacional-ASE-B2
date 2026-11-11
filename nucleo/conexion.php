<?php
/**
 * NÚCLEO · Conexión a la base de datos
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Devuelve siempre la MISMA conexión PDO. Se abre una sola vez por petición:
 * abrir una conexión por consulta desperdicia recursos del servidor.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function bd(): PDO
{
    // La conexión se guarda entre llamadas dentro de la misma petición.
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        BD_SERVIDOR,
        BD_PUERTO,
        BD_NOMBRE,
        BD_JUEGO_CARACTERES
    );

    $opciones = [
        // Los errores lanzan excepción en lugar de pasar inadvertidos.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Las filas llegan como arreglo asociativo: $fila['nombre'].
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Sentencias preparadas REALES, no emuladas por el controlador.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, BD_USUARIO, BD_CONTRASENA, $opciones);
    } catch (PDOException $e) {
        // Nunca se muestra el mensaje original al usuario: contiene datos del
        // servidor. Se registra en el log y se muestra un aviso genérico.
        error_log('Error de conexión: ' . $e->getMessage());

        http_response_code(500);
        exit('No fue posible conectar con la base de datos. Avisa al docente.');
    }

    return $pdo;
}
