<?php
/**
 * NÚCLEO · Sesión y control de acceso
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Roles de la plataforma:
 *   aspirante     · visitante registrado
 *   editor        · responsable del contenido de UNA carrera (usuarios.carrera_id)
 *   coordinacion  · personal que revisa, modera y dictamina
 */

declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

const ROLES = ['aspirante', 'editor', 'coordinacion'];

/** Inicia la sesión si no está iniciada. Se llama al principio de cada página. */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,   // JavaScript no puede leer la cookie de sesión
        'samesite' => 'Lax',  // no viaja desde otros sitios
    ]);

    session_start();
}

/** Devuelve el usuario de la sesión, o null si nadie ha iniciado. */
function usuario(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** ¿Hay sesión iniciada? */
function hay_sesion(): bool
{
    return usuario() !== null;
}

/** Rol del usuario actual, o cadena vacía. */
function rol(): string
{
    return usuario()['rol'] ?? '';
}

/** Carrera asociada al editor, o null. */
function carrera_del_usuario(): ?int
{
    $c = usuario()['carrera_id'] ?? null;
    return $c === null ? null : (int)$c;
}

/* -------------------------------------------------------------------------
 * VERIFICACIONES
 *
 * REGLA DEL PROYECTO: toda página del panel llama a requerir_rol() en su
 * PRIMERA instrucción, antes de imprimir cualquier cosa. Esconder el enlace
 * en el menú no es control de acceso: la dirección se puede escribir a mano.
 * ---------------------------------------------------------------------- */

/** Exige sesión iniciada. */
function requerir_sesion(): void
{
    iniciar_sesion();

    if (!hay_sesion()) {
        $_SESSION['destino'] = $_SERVER['REQUEST_URI'] ?? '/';
        mensaje('info', 'Inicia sesión para continuar.');
        redirigir(RUTA_BASE . '/auth/entrar.php');
    }
}

/**
 * Exige sesión iniciada Y uno de los roles indicados.
 *
 *     requerir_rol('coordinacion');
 *     requerir_rol('editor', 'coordinacion');
 */
function requerir_rol(string ...$permitidos): void
{
    requerir_sesion();

    if (!in_array(rol(), $permitidos, true)) {
        http_response_code(403);
        exit('No tienes permiso para ver esta página.');
    }
}

/**
 * Exige que el editor trabaje sobre SU carrera.
 * La coordinación pasa siempre.
 *
 * Sin esta verificación, un editor puede cambiar ?carrera_id= en la dirección
 * y editar el contenido de otra carrera.
 */
function requerir_carrera(int $carreraId): void
{
    requerir_sesion();

    if (rol() === 'coordinacion') {
        return;
    }

    if (rol() === 'editor' && carrera_del_usuario() === $carreraId) {
        return;
    }

    http_response_code(403);
    exit('No tienes permiso sobre esta carrera.');
}

/* -------------------------------------------------------------------------
 * ENTRAR Y SALIR
 * ---------------------------------------------------------------------- */

/**
 * Comprueba las credenciales. Devuelve true si la sesión quedó abierta.
 *
 * El mismo mensaje de error sirve para correo inexistente y contraseña
 * incorrecta: distinguirlos le revela a un atacante qué correos existen.
 */
function intentar_entrar(string $correo, string $contrasena): bool
{
    iniciar_sesion();

    $sql = 'SELECT id, nombre, correo, contrasena, rol, carrera_id
              FROM usuarios
             WHERE correo = ? AND activo = 1
             LIMIT 1';

    $con = bd()->prepare($sql);
    $con->execute([$correo]);
    $fila = $con->fetch();

    if (!$fila || !password_verify($contrasena, $fila['contrasena'])) {
        return false;
    }

    // Evita la fijación de sesión: el identificador cambia al entrar.
    session_regenerate_id(true);

    unset($fila['contrasena']);          // la contraseña no vive en la sesión
    $_SESSION['usuario'] = $fila;

    return true;
}

/** Cierra la sesión por completo. */
function salir(): void
{
    iniciar_sesion();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}
