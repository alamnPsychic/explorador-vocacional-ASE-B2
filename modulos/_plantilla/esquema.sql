-- =============================================================================
-- MÓDULO PLANTILLA · esquema.sql
-- Explorador Vocacional UTBB
--
-- Copia este archivo a tu módulo y sustituye el prefijo plt_ por el tuyo.
-- Se ejecuta DESPUÉS de sql/01-nucleo.sql.
--
-- REGLAS
--   · Todas tus tablas llevan tu prefijo. Sin excepción.
--   · Puedes apuntar a usuarios y a carreras. A ninguna tabla de otro módulo.
--   · Incluye datos de prueba suficientes para demostrar todas las funciones.
-- =============================================================================

USE explorador_vocacional;

SET NAMES utf8mb4;


-- -----------------------------------------------------------------------------
-- Entidad principal del módulo
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plt_elementos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carrera_id  INT UNSIGNED NOT NULL,
    titulo      VARCHAR(160) NOT NULL,
    resumen     VARCHAR(400) NULL,
    cuerpo      TEXT NULL,
    imagen      VARCHAR(80) NULL,          -- nombre generado por el servidor
    estado      ENUM('borrador','revision','publicado','rechazado','archivado')
                NOT NULL DEFAULT 'borrador',
    creado_por  INT UNSIGNED NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY ix_plt_elementos_estado (estado),
    KEY ix_plt_elementos_carrera (carrera_id),

    CONSTRAINT fk_plt_elementos_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_plt_elementos_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Entidad secundaria · ejemplo de relación uno a muchos
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plt_notas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    elemento_id INT UNSIGNED NOT NULL,
    texto       VARCHAR(400) NOT NULL,
    autor_id    INT UNSIGNED NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_plt_notas_elemento (elemento_id),

    CONSTRAINT fk_plt_notas_elemento
        FOREIGN KEY (elemento_id) REFERENCES plt_elementos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Bitácora de cambios de estado
-- Todo módulo con un proceso debe poder decir quién cambió qué y cuándo.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plt_historial (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    elemento_id   INT UNSIGNED NOT NULL,
    estado_previo VARCHAR(20) NULL,
    estado_nuevo  VARCHAR(20) NOT NULL,
    motivo        VARCHAR(300) NULL,
    usuario_id    INT UNSIGNED NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_plt_historial_elemento (elemento_id),

    CONSTRAINT fk_plt_historial_elemento
        FOREIGN KEY (elemento_id) REFERENCES plt_elementos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Datos de prueba
-- -----------------------------------------------------------------------------
INSERT INTO plt_elementos (carrera_id, titulo, resumen, estado) VALUES
    (1, 'Elemento publicado de ejemplo', 'Resumen breve que se muestra en la tarjeta.', 'publicado'),
    (1, 'Elemento en revisión',          'Esperando dictamen de la coordinación.',      'revision'),
    (3, 'Elemento en borrador',          'Todavía no se envía a revisión.',             'borrador'),
    (7, 'Elemento rechazado',            'Se devolvió con observaciones.',              'rechazado');
