<?php
/**
 * MÓDULO ASE · Asesoría a aspirantes (Equipo B-2) · Consultas
 * Explorador Vocacional UTBB
 *
 * Todas las consultas del módulo viven aquí, separadas del HTML.
 * REGLA SIN EXCEPCIÓN: ningún dato variable se concatena a la cadena SQL.
 * Se usan marcadores ? y se pasan los valores en execute().
 */

declare(strict_types=1);

const ASE_POR_PAGINA = 10;

const ASE_ESTADOS = [
    'abierta'    => 'Abierta',
    'respondida' => 'Respondida',
    'cerrada'    => 'Cerrada',
];

/** Tipos permitidos en adjuntos de asesoría: imágenes y PDF (máx. 5 MB). */
const ASE_TIPOS_ADJUNTOS = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
const ASE_MAX_MB_ADJUNTO = 5;

/**
 * Clase de insignia del sistema de diseño (ev-base.css) para cada estado.
 */
function ase_clase_estado(string $estado): string
{
    $mapa = [
        'abierta'    => 'revision',
        'respondida' => 'publicado',
        'cerrada'    => 'archivado',
    ];
    $variante = $mapa[$estado] ?? 'borrador';
    return 'insignia insignia--' . $variante;
}

/** Ruta física de la carpeta uploads del módulo ASE. */
function ase_ruta_uploads(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
}

/**
 * Si el usuario adjuntó un archivo en $_FILES[$campo], lo valida y guarda
 * usando guardar_archivo() del núcleo, y devuelve los metadatos listos para BD.
 * Si no seleccionó archivo, devuelve null.
 */
function ase_procesar_adjunto_subido(?array $archivo): ?array
{
    if ($archivo === null || !isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $nombreServidor = guardar_archivo(
        $archivo,
        ase_ruta_uploads(),
        ASE_TIPOS_ADJUNTOS,
        ASE_MAX_MB_ADJUNTO
    );

    $rutaCompleta = ase_ruta_uploads() . DIRECTORY_SEPARATOR . $nombreServidor;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string)$finfo->file($rutaCompleta);

    return [
        'nombre_original' => basename((string)($archivo['name'] ?? $nombreServidor)),
        'nombre_servidor' => $nombreServidor,
        'mime'            => $mime,
        'tamano'          => (int)($archivo['size'] ?? filesize($rutaCompleta)),
    ];
}

/* =========================================================================
 * 1. CATEGORÍAS (CRUD COMPLETO #1)
 * ====================================================================== */

/** Lista todas las categorías (o solo las activas para la vista pública). */
function ase_categorias_listar(bool $soloActivas = false): array
{
    $donde = $soloActivas ? 'WHERE c.activa = 1' : '';
    $sql = "SELECT c.*,
                   (SELECT COUNT(*) FROM ase_faq f WHERE f.categoria_id = c.id) AS total_faq,
                   (SELECT COUNT(*) FROM ase_conversaciones cv WHERE cv.categoria_id = c.id) AS total_conversaciones
              FROM ase_categorias c
              $donde
          ORDER BY c.orden ASC, c.nombre ASC";

    $con = bd()->prepare($sql);
    $con->execute();
    return $con->fetchAll();
}

/** Obtiene una categoría por su ID. */
function ase_categoria_obtener(int $id): ?array
{
    $con = bd()->prepare('SELECT * FROM ase_categorias WHERE id = ?');
    $con->execute([$id]);
    return $con->fetch() ?: null;
}

/** Crea una nueva categoría. */
function ase_categoria_crear(array $datos): int
{
    $con = bd()->prepare(
        'INSERT INTO ase_categorias (nombre, descripcion, orden, activa)
         VALUES (?, ?, ?, ?)'
    );
    $con->execute([
        $datos['nombre'],
        $datos['descripcion'],
        (int)($datos['orden'] ?? 1),
        !empty($datos['activa']) ? 1 : 0,
    ]);
    return (int)bd()->lastInsertId();
}

