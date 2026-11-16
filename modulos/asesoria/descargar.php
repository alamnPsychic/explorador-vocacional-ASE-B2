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