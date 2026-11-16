<?php
/**
 * MÓDULO ASE · Asesoría a aspirantes (Equipo B-2) · Pantalla pública principal
 * Explorador Vocacional UTBB
 *
 * Incluye:
 *  1. Preguntas frecuentes (FAQ) por categoría y carrera con buscador.
 *  2. Formulario para enviar una nueva consulta (con adjunto opcional).
 *  3. Listado de conversaciones propias del aspirante en sesión.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';

iniciar_sesion();

// --- Filtros de FAQ (GET) --------------------------------------------------
$busqueda    = get('q');
$categoriaId = entero(get('categoria'));
$carreraId   = entero(get('carrera'));

// --- Formulario de Nueva Consulta (POST) -----------------------------------
$datosConsulta = [
    'categoria_id' => '',
    'carrera_id'   => '',
    'asunto'       => '',
    'cuerpo'       => '',
];
$errores = [];