/** Actualiza una categoría existente. */
function ase_categoria_actualizar(int $id, array $datos): void
{
    $con = bd()->prepare(
        'UPDATE ase_categorias
            SET nombre = ?, descripcion = ?, orden = ?, activa = ?
          WHERE id = ?'
    );
    $con->execute([
        $datos['nombre'],
        $datos['descripcion'],
        (int)($datos['orden'] ?? 1),
        !empty($datos['activa']) ? 1 : 0,
        $id,
    ]);
}

/**
 * Elimina una categoría si no tiene FAQs ni conversaciones vinculadas.
 * Devuelve true si se eliminó, o false si está en uso.
 */
function ase_categoria_eliminar(int $id): bool
{
    $con = bd()->prepare('SELECT COUNT(*) FROM ase_faq WHERE categoria_id = ?');
    $con->execute([$id]);
    if ((int)$con->fetchColumn() > 0) {
        return false;
    }

    $con = bd()->prepare('SELECT COUNT(*) FROM ase_conversaciones WHERE categoria_id = ?');
    $con->execute([$id]);
    if ((int)$con->fetchColumn() > 0) {
        return false;
    }

    $con = bd()->prepare('DELETE FROM ase_categorias WHERE id = ?');
    $con->execute([$id]);
    return true;
}

/* =========================================================================
 * 2. PREGUNTAS FRECUENTES - FAQ (CRUD COMPLETO #2 + BUSCADOR Y FILTROS)
 * ====================================================================== */

/**
 * Lista preguntas frecuentes con búsqueda por texto, filtro de categoría y carrera.
 */
function ase_faq_listar(
    string $busqueda = '',
    ?int $categoriaId = null,
    ?int $carreraId = null,
    bool $soloVisibles = true
): array {
    $condiciones = [];
    $valores     = [];

    if ($soloVisibles) {
        $condiciones[] = 'f.visible = 1';
        $condiciones[] = 'cat.activa = 1';
    }

    if ($busqueda !== '') {
        $condiciones[] = '(f.pregunta LIKE ? OR f.respuesta LIKE ?)';
        $comodin       = '%' . $busqueda . '%';
        $valores[]     = $comodin;
        $valores[]     = $comodin;
    }

    if ($categoriaId !== null && $categoriaId > 0) {
        $condiciones[] = 'f.categoria_id = ?';
        $valores[]     = $categoriaId;
    }

    if ($carreraId !== null && $carreraId > 0) {
        $condiciones[] = '(f.carrera_id = ? OR f.carrera_id IS NULL)';
        $valores[]     = $carreraId;
    }

    $donde = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    $sql = "SELECT f.*,
                   cat.nombre AS categoria_nombre,
                   car.nombre AS carrera_nombre,
                   car.clave  AS carrera_clave
              FROM ase_faq f
              JOIN ase_categorias cat ON cat.id = f.categoria_id
         LEFT JOIN carreras car       ON car.id = f.carrera_id
              $donde
          ORDER BY cat.orden ASC, f.orden ASC, f.id DESC";

    $con = bd()->prepare($sql);
    $con->execute($valores);
    return $con->fetchAll();
}

/** Obtiene una FAQ por su ID. */
function ase_faq_obtener(int $id): ?array
{
    $con = bd()->prepare(
        'SELECT f.*, cat.nombre AS categoria_nombre, car.nombre AS carrera_nombre
           FROM ase_faq f
           JOIN ase_categorias cat ON cat.id = f.categoria_id
      LEFT JOIN carreras car       ON car.id = f.carrera_id
          WHERE f.id = ?'
    );
    $con->execute([$id]);
    return $con->fetch() ?: null;
}

/** Crea una nueva pregunta frecuente. */
function ase_faq_crear(array $datos, ?int $usuarioId): int
{
    $con = bd()->prepare(
        'INSERT INTO ase_faq (categoria_id, carrera_id, pregunta, respuesta, orden, visible, creado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $con->execute([
        (int)$datos['categoria_id'],
        !empty($datos['carrera_id']) ? (int)$datos['carrera_id'] : null,
        $datos['pregunta'],
        $datos['respuesta'],
        (int)($datos['orden'] ?? 1),
        !empty($datos['visible']) ? 1 : 0,
        $usuarioId,
    ]);
    return (int)bd()->lastInsertId();
}

