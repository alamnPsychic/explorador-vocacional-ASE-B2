<?php
/**
 * NÚCLEO · Carga de archivos
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Los diez módulos reciben archivos. Esta función centraliza las tres
 * verificaciones que no pueden faltar:
 *
 *   1. El tipo REAL del archivo, leído de su contenido con finfo.
 *      La extensión y el tipo que envía el navegador los controla el usuario:
 *      un .php renombrado a .jpg sigue siendo un .php.
 *   2. El tamaño.
 *   3. El nombre, que lo genera el SERVIDOR. Si se conserva el nombre
 *      original, el usuario decide dónde y con qué nombre se escribe.
 */

declare(strict_types=1);

/** Tipos permitidos en la plataforma y su extensión. */
const TIPOS_PERMITIDOS = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'video/mp4'  => 'mp4',
    'audio/mpeg' => 'mp3',
    'audio/mp4'  => 'm4a',
    'application/pdf' => 'pdf',
];

/** Conjuntos de uso frecuente. */
const SOLO_IMAGEN    = ['image/jpeg', 'image/png', 'image/webp'];
const SOLO_DOCUMENTO = ['application/pdf'];
const SOLO_VIDEO     = ['video/mp4'];

/**
 * Guarda un archivo subido y devuelve el nombre generado.
 *
 *   $nombre = guardar_archivo($_FILES['portada'], __DIR__.'/../uploads', SOLO_IMAGEN, 1);
 *
 * @param array  $archivo     Un elemento de $_FILES
 * @param string $carpeta     Carpeta destino (debe existir y ser escribible)
 * @param array  $permitidos  Lista de tipos MIME aceptados
 * @param int    $maximoMB    Tamaño máximo en megabytes
 *
 * @throws RuntimeException con un mensaje que SÍ se puede mostrar al usuario
 */
function guardar_archivo(array $archivo, string $carpeta, array $permitidos, int $maximoMB): string
{
    if (!isset($archivo['error']) || is_array($archivo['error'])) {
        throw new RuntimeException('No se recibió el archivo.');
    }

    switch ($archivo['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No seleccionaste ningún archivo.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('El archivo excede el tamaño permitido.');
        default:
            throw new RuntimeException('No se pudo subir el archivo.');
    }

    // 2. Tamaño
    $maximoBytes = $maximoMB * 1024 * 1024;
    if ($archivo['size'] > $maximoBytes) {
        throw new RuntimeException("El archivo supera los {$maximoMB} MB.");
    }

    // 1. Tipo real, leído del contenido
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo  = (string)$finfo->file($archivo['tmp_name']);

    if (!in_array($tipo, $permitidos, true) || !isset(TIPOS_PERMITIDOS[$tipo])) {
        throw new RuntimeException('El tipo de archivo no está permitido.');
    }

    // 3. Nombre generado por el servidor
    $extension = TIPOS_PERMITIDOS[$tipo];
    $nombre    = bin2hex(random_bytes(16)) . '.' . $extension;
    $destino   = rtrim($carpeta, '/\\') . DIRECTORY_SEPARATOR . $nombre;

    if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
        throw new RuntimeException('No existe la carpeta de destino.');
    }

    // move_uploaded_file confirma además que el archivo llegó por HTTP POST.
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }

    return $nombre;
}

/**
 * Entrega un archivo restringido al navegador.
 *
 * Se usa cuando el archivo NO debe ser público: un expediente de beca, un
 * currículum. El permiso se verifica ANTES de llamar a esta función; aquí
 * solo se entrega.
 */
function entregar_archivo(string $ruta, string $nombreVisible): never
{
    $real = realpath($ruta);

    if ($real === false || !is_file($real)) {
        no_encontrado('El archivo no existe.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    header('Content-Type: ' . $finfo->file($real));
    header('Content-Length: ' . filesize($real));
    header('Content-Disposition: attachment; filename="' . basename($nombreVisible) . '"');
    header('X-Content-Type-Options: nosniff');

    readfile($real);
    exit;
}
