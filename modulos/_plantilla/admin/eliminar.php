<?php
/**
 * MÓDULO PLANTILLA · Baja
 * Explorador Vocacional UTBB
 *
 * Esta página NO imprime nada: recibe el POST, borra y redirige.
 * Tres cosas que la hacen segura y que se repiten en los diez módulos:
 *
 *   1. Solo acepta POST. Un borrado por GET lo puede disparar cualquier
 *      enlace o imagen de otro sitio.
 *   2. Verifica el token anti-CSRF.
 *   3. Verifica rol y pertenencia ANTES de borrar.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

const CARPETA_SUBIDAS = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

verificar_token();

$id = entero(post('id'));

if ($id === null) {
    mensaje('error', 'No se indicó qué registro eliminar.');
    redirigir('index.php');
}

$registro = plt_obtener($id);

if ($registro === null) {
    mensaje('error', 'El registro ya no existe.');
    redirigir('index.php');
}

requerir_carrera((int)$registro['carrera_id']);

try {
    plt_eliminar($id);

    if (!empty($registro['imagen'])) {
        @unlink(CARPETA_SUBIDAS . '/' . $registro['imagen']);
    }

    mensaje('exito', 'Registro eliminado.');
} catch (PDOException $e) {
    // Por ejemplo, cuando otra tabla lo referencia con ON DELETE RESTRICT.
    error_log('Error al eliminar: ' . $e->getMessage());
    mensaje('error', 'No se pudo eliminar: el registro tiene información asociada.');
}

redirigir('index.php');