/** Actualiza una pregunta frecuente existente. */
function ase_faq_actualizar(int $id, array $datos): void
{
    $con = bd()->prepare(
        'UPDATE ase_faq
            SET categoria_id = ?, carrera_id = ?, pregunta = ?, respuesta = ?, orden = ?, visible = ?
          WHERE id = ?'
    );
    $con->execute([
        (int)$datos['categoria_id'],
        !empty($datos['carrera_id']) ? (int)$datos['carrera_id'] : null,
        $datos['pregunta'],
        $datos['respuesta'],
        (int)($datos['orden'] ?? 1),
        !empty($datos['visible']) ? 1 : 0,
        $id,
    ]);
}

/** Elimina una pregunta frecuente. */
function ase_faq_eliminar(int $id): void
{
    $con = bd()->prepare('DELETE FROM ase_faq WHERE id = ?');
    $con->execute([$id]);
}

/**
 * Publica la respuesta de un orientador directamente como Pregunta Frecuente (FAQ)
 * y enlaza el mensaje original con el nuevo registro en ase_faq.
 */
function ase_publicar_mensaje_como_faq(int $mensajeId, array $datosFaq, int $usuarioId): int
{
    $pdo = bd();
    $pdo->beginTransaction();

    try {
        $faqId = ase_faq_crear($datosFaq, $usuarioId);

        $con = $pdo->prepare('UPDATE ase_mensajes SET publicado_faq_id = ? WHERE id = ?');
        $con->execute([$faqId, $mensajeId]);

        $pdo->commit();
        return $faqId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* =========================================================================
 * 3. CONVERSACIONES, MENSAJES Y ADJUNTOS (PROCESO DE NEGOCIO)
 * ====================================================================== */

/**
 * Lista paginada de conversaciones para la bandeja del orientador o vista de aspirante.
 */
function ase_conversaciones_listar(
    string $busqueda = '',
    string $estado = '',
    ?int $categoriaId = null,
    ?int $carreraId = null,
    ?int $aspiranteId = null,
    int $pagina = 1
): array {
    $condiciones = [];
    $valores     = [];

    if ($busqueda !== '') {
        $condiciones[] = '(cv.asunto LIKE ? OR u.nombre LIKE ? OR u.correo LIKE ?)';
        $comodin       = '%' . $busqueda . '%';
        $valores[]     = $comodin;
        $valores[]     = $comodin;
        $valores[]     = $comodin;
    }

    if ($estado !== '' && array_key_exists($estado, ASE_ESTADOS)) {
        $condiciones[] = 'cv.estado = ?';
        $valores[]     = $estado;
    }

    if ($categoriaId !== null && $categoriaId > 0) {
        $condiciones[] = 'cv.categoria_id = ?';
        $valores[]     = $categoriaId;
    }

    if ($carreraId !== null && $carreraId > 0) {
        $condiciones[] = 'cv.carrera_id = ?';
        $valores[]     = $carreraId;
    }

    if ($aspiranteId !== null && $aspiranteId > 0) {
        $condiciones[] = 'cv.aspirante_id = ?';
        $valores[]     = $aspiranteId;
    }

    $donde = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    $con = bd()->prepare(
        "SELECT COUNT(*)
           FROM ase_conversaciones cv
           JOIN usuarios u ON u.id = cv.aspirante_id
           $donde"
    );
    $con->execute($valores);
    $total = (int)$con->fetchColumn();

    $pagina = max(1, $pagina);
    $desde  = ($pagina - 1) * ASE_POR_PAGINA;
    $limite = ASE_POR_PAGINA;

    $sql = "SELECT cv.*,
                   u.nombre   AS aspirante_nombre,
                   u.correo   AS aspirante_correo,
                   ori.nombre AS orientador_nombre,
                   cat.nombre AS categoria_nombre,
                   car.nombre AS carrera_nombre,
                   car.clave  AS carrera_clave,
                   (SELECT COUNT(*) FROM ase_mensajes m WHERE m.conversacion_id = cv.id) AS total_mensajes
              FROM ase_conversaciones cv
              JOIN usuarios u         ON u.id   = cv.aspirante_id
              JOIN ase_categorias cat ON cat.id = cv.categoria_id
         LEFT JOIN carreras car       ON car.id = cv.carrera_id
         LEFT JOIN usuarios ori       ON ori.id = cv.orientador_id
              $donde
          ORDER BY FIELD(cv.estado, 'abierta', 'respondida', 'cerrada'), cv.actualizado_en DESC
             LIMIT $limite OFFSET $desde";

    $con = bd()->prepare($sql);
    $con->execute($valores);

    return [
        'filas'   => $con->fetchAll(),
        'total'   => $total,
        'pagina'  => $pagina,
        'paginas' => (int)ceil($total / ASE_POR_PAGINA),
    ];
}

/**
 * Obtiene una conversación por su ID.
 * Si se pasa $aspiranteId, verifica en la misma consulta que le pertenezca (evita IDOR).
 */
function ase_conversacion_obtener(int $id, ?int $aspiranteId = null): ?array
{
    $sql = 'SELECT cv.*,
                   u.nombre   AS aspirante_nombre,
                   u.correo   AS aspirante_correo,
                   ori.nombre AS orientador_nombre,
                   cat.nombre AS categoria_nombre,
                   car.nombre AS carrera_nombre,
                   car.clave  AS carrera_clave
              FROM ase_conversaciones cv
              JOIN usuarios u         ON u.id   = cv.aspirante_id
              JOIN ase_categorias cat ON cat.id = cv.categoria_id
         LEFT JOIN carreras car       ON car.id = cv.carrera_id
         LEFT JOIN usuarios ori       ON ori.id = cv.orientador_id
             WHERE cv.id = ?';

    $valores = [$id];

    if ($aspiranteId !== null) {
        $sql .= ' AND cv.aspirante_id = ?';
        $valores[] = $aspiranteId;
    }

    $con = bd()->prepare($sql);
    $con->execute($valores);
    return $con->fetch() ?: null;
}

/**
 * Lista todos los mensajes de una conversación en orden cronológico,
 * incluyendo sus archivos adjuntos.
 */
function ase_mensajes_listar(int $conversacionId): array
{
    $con = bd()->prepare(
        'SELECT m.*, u.nombre AS autor_nombre, u.rol AS autor_rol
           FROM ase_mensajes m
           JOIN usuarios u ON u.id = m.usuario_id
          WHERE m.conversacion_id = ?
       ORDER BY m.creado_en ASC, m.id ASC'
    );
    $con->execute([$conversacionId]);
    $mensajes = $con->fetchAll();

    if (!$mensajes) {
        return [];
    }

    $conAdj = bd()->prepare('SELECT * FROM ase_adjuntos WHERE mensaje_id = ? ORDER BY id ASC');
    foreach ($mensajes as &$msj) {
        $conAdj->execute([(int)$msj['id']]);
        $msj['adjuntos'] = $conAdj->fetchAll();
    }
    unset($msj);

    return $mensajes;
}

/** Obtiene un mensaje específico junto con datos de su conversación. */
function ase_mensaje_obtener(int $mensajeId): ?array
{
    $con = bd()->prepare(
        'SELECT m.*, cv.categoria_id, cv.carrera_id, cv.asunto, cv.aspirante_id
           FROM ase_mensajes m
           JOIN ase_conversaciones cv ON cv.id = m.conversacion_id
          WHERE m.id = ?'
    );
    $con->execute([$mensajeId]);
    return $con->fetch() ?: null;
}

/**
 * Crea una nueva conversación con su mensaje inicial y adjunto opcional.
 */
function ase_conversacion_crear(array $datos, int $aspiranteId, ?array $archivoGuardado = null): int
{
    $pdo = bd();
    $pdo->beginTransaction();

    try {
        $con = $pdo->prepare(
            "INSERT INTO ase_conversaciones (aspirante_id, categoria_id, carrera_id, asunto, estado)
             VALUES (?, ?, ?, ?, 'abierta')"
        );
        $con->execute([
            $aspiranteId,
            (int)$datos['categoria_id'],
            !empty($datos['carrera_id']) ? (int)$datos['carrera_id'] : null,
            $datos['asunto'],
        ]);
        $conversacionId = (int)$pdo->lastInsertId();

        $conMsj = $pdo->prepare(
            'INSERT INTO ase_mensajes (conversacion_id, usuario_id, cuerpo, es_orientador)
             VALUES (?, ?, ?, 0)'
        );
        $conMsj->execute([
            $conversacionId,
            $aspiranteId,
            $datos['cuerpo'],
        ]);
        $mensajeId = (int)$pdo->lastInsertId();

        if ($archivoGuardado !== null) {
            ase_adjunto_registrar($mensajeId, $archivoGuardado);
        }

        $pdo->commit();
        return $conversacionId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Agrega un mensaje a una conversación existente y actualiza el estado del proceso:
 * - Si responde el orientador: pasa a 'respondida' y registra orientador_id.
 * - Si responde el aspirante: pasa a 'abierta' (permite reapertura o seguimiento).
 */
function ase_mensaje_agregar(
    int $conversacionId,
    int $usuarioId,
    string $cuerpo,
    bool $esOrientador,
    ?array $archivoGuardado = null
): int {
    $pdo = bd();
    $pdo->beginTransaction();

    try {
        $conMsj = $pdo->prepare(
            'INSERT INTO ase_mensajes (conversacion_id, usuario_id, cuerpo, es_orientador)
             VALUES (?, ?, ?, ?)'
        );
        $conMsj->execute([
            $conversacionId,
            $usuarioId,
            $cuerpo,
            $esOrientador ? 1 : 0,
        ]);
        $mensajeId = (int)$pdo->lastInsertId();

        if ($archivoGuardado !== null) {
            ase_adjunto_registrar($mensajeId, $archivoGuardado);
        }

        if ($esOrientador) {
            $conUpd = $pdo->prepare(
                "UPDATE ase_conversaciones
                    SET estado = 'respondida', orientador_id = ?, actualizado_en = CURRENT_TIMESTAMP
                  WHERE id = ?"
            );
            $conUpd->execute([$usuarioId, $conversacionId]);
        } else {
            $conUpd = $pdo->prepare(
                "UPDATE ase_conversaciones
                    SET estado = 'abierta', actualizado_en = CURRENT_TIMESTAMP
                  WHERE id = ?"
            );
            $conUpd->execute([$conversacionId]);
        }

        $pdo->commit();
        return $mensajeId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Cambia explícitamente el estado de una conversación ('abierta', 'respondida', 'cerrada').
 */
function ase_conversacion_cambiar_estado(int $conversacionId, string $nuevoEstado): void
{
    if (!array_key_exists($nuevoEstado, ASE_ESTADOS)) {
        throw new InvalidArgumentException('Estado de conversación no válido.');
    }

    $con = bd()->prepare(
        'UPDATE ase_conversaciones
            SET estado = ?, actualizado_en = CURRENT_TIMESTAMP
          WHERE id = ?'
    );
    $con->execute([$nuevoEstado, $conversacionId]);
}

/** Registra los metadatos de un archivo adjunto en la base de datos. */
function ase_adjunto_registrar(int $mensajeId, array $archivo): int
{
    $con = bd()->prepare(
        'INSERT INTO ase_adjuntos (mensaje_id, nombre_original, nombre_servidor, mime, tamano)
         VALUES (?, ?, ?, ?, ?)'
    );
    $con->execute([
        $mensajeId,
        $archivo['nombre_original'],
        $archivo['nombre_servidor'],
        $archivo['mime'],
        (int)$archivo['tamano'],
    ]);
    return (int)bd()->lastInsertId();
}

/**
 * Obtiene un archivo adjunto junto con el aspirante_id y carrera_id de la conversación
 * para verificar permisos antes de entregar la descarga restringida.
 */
function ase_adjunto_obtener(int $id): ?array
{
    $con = bd()->prepare(
        'SELECT a.*, cv.aspirante_id, cv.carrera_id, cv.id AS conversacion_id
           FROM ase_adjuntos a
           JOIN ase_mensajes m        ON m.id  = a.mensaje_id
           JOIN ase_conversaciones cv ON cv.id = m.conversacion_id
          WHERE a.id = ?'
    );
    $con->execute([$id]);
    return $con->fetch() ?: null;
}