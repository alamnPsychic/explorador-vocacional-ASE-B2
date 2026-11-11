<?php
/**
 * MÓDULO PLANTILLA · Consultas
 * Explorador Vocacional UTBB
 *
 * Todas las consultas del módulo viven aquí, no mezcladas con el HTML.
 * Una página que imprime también arma consultas se vuelve imposible de revisar.
 *
 * REGLA SIN EXCEPCIÓN: ningún dato variable se concatena a la cadena SQL.
 * Se usan marcadores ? y se pasan los valores en execute().
 */

declare(strict_types=1);

const PLT_POR_PAGINA = 10;

const PLT_ESTADOS = [
    'borrador'  => 'Borrador',
    'revision'  => 'En revisión',
    'publicado' => 'Publicado',
    'rechazado' => 'Rechazado',
    'archivado' => 'Archivado',
];

/** Clase de insignia del sistema de diseño que corresponde a cada estado. */
function plt_clase_estado(string $estado): string
{
    return 'insignia insignia--' . (array_key_exists($estado, PLT_ESTADOS) ? $estado : 'borrador');
}

/* -------------------------------------------------------------------------
 * CONSULTA
 * ---------------------------------------------------------------------- */

/**
 * Lista paginada con búsqueda y filtros.
 *
 * El filtrado se arma en dos piezas: $condiciones son fragmentos FIJOS de SQL
 * escritos por nosotros, y $valores son los datos del usuario. Los dos nunca
 * se mezclan en la misma cadena.
 */
function plt_listar(string $busqueda = '', string $estado = '', ?int $carreraId = null, int $pagina = 1): array
{
    $condiciones = [];
    $valores     = [];

    if ($busqueda !== '') {
        $condiciones[] = '(e.titulo LIKE ? OR e.resumen LIKE ?)';
        $comodin = '%' . $busqueda . '%';
        $valores[] = $comodin;
        $valores[] = $comodin;
    }

    if ($estado !== '' && array_key_exists($estado, PLT_ESTADOS)) {
        $condiciones[] = 'e.estado = ?';
        $valores[]     = $estado;
    }

    if ($carreraId !== null) {
        $condiciones[] = 'e.carrera_id = ?';
        $valores[]     = $carreraId;
    }

    $donde = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    // Total, para calcular las páginas.
    $con = bd()->prepare("SELECT COUNT(*) FROM plt_elementos e $donde");
    $con->execute($valores);
    $total = (int)$con->fetchColumn();

    // LIMIT y OFFSET se calculan como enteros nuestros, no como texto del
    // usuario: por eso pasan por (int) y max().
    $pagina  = max(1, $pagina);
    $desde   = ($pagina - 1) * PLT_POR_PAGINA;
    $limite  = PLT_POR_PAGINA;

    $sql = "SELECT e.id, e.titulo, e.resumen, e.imagen, e.estado, e.actualizado_en,
                   c.nombre AS carrera, c.area
              FROM plt_elementos e
              JOIN carreras c ON c.id = e.carrera_id
              $donde
          ORDER BY e.actualizado_en DESC
             LIMIT $limite OFFSET $desde";

    $con = bd()->prepare($sql);
    $con->execute($valores);

    return [
        'filas'    => $con->fetchAll(),
        'total'    => $total,
        'pagina'   => $pagina,
        'paginas'  => (int)ceil($total / PLT_POR_PAGINA),
    ];
}

/** Un elemento por su identificador, o null. */
function plt_obtener(int $id): ?array
{
    $con = bd()->prepare(
        'SELECT e.*, c.nombre AS carrera, c.area
           FROM plt_elementos e
           JOIN carreras c ON c.id = e.carrera_id
          WHERE e.id = ?'
    );
    $con->execute([$id]);

    return $con->fetch() ?: null;
}

/* -------------------------------------------------------------------------
 * ESCRITURA
 * ---------------------------------------------------------------------- */

/** Alta. Devuelve el identificador del registro creado. */
function plt_crear(array $datos, ?int $usuarioId): int
{
    $con = bd()->prepare(
        'INSERT INTO plt_elementos (carrera_id, titulo, resumen, cuerpo, imagen, estado, creado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $con->execute([
        $datos['carrera_id'],
        $datos['titulo'],
        $datos['resumen'],
        $datos['cuerpo'],
        $datos['imagen'],
        $datos['estado'],
        $usuarioId,
    ]);

    return (int)bd()->lastInsertId();
}

/** Modificación. */
function plt_actualizar(int $id, array $datos): void
{
    $con = bd()->prepare(
        'UPDATE plt_elementos
            SET carrera_id = ?, titulo = ?, resumen = ?, cuerpo = ?, imagen = ?
          WHERE id = ?'
    );

    $con->execute([
        $datos['carrera_id'],
        $datos['titulo'],
        $datos['resumen'],
        $datos['cuerpo'],
        $datos['imagen'],
        $id,
    ]);
}

/** Baja. */
function plt_eliminar(int $id): void
{
    $con = bd()->prepare('DELETE FROM plt_elementos WHERE id = ?');
    $con->execute([$id]);
}

/**
 * Cambio de estado con registro en la bitácora.
 *
 * Las dos escrituras van dentro de una transacción: o quedan las dos o no
 * queda ninguna. Un cambio de estado sin su registro deja el historial
 * mintiendo.
 */
function plt_cambiar_estado(int $id, string $nuevo, ?string $motivo, ?int $usuarioId): void
{
    if (!array_key_exists($nuevo, PLT_ESTADOS)) {
        throw new InvalidArgumentException('Estado no válido.');
    }

    $pdo = bd();
    $pdo->beginTransaction();

    try {
        $con = $pdo->prepare('SELECT estado FROM plt_elementos WHERE id = ? FOR UPDATE');
        $con->execute([$id]);
        $previo = $con->fetchColumn();

        if ($previo === false) {
            throw new RuntimeException('El registro no existe.');
        }

        $con = $pdo->prepare('UPDATE plt_elementos SET estado = ? WHERE id = ?');
        $con->execute([$nuevo, $id]);

        $con = $pdo->prepare(
            'INSERT INTO plt_historial (elemento_id, estado_previo, estado_nuevo, motivo, usuario_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $con->execute([$id, $previo, $nuevo, $motivo, $usuarioId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Historial de un elemento, del más reciente al más antiguo. */
function plt_historial(int $id): array
{
    $con = bd()->prepare(
        'SELECT h.*, u.nombre AS usuario
           FROM plt_historial h
      LEFT JOIN usuarios u ON u.id = h.usuario_id
          WHERE h.elemento_id = ?
       ORDER BY h.creado_en DESC'
    );
    $con->execute([$id]);

    return $con->fetchAll();
}
