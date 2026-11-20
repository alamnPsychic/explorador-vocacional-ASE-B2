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
require_once __DIR__ . '/includes/entrada.php';

$id = entero(ase_entrada($_GET, 'id'));
if ($id === null) {
    no_encontrado('Archivo no especificado.');
}

$adjunto = ase_adjunto_obtener($id);
if ($adjunto === null) {
    no_encontrado('El archivo adjunto no existe.');
}

$carreraId = $adjunto['carrera_id'] === null ? null : (int)$adjunto['carrera_id'];
$esTitular = rol() === 'aspirante' && (int)$adjunto['aspirante_id'] === (int)usuario()['id'];
if (!$esTitular && !ase_puede_atender_conversacion($carreraId)) {
    http_response_code(403);
    exit('No tienes permiso para descargar este archivo adjunto.');
}

$rutaArchivo = ase_ruta_uploads() . DIRECTORY_SEPARATOR . $adjunto['nombre_servidor'];
$nombreVisible = preg_replace('/[\x00-\x1f\x7f"]/', '', $adjunto['nombre_original']);
header('Cache-Control: private, no-store');
entregar_archivo($rutaArchivo, $nombreVisible ?: 'adjunto');
