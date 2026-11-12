-- =============================================================================
-- MÓDULO ASE · Asesoría a aspirantes (Equipo B-2) · esquema.sql
-- Explorador Vocacional UTBB
-- Carrera asignada al equipo: Médico Cirujano (MED)
--
-- Se ejecuta DESPUÉS de sql/01-nucleo.sql.
-- =============================================================================

USE explorador_vocacional;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS ase_adjuntos;
DROP TABLE IF EXISTS ase_mensajes;
DROP TABLE IF EXISTS ase_conversaciones;
DROP TABLE IF EXISTS ase_faq;
DROP TABLE IF EXISTS ase_categorias;


-- -----------------------------------------------------------------------------
-- 1. Categorías de consulta y de preguntas frecuentes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ase_categorias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    orden       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    activa      TINYINT(1) NOT NULL DEFAULT 1,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_ase_categorias_activa (activa, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2. Preguntas frecuentes (FAQ) publicadas
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ase_faq (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    carrera_id   INT UNSIGNED NULL DEFAULT NULL,
    pregunta     VARCHAR(255) NOT NULL,
    respuesta    TEXT NOT NULL,
    orden        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    visible      TINYINT(1) NOT NULL DEFAULT 1,
    creado_por   INT UNSIGNED NULL DEFAULT NULL,
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY ix_ase_faq_categoria (categoria_id),
    KEY ix_ase_faq_carrera (carrera_id),
    KEY ix_ase_faq_visible (visible),

    CONSTRAINT fk_ase_faq_categoria
        FOREIGN KEY (categoria_id) REFERENCES ase_categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_ase_faq_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_ase_faq_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 3. Conversaciones (Canal de consulta entre aspirante y orientador)
-- Proceso obligatorio: abierta -> respondida -> cerrada (con reapertura)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ase_conversaciones (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aspirante_id   INT UNSIGNED NOT NULL,
    categoria_id   INT UNSIGNED NOT NULL,
    carrera_id     INT UNSIGNED NULL DEFAULT NULL,
    orientador_id  INT UNSIGNED NULL DEFAULT NULL,
    asunto         VARCHAR(200) NOT NULL,
    estado         ENUM('abierta','respondida','cerrada') NOT NULL DEFAULT 'abierta',
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY ix_ase_conv_aspirante (aspirante_id),
    KEY ix_ase_conv_categoria (categoria_id),
    KEY ix_ase_conv_carrera (carrera_id),
    KEY ix_ase_conv_estado (estado),

    CONSTRAINT fk_ase_conv_aspirante
        FOREIGN KEY (aspirante_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_ase_conv_categoria
        FOREIGN KEY (categoria_id) REFERENCES ase_categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_ase_conv_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_ase_conv_orientador
        FOREIGN KEY (orientador_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 4. Mensajes dentro de una conversación (comunicación asincrónica)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ase_mensajes (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversacion_id  INT UNSIGNED NOT NULL,
    usuario_id       INT UNSIGNED NOT NULL,
    cuerpo           TEXT NOT NULL,
    es_orientador    TINYINT(1) NOT NULL DEFAULT 0, -- 0 = aspirante, 1 = orientador
    publicado_faq_id INT UNSIGNED NULL DEFAULT NULL, -- Si esta respuesta se publicó como FAQ
    creado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_ase_msj_conversacion (conversacion_id),
    KEY ix_ase_msj_usuario (usuario_id),

    CONSTRAINT fk_ase_msj_conversacion
        FOREIGN KEY (conversacion_id) REFERENCES ase_conversaciones(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_ase_msj_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_ase_msj_faq
        FOREIGN KEY (publicado_faq_id) REFERENCES ase_faq(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 5. Archivos adjuntos en los mensajes de ambas partes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ase_adjuntos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mensaje_id      INT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_servidor VARCHAR(120) NOT NULL, -- nombre generado por el servidor (guardar_archivo)
    mime            VARCHAR(100) NOT NULL,
    tamano          INT UNSIGNED NOT NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_ase_adj_mensaje (mensaje_id),

    CONSTRAINT fk_ase_adj_mensaje
        FOREIGN KEY (mensaje_id) REFERENCES ase_mensajes(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Datos de prueba
-- -----------------------------------------------------------------------------
INSERT INTO ase_categorias (id, nombre, descripcion, orden, activa) VALUES
    (1, 'Proceso de Admisión y Fichas', 'Fechas de examen, requisitos de certificado de bachillerato y costos de ficha.', 1, 1),
    (2, 'Plan de Estudios y Prácticas', 'Duración por cuatrimestres, laboratorios, campos clínicos y estadías.', 2, 1),
    (3, 'Ingreso a Médico Cirujano', 'Perfil de ingreso, promedio mínimo, examen diagnóstico y dedicación de tiempo completo.', 3, 1),
    (4, 'Becas, Costos y Colegiaturas', 'Cuotas de inscripción cuatrimestral, seguro facultativo y convocatorias de apoyo.', 4, 1),
    (5, 'Vida Universitaria y Servicios', 'Transporte escolar, biblioteca, horarios y actividades culturales y deportivas.', 5, 1);

INSERT INTO ase_faq (categoria_id, carrera_id, pregunta, respuesta, orden, visible) VALUES
    (1, NULL, '¿Cuáles son los documentos necesarios para tramitar mi ficha de admisión?', 'Necesitas acta de nacimiento actualizada, CURP, constancia de estudios de bachillerato con promedio o certificado oficial, comprobante de domicilio y cubrir el arancel de derecho a examen de admisión.', 1, 1),
    (3, (SELECT id FROM carreras WHERE clave = 'MED' LIMIT 1), '¿Cuál es la duración e internado de la carrera de Médico Cirujano en la UTBB?', 'El programa académico de Médico Cirujano contempla formación básica y clínica en el campus y unidades de salud, seguida del año de Internado Rotatorio de Pregrado y un año de Servicio Social obligatorio.', 1, 1),
    (2, (SELECT id FROM carreras WHERE clave = 'MED' LIMIT 1), '¿Desde qué cuatrimestre se realizan prácticas en laboratorios y simuladores médicos?', 'Desde el primer ciclo tendrás acceso a los laboratorios de anatomía, histología y simulación clínica para desarrollar habilidades médicas en un entorno controlado antes de acudir a campos clínicos.', 2, 1),
    (4, NULL, '¿Cuentan con seguro médico estudiantil para los alumnos inscritos?', 'Sí, todos los estudiantes matriculados en la UTBB son dados de alta en el seguro facultativo del IMSS sin costo adicional durante toda su trayectoria académica.', 1, 1),
    (5, NULL, '¿En qué horario se imparten las clases de las carreras?', 'La modalidad es escolarizada presencial. En carreras del área de la salud como Médico Cirujano se requiere disponibilidad de horario mixto por las rotaciones y prácticas clínicas.', 1, 1);

INSERT INTO ase_conversaciones (id, aspirante_id, categoria_id, carrera_id, orientador_id, asunto, estado)
SELECT 1, u_asp.id, 3, c_med.id, u_ori.id, 'Duda sobre el examen de admisión para Médico Cirujano', 'respondida'
FROM usuarios u_asp
JOIN carreras c_med ON c_med.clave = 'MED'
JOIN usuarios u_ori ON u_ori.rol IN ('coordinacion', 'editor')
WHERE u_asp.rol = 'aspirante'
LIMIT 1;

INSERT INTO ase_conversaciones (id, aspirante_id, categoria_id, carrera_id, orientador_id, asunto, estado)
SELECT 2, u_asp.id, 1, c_med.id, NULL, 'Constancia de bachillerato en trámite', 'abierta'
FROM usuarios u_asp
JOIN carreras c_med ON c_med.clave = 'MED'
WHERE u_asp.rol = 'aspirante'
LIMIT 1;

INSERT INTO ase_mensajes (conversacion_id, usuario_id, cuerpo, es_orientador)
SELECT 1, aspirante_id, 'Hola, buenas tardes. Estoy por terminar la preparatoria en Bahía de Banderas y quiero saber si para ingresar a Médico Cirujano piden algún área específica de bachillerato o guía de estudio especial.', 0
FROM ase_conversaciones WHERE id = 1;

INSERT INTO ase_mensajes (conversacion_id, usuario_id, cuerpo, es_orientador)
SELECT 1, orientador_id, '¡Hola! Qué gusto saludarte. Puedes ingresar con cualquier certificado de bachillerato general o tecnológico, aunque te recomendamos repasar los módulos de Biología, Química y Premedicina de la guía oficial del examen.', 1
FROM ase_conversaciones WHERE id = 1 AND orientador_id IS NOT NULL;

INSERT INTO ase_mensajes (conversacion_id, usuario_id, cuerpo, es_orientador)
SELECT 2, aspirante_id, 'Disculpen, mi escuela me entrega el certificado hasta julio, ¿puedo subir una constancia con calificaciones hasta quinto semestre para sacar la ficha?', 0
FROM ase_conversaciones WHERE id = 2;