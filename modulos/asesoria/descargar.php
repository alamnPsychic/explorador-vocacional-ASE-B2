<?php
/**
 * MÓDULO ASE · Descarga restringida de archivos adjuntos
 * Explorador Vocacional UTBB
 *
 * Verifica permisos antes de entregar el archivo con entregar_archivo().
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';

requerir_sesion();

require_once __DIR__ . '/includes/consultas.php';

$id = entero(get('id'));
if ($id === null) {
    no_encontrado('Archivo no especificado.');
}

$adjunto = ase_adjunto_obtener($id);
if ($adjunto === null) {
    no_encontrado('El archivo adjunto no existe.');
}

$usuarioActual = usuario();
$rolActual     = rol();

// Permisos:
// 1. Coordinación puede descargar cualquier adjunto.
// 2. Editor puede descargar si la conversación es de su carrera o general (NULL).
// 3. Aspirante solo puede descargar si él es el titular de la conversación.
$tienePermiso = false;

if ($rolActual === 'coordinacion') {
    $tienePermiso = true;
} elseif ($rolActual === 'editor') {
    $carreraConv  = $adjunto['carrera_id'] !== null ? (int)$adjunto['carrera_id'] : null;
    $tienePermiso = ($carreraConv === null || $carreraConv === carrera_del_usuario());
} elseif ((int)$adjunto['aspirante_id'] === (int)$usuarioActual['id']) {
    $tienePermiso = true;
}

if (!$tienePermiso) {
    http_response_code(403);
    exit('No tienes permiso para descargar este archivo adjunto.');
}

$rutaArchivo = ase_ruta_uploads() . DIRECTORY_SEPARATOR . $adjunto['nombre_servidor'];
entregar_archivo($rutaArchivo, $adjunto['nombre_original']);


  