<?php
/**
 * NÚCLEO · Catálogo de los diez módulos
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Es la única lista de módulos del proyecto. La portada, el menú y el panel
 * la leen de aquí, de modo que agregar o renombrar un módulo se hace en un
 * solo lugar.
 *
 * 'area' determina el color con el que se pinta la tarjeta del módulo en la
 * portada. No es el área de una carrera: es una elección visual para que los
 * diez accesos se distingan entre sí.
 */

declare(strict_types=1);

const MODULOS = [
    'carreras' => [
        'clave'  => 'CAR',
        'nombre' => 'Explorador de carreras',
        'texto'  => 'Plan de estudios, perfil de egreso y laboratorios de cada carrera.',
        'equipo' => 'A-1',
        'area'   => 'tecnologia',
    ],
    'testimonios' => [
        'clave'  => 'TES',
        'nombre' => 'Testimonios',
        'texto'  => 'Lo que cuentan quienes ya estudian aquí y quienes ya egresaron.',
        'equipo' => 'A-2',
        'area'   => 'servicios',
    ],
    'test' => [
        'clave'  => 'TEST',
        'nombre' => 'Test vocacional',
        'texto'  => 'Responde el cuestionario y conoce las tres carreras más afines a ti.',
        'equipo' => 'A-3',
        'area'   => 'administrativa',
    ],
    'recorridos' => [
        'clave'  => 'REC',
        'nombre' => 'Recorridos virtuales',
        'texto'  => 'Recorre los laboratorios y talleres sin salir de tu casa.',
        'equipo' => 'A-4',
        'area'   => 'salud',
    ],
    'visitas' => [
        'clave'  => 'VIS',
        'nombre' => 'Agenda de visitas',
        'texto'  => 'Aparta tu lugar en una visita guiada al campus.',
        'equipo' => 'A-5',
        'area'   => 'agroindustrial',
    ],
    'portafolio' => [
        'clave'  => 'PRO',
        'nombre' => 'Portafolio de proyectos',
        'texto'  => 'Lo que construyen los estudiantes en cada carrera.',
        'equipo' => 'A-6',
        'area'   => 'tecnologia',
    ],
    'becas' => [
        'clave'  => 'BEC',
        'nombre' => 'Becas y apoyos',
        'texto'  => 'Programas disponibles, requisitos y solicitud en línea.',
        'equipo' => 'B-1',
        'area'   => 'agroindustrial',
    ],
    'asesoria' => [
        'clave'  => 'ASE',
        'nombre' => 'Asesoría a aspirantes',
        'texto'  => 'Pregunta a orientación educativa y da seguimiento a tu consulta.',
        'equipo' => 'B-2',
        'area'   => 'salud',
    ],
    'egresados' => [
        'clave'  => 'EMP',
        'nombre' => 'Egresados y bolsa de trabajo',
        'texto'  => 'Vacantes por carrera y dónde trabajan quienes ya terminaron.',
        'equipo' => 'B-3',
        'area'   => 'administrativa',
    ],
    'noticias' => [
        'clave'  => 'NOT',
        'nombre' => 'Noticias y eventos',
        'texto'  => 'Convocatorias, fechas de inscripción y eventos abiertos.',
        'equipo' => 'B-4',
        'area'   => 'servicios',
    ],
];

/**
 * Agrupación del menú principal.
 *
 * Diez enlaces no caben en una barra. Se agrupan por lo que el aspirante
 * viene a buscar, no por el equipo que los desarrolla. 'carreras' y 'visitas'
 * quedan fuera de los grupos porque van sueltos en el menú.
 */
const MENU_GRUPOS = [
    'Explora' => [
        'test'       => 'Test vocacional',
        'recorridos' => 'Recorridos virtuales',
        'portafolio' => 'Portafolio de proyectos',
    ],
    'Apoyos' => [
        'becas'    => 'Becas y apoyos',
        'asesoria' => 'Asesoría a aspirantes',
    ],
    'Comunidad' => [
        'testimonios' => 'Testimonios',
        'egresados'   => 'Egresados y bolsa de trabajo',
        'noticias'    => 'Noticias y eventos',
    ],
];

/** ¿El equipo ya entregó este módulo al repositorio? */
function modulo_existe(string $carpeta): bool
{
    return is_file(__DIR__ . '/../modulos/' . $carpeta . '/index.php');
}
