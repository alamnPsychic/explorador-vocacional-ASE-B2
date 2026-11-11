<?php
/**
 * NÚCLEO · Consultas de las tablas comunes
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Son las ÚNICAS tablas que un módulo puede consultar fuera de las suyas:
 * usuarios y carreras. Ningún módulo lee las tablas de otro módulo.
 */

declare(strict_types=1);

/** Las diez carreras activas, ordenadas por nombre. */
function carreras(): array
{
    static $cache = null;

    if ($cache === null) {
        $cache = bd()->query(
            'SELECT id, nombre, clave, area, duracion, modalidad
               FROM carreras
              WHERE activa = 1
           ORDER BY nombre'
        )->fetchAll();
    }

    return $cache;
}

/** Una carrera por su identificador, o null. */
function carrera(int $id): ?array
{
    $con = bd()->prepare(
        'SELECT id, nombre, clave, area, duracion, modalidad
           FROM carreras
          WHERE id = ? AND activa = 1'
    );
    $con->execute([$id]);

    return $con->fetch() ?: null;
}

/**
 * Clase CSS del área de una carrera, para ponerla en <body>.
 * Devuelve cadena vacía si el área no corresponde a ninguna de las cinco.
 */
function clase_area(?string $area): string
{
    $mapa = [
        'tecnologia'     => 'area-tecnologia',
        'salud'          => 'area-salud',
        'administrativa' => 'area-administrativa',
        'servicios'      => 'area-servicios',
        'agroindustrial' => 'area-agroindustrial',
    ];

    return $mapa[$area] ?? '';
}
