-- =============================================================================
-- EXPLORADOR VOCACIONAL UTBB
-- Esquema del núcleo · 01-nucleo.sql
--
-- Se ejecuta UNA VEZ, antes que cualquier esquema de módulo.
-- Desde phpMyAdmin: Importar > seleccionar este archivo.
-- Desde la consola:  mysql -u root < sql/01-nucleo.sql
--
-- Estas dos tablas son las únicas que los módulos pueden referenciar.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS explorador_vocacional
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE explorador_vocacional;

-- Indica al servidor que el texto de este archivo viene en UTF-8.
-- Sin esta línea, los acentos se guardan mal y en la página aparece
-- "ContadurÃ­a" en lugar de "Contaduría".
SET NAMES utf8mb4;


-- -----------------------------------------------------------------------------
-- carreras · las diez carreras de la universidad
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carreras (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(120) NOT NULL,
    clave      VARCHAR(10)  NOT NULL,
    area       ENUM('tecnologia','salud','administrativa','servicios','agroindustrial') NOT NULL,
    duracion   VARCHAR(40)  NOT NULL,
    modalidad  VARCHAR(40)  NOT NULL DEFAULT 'Escolarizada',
    activa     TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_carreras_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- usuarios · cuentas de la plataforma
--
-- contrasena guarda el RESULTADO de password_hash(), nunca la contraseña.
-- 255 caracteres porque el algoritmo puede cambiar y producir cadenas largas.
-- carrera_id solo tiene valor cuando el rol es 'editor'.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(120) NOT NULL,
    correo     VARCHAR(160) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol        ENUM('aspirante','editor','coordinacion') NOT NULL DEFAULT 'aspirante',
    carrera_id INT UNSIGNED NULL,
    activo     TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_correo (correo),
    KEY ix_usuarios_rol (rol),
    CONSTRAINT fk_usuarios_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Semilla · las diez carreras
-- El área determina el color de la página; ver el sistema de diseño.
-- -----------------------------------------------------------------------------
INSERT INTO carreras (nombre, clave, area, duracion, modalidad) VALUES
    ('Tecnologías de la Información', 'TIC',  'tecnologia',     '11 cuatrimestres', 'Escolarizada'),
    ('Mantenimiento Industrial',      'MAN',  'tecnologia',     '11 cuatrimestres', 'Escolarizada'),
    ('Médico Cirujano',               'MED',  'salud',          '12 semestres',     'Escolarizada'),
    ('Terapia Física',                'TER',  'salud',          '11 cuatrimestres', 'Escolarizada'),
    ('Contaduría',                    'CON',  'administrativa', '11 cuatrimestres', 'Escolarizada'),
    ('Mercadotecnia',                 'MER',  'administrativa', '11 cuatrimestres', 'Escolarizada'),
    ('Turismo',                       'TUR',  'servicios',      '11 cuatrimestres', 'Escolarizada'),
    ('Gastronomía',                   'GAS',  'servicios',      '11 cuatrimestres', 'Escolarizada'),
    ('Agricultura',                   'AGR',  'agroindustrial', '11 cuatrimestres', 'Escolarizada'),
    ('Energías Renovables',           'ENE',  'agroindustrial', '11 cuatrimestres', 'Escolarizada')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    area   = VALUES(area);

-- Los usuarios de prueba NO se crean aquí: las contraseñas deben pasar por
-- password_hash(). Ejecuta en el navegador:
--     http://localhost/explorador-vocacional/herramientas/crear-usuarios.php
