<?php
/**
 * MÓDULO ASE · Seguimiento de conversación del aspirante
 * Explorador Vocacional UTBB
 *
 * Verifica en el servidor que la conversación pertenezca al usuario en sesión
 * (o sea personal de orientación) para impedir acceso por manipulación de ?id=.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';

requerir_sesion();

require_once __DIR__ . '/includes/consultas.php';

$id = entero(get('id'));
if ($id === null) {
    no_encontrado('Conversación no especificada.');
}

$usuarioActual = usuario();
$esPersonal    = in_array(rol(), ['editor', 'coordinacion'], true);

// Si es aspirante, se fuerza a que solo pueda cargar SU propia conversación
$conversacion = ase_conversacion_obtener($id, $esPersonal ? null : (int)$usuarioActual['id']);

if ($conversacion === null) {
    no_encontrado('La conversación no existe o no tienes permiso para consultarla.');
}

$errores = [];
$cuerpoRespuesta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $accion = post('accion');

    if ($accion === 'cambiar_estado') {
        $nuevoEstado = post('nuevo_estado');
        if (in_array($nuevoEstado, ['abierta', 'cerrada'], true)) {
            ase_conversacion_cambiar_estado($id, $nuevoEstado);
            $textoMsj = $nuevoEstado === 'cerrada'
                ? 'Marcaste la conversación como cerrada.'
                : 'La conversación fue reabierta.';
            mensaje('exito', $textoMsj);
        }
        redirigir('conversacion.php?id=' . $id);
    }
}