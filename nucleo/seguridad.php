<?php
/**
 * NÚCLEO · Funciones de seguridad y utilidades comunes
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------
 * SALIDA SEGURA
 * ---------------------------------------------------------------------- */

/**
 * Imprime texto de forma segura. TODO dato que venga de la base de datos o
 * del usuario pasa por aquí antes de llegar al HTML.
 *
 *     <h1><?= e($fila['titulo']) ?></h1>
 *
 * Sin esto, un registro que contenga <script> se ejecuta en el navegador de
 * quien visite la página. Eso es un ataque XSS.
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* -------------------------------------------------------------------------
 * REDIRECCIÓN
 * ---------------------------------------------------------------------- */

/**
 * Redirige y detiene la ejecución.
 *
 * Se usa siempre después de un POST que modifica datos (patrón
 * POST-Redirect-GET). Si no se redirige, al recargar la página el navegador
 * reenvía el formulario y el registro se duplica.
 */
function redirigir(string $destino): never
{
    header('Location: ' . $destino);
    exit;
}

/* -------------------------------------------------------------------------
 * MENSAJES ENTRE PÁGINAS
 * ---------------------------------------------------------------------- */

/**
 * Guarda un mensaje que se mostrará en la SIGUIENTE página.
 * $tipo: exito | error | alerta | info
 */
function mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensajes'][] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Devuelve los mensajes pendientes y los borra. */
function tomar_mensajes(): array
{
    $pendientes = $_SESSION['mensajes'] ?? [];
    unset($_SESSION['mensajes']);
    return $pendientes;
}

/** Imprime los mensajes pendientes con el componente de alerta del sistema. */
function pintar_mensajes(): void
{
    foreach (tomar_mensajes() as $m) {
        $papel = ($m['tipo'] === 'error') ? 'alert' : 'status';
        printf(
            '<div class="alerta alerta--%s" role="%s"><p>%s</p></div>',
            e($m['tipo']),
            $papel,
            e($m['texto'])
        );
    }
}

/* -------------------------------------------------------------------------
 * TOKEN ANTI-CSRF
 *
 * Impide que un sitio ajeno envíe un formulario a nuestra aplicación usando
 * la sesión abierta del usuario. Todo formulario POST lleva el token.
 * ---------------------------------------------------------------------- */

function campo_token(): string
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="token" value="' . e($_SESSION['token']) . '">';
}

/** Verifica el token. Si no coincide, corta la petición. */
function verificar_token(): void
{
    $enviado  = $_POST['token']     ?? '';
    $esperado = $_SESSION['token']  ?? '';

    if ($enviado === '' || !hash_equals($esperado, $enviado)) {
        http_response_code(400);
        exit('Petición no válida.');
    }
}

/* -------------------------------------------------------------------------
 * ENTRADA
 * ---------------------------------------------------------------------- */

/** Devuelve un campo POST ya recortado de espacios. */
function post(string $campo, string $porOmision = ''): string
{
    return trim((string)($_POST[$campo] ?? $porOmision));
}

/** Devuelve un campo GET ya recortado de espacios. */
function get(string $campo, string $porOmision = ''): string
{
    return trim((string)($_GET[$campo] ?? $porOmision));
}

/**
 * Devuelve un entero positivo o null.
 * Útil para los identificadores que llegan en la dirección: ?id=7
 */
function entero(?string $valor): ?int
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $n = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $n === false ? null : $n;
}

/** Termina la petición con 404 cuando el recurso solicitado no existe. */
function no_encontrado(string $mensaje = 'El recurso solicitado no existe.'): never
{
    http_response_code(404);
    exit($mensaje);
}
