-- ==============================================================================
-- PROYECTO EDUCATIVO HANDY - LENGUA DE SEÑAS BOLIVIANA (LSB) & INGLÉS
-- Script de Migración y Expansión Relacional a 26 Tablas (v2.0)
-- Motor: MariaDB (XAMPP) | Base de Datos: bd_handy
-- ==============================================================================

USE bd_handy;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. TABLA: roles
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id_rol` TINYINT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(30) NOT NULL UNIQUE,
    `descripcion` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id_rol`, `codigo`, `descripcion`) VALUES
(1, 'ADMINISTRADOR', 'Control total de la plataforma, usuarios y configuración global'),
(2, 'SUPERVISOR', 'Monitoreo pedagógico de estudiantes, reportes y tareas (Padres, Maestros, Terapeutas)'),
(3, 'GESTOR_CONTENIDOS', 'Creación y curaduría de vocabulario, señas LSB, multimedia y lecciones'),
(4, 'ESTUDIANTE', 'Niño que interactúa con misiones, minijuegos y reconocimiento por cámara')
ON DUPLICATE KEY UPDATE `descripcion`=VALUES(`descripcion`);

-- ------------------------------------------------------------------------------
-- 2. TABLA: avatares
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `avatares` (
    `id_avatar` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(80) NOT NULL,
    `descripcion` VARCHAR(150) NULL,
    `ruta_imagen_frontal` VARCHAR(255) NOT NULL,
    `precio_monedas` INT DEFAULT 0 NOT NULL,
    `requiere_nivel` SMALLINT DEFAULT 1 NOT NULL,
    `es_activo` BOOLEAN DEFAULT TRUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avatares` (`id_avatar`, `nombre`, `descripcion`, `ruta_imagen_frontal`, `precio_monedas`, `requiere_nivel`, `es_activo`) VALUES
(1, 'Cosmo el Conejito', 'Piloto espacial curioso y ágil', '/LSB/assets/img/avatares/cosmo.png', 0, 1, 1),
(2, 'Astro-Bot 3000', 'Robot explorador con manos biónicas para señas', '/LSB/assets/img/avatares/astrobot.png', 0, 1, 1),
(3, 'Estrellita Neón', 'Navegante cósmica experta en el abecedario', '/LSB/assets/img/avatares/estrella.png', 50, 2, 1),
(4, 'Capitán Nebulosa', 'Guardián de las constelaciones de vocabulario', '/LSB/assets/img/avatares/nebulosa.png', 100, 3, 1)
ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`);

-- ------------------------------------------------------------------------------
-- 3. TABLA EXISTENTE: usuarios (Modificación Aditiva No Destructiva)
-- ------------------------------------------------------------------------------
ALTER TABLE `usuarios`
    MODIFY COLUMN `rol` ENUM('admin','nino','supervisor','gestor_contenidos','estudiante') DEFAULT 'nino' NOT NULL;

-- Agregar columnas nuevas si no existen
SET @col_id_rol = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='usuarios' AND COLUMN_NAME='id_rol');
SET @sql_rol = IF(@col_id_rol = 0, 'ALTER TABLE `usuarios` ADD COLUMN `id_rol` TINYINT NULL AFTER `rol`;', 'SELECT 1;');
PREPARE stmt_rol FROM @sql_rol; EXECUTE stmt_rol; DEALLOCATE PREPARE stmt_rol;

SET @col_id_avatar = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='usuarios' AND COLUMN_NAME='id_avatar');
SET @sql_avatar = IF(@col_id_avatar = 0, 'ALTER TABLE `usuarios` ADD COLUMN `id_avatar` INT NULL AFTER `id_rol`;', 'SELECT 1;');
PREPARE stmt_avatar FROM @sql_avatar; EXECUTE stmt_avatar; DEALLOCATE PREPARE stmt_avatar;

SET @col_monedas = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='usuarios' AND COLUMN_NAME='monedas_actuales');
SET @sql_monedas = IF(@col_monedas = 0, 'ALTER TABLE `usuarios` ADD COLUMN `monedas_actuales` INT DEFAULT 0 NOT NULL AFTER `id_avatar`;', 'SELECT 1;');
PREPARE stmt_monedas FROM @sql_monedas; EXECUTE stmt_monedas; DEALLOCATE PREPARE stmt_monedas;

SET @col_estrellas = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='usuarios' AND COLUMN_NAME='estrellas_actuales');
SET @sql_estrellas = IF(@col_estrellas = 0, 'ALTER TABLE `usuarios` ADD COLUMN `estrellas_actuales` INT DEFAULT 0 NOT NULL AFTER `monedas_actuales`;', 'SELECT 1;');
PREPARE stmt_estrellas FROM @sql_estrellas; EXECUTE stmt_estrellas; DEALLOCATE PREPARE stmt_estrellas;

SET @col_estado = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='usuarios' AND COLUMN_NAME='estado_cuenta');
SET @sql_estado = IF(@col_estado = 0, 'ALTER TABLE `usuarios` ADD COLUMN `estado_cuenta` ENUM(\'activo\',\'inactivo\',\'bloqueado\') DEFAULT \'activo\' NOT NULL AFTER `estrellas_actuales`;', 'SELECT 1;');
PREPARE stmt_estado FROM @sql_estado; EXECUTE stmt_estado; DEALLOCATE PREPARE stmt_estado;

SET @col_ultimo = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='usuarios' AND COLUMN_NAME='ultimo_acceso');
SET @sql_ultimo = IF(@col_ultimo = 0, 'ALTER TABLE `usuarios` ADD COLUMN `ultimo_acceso` DATETIME NULL AFTER `estado_cuenta`;', 'SELECT 1;');
PREPARE stmt_ultimo FROM @sql_ultimo; EXECUTE stmt_ultimo; DEALLOCATE PREPARE stmt_ultimo;

-- Sincronizar datos existentes con roles normalizados y avatar inicial
UPDATE `usuarios` SET `id_rol` = 1 WHERE `rol` = 'admin' AND (`id_rol` IS NULL OR `id_rol` = 0);
UPDATE `usuarios` SET `id_rol` = 4 WHERE `rol` = 'nino' AND (`id_rol` IS NULL OR `id_rol` = 0);
UPDATE `usuarios` SET `id_avatar` = 1 WHERE `id_avatar` IS NULL;

-- ------------------------------------------------------------------------------
-- 4. TABLA: perfiles_estudiantes
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `perfiles_estudiantes` (
    `id_perfil` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL UNIQUE,
    `edad` TINYINT NOT NULL DEFAULT 8,
    `mano_dominante` ENUM('derecha','izquierda','ambidiestro') DEFAULT 'derecha' NOT NULL,
    `grado_escolar` VARCHAR(50) NULL,
    `nivel_hipoacusia` ENUM('leve','moderada','severa','profunda','oyente') DEFAULT 'profunda' NOT NULL,
    `notas_pedagogicas` TEXT NULL,
    CONSTRAINT `fk_pe_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poblar perfiles para los estudiantes actuales
INSERT IGNORE INTO `perfiles_estudiantes` (`id_usuario`, `edad`, `mano_dominante`, `grado_escolar`, `nivel_hipoacusia`, `notas_pedagogicas`)
SELECT `id_usuario`, 8, 'derecha', 'Primaria', 'profunda', 'Estudiante registrado inicialmente en la plataforma'
FROM `usuarios` WHERE `rol` = 'nino' OR `id_rol` = 4;

-- ------------------------------------------------------------------------------
-- 5. TABLA: supervisores_estudiantes
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `supervisores_estudiantes` (
    `id_supervision` INT AUTO_INCREMENT PRIMARY KEY,
    `id_supervisor` INT NOT NULL,
    `id_estudiante` INT NOT NULL,
    `tipo_vinculo` ENUM('padre','madre','tutor_legal','maestro','terapeuta') NOT NULL,
    `permite_reportes_correo` BOOLEAN DEFAULT TRUE NOT NULL,
    `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    UNIQUE KEY `uq_supervisor_estudiante` (`id_supervisor`, `id_estudiante`),
    CONSTRAINT `fk_se_supervisor` FOREIGN KEY (`id_supervisor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_se_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. TABLA: categorias
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
    `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_clave` VARCHAR(50) NOT NULL UNIQUE,
    `nombre_espanol` VARCHAR(100) NOT NULL,
    `nombre_ingles` VARCHAR(100) NOT NULL,
    `icono_interfaz` VARCHAR(255) NULL,
    `color_hex` VARCHAR(7) DEFAULT '#8b5cf6' NOT NULL,
    `orden_aparicion` SMALLINT DEFAULT 1 NOT NULL,
    `es_activo` BOOLEAN DEFAULT TRUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id_categoria`, `codigo_clave`, `nombre_espanol`, `nombre_ingles`, `icono_interfaz`, `color_hex`, `orden_aparicion`, `es_activo`) VALUES
(1, 'abecedario', 'Abecedario LSB', 'Alphabet', '🔤', '#8b5cf6', 1, 1),
(2, 'saludos', 'Saludos y Cortesía', 'Greetings', '👋', '#3b82f6', 2, 1),
(3, 'colores', 'Colores del Espacio', 'Colors', '🎨', '#ec4899', 3, 1),
(4, 'animales', 'Animales Fantásticos', 'Animals', '🐾', '#22c55e', 4, 1),
(5, 'familia', 'Mi Familia', 'Family', '👨‍👩‍👧', '#f59e0b', 5, 1),
(6, 'escuela', 'Objetos de la Escuela', 'School Objects', '🎒', '#06b6d4', 6, 1),
(7, 'numeros', 'Números Mágicos', 'Numbers', '🔢', '#a855f7', 7, 1)
ON DUPLICATE KEY UPDATE `nombre_espanol`=VALUES(`nombre_espanol`);

-- ------------------------------------------------------------------------------
-- 7. TABLA EXISTENTE: contenidos (Modificación Aditiva No Destructiva)
-- ------------------------------------------------------------------------------
ALTER TABLE `contenidos`
    MODIFY COLUMN `tipo_categoria` ENUM('abecedario','palabra_basica','frase_corta') NOT NULL;

SET @col_id_cat = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='contenidos' AND COLUMN_NAME='id_categoria');
SET @sql_cat = IF(@col_id_cat = 0, 'ALTER TABLE `contenidos` ADD COLUMN `id_categoria` INT NULL AFTER `id_contenido`;', 'SELECT 1;');
PREPARE stmt_cat FROM @sql_cat; EXECUTE stmt_cat; DEALLOCATE PREPARE stmt_cat;

SET @col_pronunc = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='contenidos' AND COLUMN_NAME='pronunciacion_fonetica');
SET @sql_pronunc = IF(@col_pronunc = 0, 'ALTER TABLE `contenidos` ADD COLUMN `pronunciacion_fonetica` VARCHAR(100) NULL AFTER `palabra_espanol`;', 'SELECT 1;');
PREPARE stmt_pronunc FROM @sql_pronunc; EXECUTE stmt_pronunc; DEALLOCATE PREPARE stmt_pronunc;

SET @col_dificultad = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='contenidos' AND COLUMN_NAME='nivel_dificultad');
SET @sql_dificultad = IF(@col_dificultad = 0, 'ALTER TABLE `contenidos` ADD COLUMN `nivel_dificultad` TINYINT DEFAULT 1 NOT NULL AFTER `pronunciacion_fonetica`;', 'SELECT 1;');
PREPARE stmt_dificultad FROM @sql_dificultad; EXECUTE stmt_dificultad; DEALLOCATE PREPARE stmt_dificultad;

SET @col_etiqueta_ia = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='contenidos' AND COLUMN_NAME='etiqueta_ia');
SET @sql_etiqueta_ia = IF(@col_etiqueta_ia = 0, 'ALTER TABLE `contenidos` ADD COLUMN `etiqueta_ia` VARCHAR(50) NULL AFTER `nivel_dificultad`;', 'SELECT 1;');
PREPARE stmt_etiqueta_ia FROM @sql_etiqueta_ia; EXECUTE stmt_etiqueta_ia; DEALLOCATE PREPARE stmt_etiqueta_ia;

-- Sincronizar categorías y etiquetas IA con registros existentes
UPDATE `contenidos` SET `id_categoria` = 1, `etiqueta_ia` = UPPER(TRIM(`letra_abecedario`)) WHERE `tipo_categoria` = 'abecedario';
UPDATE `contenidos` SET `id_categoria` = 2, `etiqueta_ia` = 'Hellow' WHERE `tipo_categoria` = 'palabra_basica' AND `palabra_ingles` = 'Hellow';
UPDATE `contenidos` SET `id_categoria` = 2, `etiqueta_ia` = 'Thank you' WHERE `tipo_categoria` = 'palabra_basica' AND `palabra_ingles` = 'Thank you';

-- ------------------------------------------------------------------------------
-- 8. TABLA: recursos_multimedia
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recursos_multimedia` (
    `id_recurso` INT AUTO_INCREMENT PRIMARY KEY,
    `tipo_recurso` ENUM('imagen_ilustrada','foto_seña_real','gif_animado','video_mp4','modelo_3d','audio_ingles') NOT NULL,
    `url_archivo` VARCHAR(255) NOT NULL,
    `formato` VARCHAR(10) NOT NULL,
    `texto_alternativo` VARCHAR(150) NULL,
    `tamano_bytes` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar rutas visuales existentes hacia recursos_multimedia sin duplicar
INSERT IGNORE INTO `recursos_multimedia` (`url_archivo`, `tipo_recurso`, `formato`, `texto_alternativo`)
SELECT DISTINCT `ruta_visual`, 'foto_seña_real', 'jpg', CONCAT('Seña de ', COALESCE(`letra_abecedario`, `palabra_espanol`))
FROM `contenidos` WHERE `ruta_visual` IS NOT NULL AND `ruta_visual` != '';

-- ------------------------------------------------------------------------------
-- 9. TABLA: contenidos_recursos
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contenidos_recursos` (
    `id_contenido_recurso` INT AUTO_INCREMENT PRIMARY KEY,
    `id_contenido` INT NOT NULL,
    `id_recurso` INT NOT NULL,
    `rol_recurso` ENUM('portada','guia_seña','animacion_demostrativa','objeto_real','audio_referencia') NOT NULL,
    `es_principal` BOOLEAN DEFAULT FALSE NOT NULL,
    CONSTRAINT `fk_cr_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cr_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `recursos_multimedia` (`id_recurso`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enlazar los recursos existentes con sus respectivos contenidos
INSERT IGNORE INTO `contenidos_recursos` (`id_contenido`, `id_recurso`, `rol_recurso`, `es_principal`)
SELECT c.`id_contenido`, r.`id_recurso`, 'guia_seña', 1
FROM `contenidos` c
INNER JOIN `recursos_multimedia` r ON c.`ruta_visual` = r.`url_archivo`;

-- ------------------------------------------------------------------------------
-- 10. TABLA: senas_lsb
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `senas_lsb` (
    `id_sena` INT AUTO_INCREMENT PRIMARY KEY,
    `id_contenido` INT NOT NULL UNIQUE,
    `configuracion_manual` VARCHAR(100) NOT NULL,
    `punto_articulacion` VARCHAR(100) NOT NULL,
    `tipo_movimiento` VARCHAR(100) NOT NULL,
    `orientacion_palma` VARCHAR(100) NOT NULL,
    `expresion_no_manual` VARCHAR(100) NULL,
    `descripcion_ejecucion` TEXT NOT NULL,
    CONSTRAINT `fk_senas_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. TABLA: niveles
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `niveles` (
    `id_nivel` INT AUTO_INCREMENT PRIMARY KEY,
    `id_categoria` INT NOT NULL,
    `numero_nivel` SMALLINT NOT NULL,
    `titulo_mision` VARCHAR(100) NOT NULL,
    `descripcion_mision` TEXT NULL,
    `estrellas_desbloqueo` INT DEFAULT 0 NOT NULL,
    `recompensa_monedas` INT DEFAULT 20 NOT NULL,
    `fondo_escena` VARCHAR(255) NULL,
    CONSTRAINT `fk_niveles_categorias` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `niveles` (`id_nivel`, `id_categoria`, `numero_nivel`, `titulo_mision`, `descripcion_mision`, `estrellas_desbloqueo`, `recompensa_monedas`, `fondo_escena`) VALUES
(1, 1, 1, 'Misión 1: Estación Espacial - Abecedario A-G', 'Aprende las primeras letras del alfabeto cósmico', 0, 20, '/LSB/assets/img/fondos/estacion1.jpg'),
(2, 1, 2, 'Misión 2: Estación Espacial - Abecedario H-N', 'Aprende el segundo tramo del alfabeto cósmico', 5, 30, '/LSB/assets/img/fondos/estacion2.jpg'),
(3, 1, 3, 'Misión 3: Estación Espacial - Abecedario Ñ-T', 'Domina las señas intermedias de la galaxia', 12, 40, '/LSB/assets/img/fondos/estacion3.jpg'),
(4, 1, 4, 'Misión 4: Estación Espacial - Abecedario U-Z', 'Conquista las últimas letras del abecedario espacial', 20, 50, '/LSB/assets/img/fondos/estacion4.jpg'),
(5, 2, 5, 'Misión 5: Planeta Saludos y Amigos', 'Comunícate con los habitantes del espacio en LSB e inglés', 25, 50, '/LSB/assets/img/fondos/planeta_saludos.jpg')
ON DUPLICATE KEY UPDATE `titulo_mision`=VALUES(`titulo_mision`);

-- ------------------------------------------------------------------------------
-- 12. TABLA: actividades
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `actividades` (
    `id_actividad` INT AUTO_INCREMENT PRIMARY KEY,
    `id_nivel` INT NOT NULL,
    `tipo_actividad` ENUM('leccion_guiada','entrenamiento_seña','minijuego','reto_evaluativo') NOT NULL,
    `titulo` VARCHAR(100) NOT NULL,
    `instruccion_visual` VARCHAR(255) NULL,
    `orden` SMALLINT NOT NULL,
    `puntos_base` INT DEFAULT 100 NOT NULL,
    CONSTRAINT `fk_actividades_niveles` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `actividades` (`id_actividad`, `id_nivel`, `tipo_actividad`, `titulo`, `instruccion_visual`, `orden`, `puntos_base`) VALUES
(1, 1, 'leccion_guiada', 'Aprende las Letras A, B, C y D', 'Observa la mano y repite la forma de la seña', 1, 100),
(2, 1, 'minijuego', 'Juego de Memoria de Letras', 'Encuentra las parejas correspondientes', 2, 150),
(3, 1, 'entrenamiento_seña', 'Cámara en Vivo: Demuestra tus Señas', 'Coloca tu mano frente a la cámara y haz la seña indicada', 3, 200)
ON DUPLICATE KEY UPDATE `titulo`=VALUES(`titulo`);

-- ------------------------------------------------------------------------------
-- 13. TABLA: actividades_contenidos
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `actividades_contenidos` (
    `id_actividad_contenido` INT AUTO_INCREMENT PRIMARY KEY,
    `id_actividad` INT NOT NULL,
    `id_contenido` INT NOT NULL,
    `orden_presentacion` SMALLINT DEFAULT 1 NOT NULL,
    CONSTRAINT `fk_ac_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `actividades` (`id_actividad`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ac_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `actividades_contenidos` (`id_actividad`, `id_contenido`, `orden_presentacion`)
SELECT 1, `id_contenido`, `id_contenido` FROM `contenidos` WHERE `tipo_categoria` = 'abecedario';

-- ------------------------------------------------------------------------------
-- 14. TABLA: preguntas
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `preguntas` (
    `id_pregunta` INT AUTO_INCREMENT PRIMARY KEY,
    `id_actividad` INT NULL,
    `id_contenido_asociado` INT NULL,
    `enunciado_visual` TEXT NOT NULL,
    `id_recurso_pregunta` INT NULL,
    `tipo_pregunta` ENUM('seleccion_seña','seleccion_palabra','completar','identificacion_camara') NOT NULL,
    `puntaje_acierto` INT DEFAULT 10 NOT NULL,
    CONSTRAINT `fk_preguntas_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `actividades` (`id_actividad`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_preguntas_contenido` FOREIGN KEY (`id_contenido_asociado`) REFERENCES `contenidos` (`id_contenido`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_preguntas_recurso` FOREIGN KEY (`id_recurso_pregunta`) REFERENCES `recursos_multimedia` (`id_recurso`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. TABLA: opciones_respuesta
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `opciones_respuesta` (
    `id_opcion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pregunta` INT NOT NULL,
    `id_recurso_visual` INT NULL,
    `texto_opcion` VARCHAR(100) NULL,
    `es_correcta` BOOLEAN DEFAULT FALSE NOT NULL,
    `retroalimentacion_positiva` VARCHAR(150) DEFAULT '¡Excelente!' NOT NULL,
    CONSTRAINT `fk_opciones_pregunta` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas` (`id_pregunta`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_opciones_recurso` FOREIGN KEY (`id_recurso_visual`) REFERENCES `recursos_multimedia` (`id_recurso`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. TABLA: evaluaciones_realizadas
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `evaluaciones_realizadas` (
    `id_evaluacion_realizada` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `id_nivel` INT NOT NULL,
    `calificacion_obtenida` DECIMAL(5,2) NOT NULL,
    `preguntas_totales` TINYINT NOT NULL,
    `aciertos` TINYINT NOT NULL,
    `errores` TINYINT NOT NULL,
    `tiempo_total_segundos` INT NOT NULL,
    `fecha_presentacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT `fk_er_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_er_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. TABLA: tipos_juegos
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipos_juegos` (
    `id_tipo_juego` TINYINT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(40) NOT NULL UNIQUE,
    `nombre` VARCHAR(80) NOT NULL,
    `descripcion_reglas` TEXT NOT NULL,
    `icono` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tipos_juegos` (`id_tipo_juego`, `codigo`, `nombre`, `descripcion_reglas`, `icono`) VALUES
(1, 'MEMORIA_VISUAL', 'Memoria Visual', 'Encuentra las parejas de cartas conectando la seña en LSB con su palabra en inglés', '🃏'),
(2, 'RELACIONAR_IMAGENES', 'Relacionar Seña e Imagen', 'Conecta mediante líneas visuales cada seña con el objeto o palabra correcta', '🔗'),
(3, 'COMPLETAR_PALABRAS', 'Completar la Palabra', 'Observa la seña y selecciona la letra faltante para armar la palabra en inglés', '✏️'),
(4, 'ORDENAR_LETRAS', 'Ordenar el Abecedario', 'Arrastra las tarjetas en desorden para formar la palabra inglesa correcta', '🔠'),
(5, 'RECONOCIMIENTO_CAMARA', 'Reconocimiento con Cámara IA', 'Realiza la seña en vivo frente a la webcam y el modelo de IA verificará tu precisión', '📷')
ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`);

-- ------------------------------------------------------------------------------
-- 18. TABLA: sesiones_juego
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sesiones_juego` (
    `id_sesion_juego` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `id_tipo_juego` TINYINT NOT NULL,
    `id_nivel` INT NULL,
    `puntuacion_obtenida` INT DEFAULT 0 NOT NULL,
    `combo_maximo` SMALLINT DEFAULT 0 NOT NULL,
    `estrellas_ganadas` TINYINT DEFAULT 0 NOT NULL,
    `monedas_ganadas` INT DEFAULT 0 NOT NULL,
    `tiempo_segundos` INT NOT NULL,
    `fecha_inicio` DATETIME NOT NULL,
    `fecha_fin` DATETIME NOT NULL,
    CONSTRAINT `fk_sj_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sj_tipojuego` FOREIGN KEY (`id_tipo_juego`) REFERENCES `tipos_juegos` (`id_tipo_juego`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sj_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. TABLA: juego_detalles_partida
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `juego_detalles_partida` (
    `id_detalle_partida` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_sesion_juego` INT NOT NULL,
    `id_contenido` INT NULL,
    `numero_intento_paso` SMALLINT NOT NULL,
    `fue_acierto` BOOLEAN NOT NULL,
    `tiempo_reaccion_ms` INT NOT NULL,
    `marca_tiempo` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT `fk_jdp_sesion` FOREIGN KEY (`id_sesion_juego`) REFERENCES `sesiones_juego` (`id_sesion_juego`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jdp_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. TABLA: medallas_logros
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medallas_logros` (
    `id_medalla` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_logro` VARCHAR(50) NOT NULL UNIQUE,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(200) NOT NULL,
    `icono_medalla` VARCHAR(255) NOT NULL,
    `recompensa_estrellas` INT DEFAULT 5 NOT NULL,
    `recompensa_monedas` INT DEFAULT 50 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medallas_logros` (`id_medalla`, `codigo_logro`, `nombre`, `descripcion`, `icono_medalla`, `recompensa_estrellas`, `recompensa_monedas`) VALUES
(1, 'PRIMER_DESPEGUE', 'Primer Despegue', 'Completaste tu primera misión espacial en HANDY', '🚀', 5, 50),
(2, 'MAESTRO_ABECEDARIO', 'Maestro del Abecedario', 'Dominaste las 27 letras del abecedario LSB', '🏆', 15, 150),
(3, 'OJO_DE_HALCON', 'Ojo de Halcón', 'Completaste el juego de memoria visual sin cometer errores', '🦅', 10, 100),
(4, 'SEÑA_PERFECTA', 'Seña Perfecta', 'Alcanzaste más del 95% de confianza en el detector de cámara', '✨', 10, 100),
(5, 'RACHA_CONSTELACION', 'Racha de la Constelación', 'Entrenaste 5 días consecutivos en HANDY', '🌟', 20, 200)
ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`);

-- ------------------------------------------------------------------------------
-- 21. TABLA: estudiantes_medallas
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estudiantes_medallas` (
    `id_estudiante_medalla` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `id_medalla` INT NOT NULL,
    `fecha_desbloqueo` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `destacada_en_perfil` BOOLEAN DEFAULT FALSE NOT NULL,
    UNIQUE KEY `uq_usuario_medalla` (`id_usuario`, `id_medalla`),
    CONSTRAINT `fk_em_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_em_medalla` FOREIGN KEY (`id_medalla`) REFERENCES `medallas_logros` (`id_medalla`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 22. TABLA: transacciones_recompensas
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transacciones_recompensas` (
    `id_transaccion` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `tipo_moneda` ENUM('estrella','moneda_galactica','experiencia_xp') NOT NULL,
    `monto` INT NOT NULL,
    `saldo_posterior` INT NOT NULL,
    `motivo` ENUM('completar_nivel','minijuego','reto_camara_ia','logro_medalla','compra_avatar','ajuste_supervisor') NOT NULL,
    `id_referencia_origen` INT NULL,
    `fecha_transaccion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT `fk_tr_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 23. TABLA: modelos_ia
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `modelos_ia` (
    `id_modelo` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_modelo` VARCHAR(100) NOT NULL,
    `version` VARCHAR(20) NOT NULL,
    `url_modelo_json` VARCHAR(255) NOT NULL,
    `url_metadata_json` VARCHAR(255) NOT NULL,
    `umbral_confianza_minimo` DECIMAL(3,2) DEFAULT 0.80 NOT NULL,
    `resolucion_camara_sugerida` VARCHAR(20) DEFAULT '640x480' NOT NULL,
    `es_activo` BOOLEAN DEFAULT TRUE NOT NULL,
    `fecha_entrenamiento` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modelos_ia` (`id_modelo`, `nombre_modelo`, `version`, `url_modelo_json`, `url_metadata_json`, `umbral_confianza_minimo`, `resolucion_camara_sugerida`, `es_activo`, `fecha_entrenamiento`) VALUES
(1, 'Teachable Machine LSB Image', 'v1.0.0', '/LSB/modelo_ia/model.json', '/LSB/modelo_ia/metadata.json', 0.80, '96x96', 1, '2026-06-03')
ON DUPLICATE KEY UPDATE `nombre_modelo`=VALUES(`nombre_modelo`);

-- ------------------------------------------------------------------------------
-- 24. TABLA: detecciones_ia_log
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detecciones_ia_log` (
    `id_deteccion` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `id_sesion_juego` INT NULL,
    `id_modelo` INT NOT NULL,
    `id_contenido_esperado` INT NOT NULL,
    `etiqueta_detectada` VARCHAR(50) NOT NULL,
    `nivel_confianza` DECIMAL(5,4) NOT NULL,
    `tiempo_deteccion_ms` INT NOT NULL,
    `resultado` ENUM('acierto','fallo','confianza_baja','fuera_de_cuadro') NOT NULL,
    `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT `fk_dia_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dia_sesion` FOREIGN KEY (`id_sesion_juego`) REFERENCES `sesiones_juego` (`id_sesion_juego`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_dia_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `modelos_ia` (`id_modelo`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_dia_contenido` FOREIGN KEY (`id_contenido_esperado`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 25. TABLA EXISTENTE: progreso (Modificación Aditiva No Destructiva)
-- ------------------------------------------------------------------------------
SET @col_prog_nivel = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='progreso' AND COLUMN_NAME='id_nivel');
SET @sql_prog_nivel = IF(@col_prog_nivel = 0, 'ALTER TABLE `progreso` ADD COLUMN `id_nivel` INT NULL AFTER `estado_completado`;', 'SELECT 1;');
PREPARE stmt_p1 FROM @sql_prog_nivel; EXECUTE stmt_p1; DEALLOCATE PREPARE stmt_p1;

SET @col_prog_punt = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='progreso' AND COLUMN_NAME='puntuacion_maxima');
SET @sql_prog_punt = IF(@col_prog_punt = 0, 'ALTER TABLE `progreso` ADD COLUMN `puntuacion_maxima` INT DEFAULT 0 NOT NULL AFTER `id_nivel`;', 'SELECT 1;');
PREPARE stmt_p2 FROM @sql_prog_punt; EXECUTE stmt_p2; DEALLOCATE PREPARE stmt_p2;

SET @col_prog_est = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='progreso' AND COLUMN_NAME='estrellas_obtenidas');
SET @sql_prog_est = IF(@col_prog_est = 0, 'ALTER TABLE `progreso` ADD COLUMN `estrellas_obtenidas` TINYINT DEFAULT 0 NOT NULL AFTER `puntuacion_maxima`;', 'SELECT 1;');
PREPARE stmt_p3 FROM @sql_prog_est; EXECUTE stmt_p3; DEALLOCATE PREPARE stmt_p3;

-- ------------------------------------------------------------------------------
-- 26. TABLA EXISTENTE: reportes (Modificación Aditiva No Destructiva)
-- ------------------------------------------------------------------------------
SET @col_rep_sup = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='reportes' AND COLUMN_NAME='id_supervisor_evaluador');
SET @sql_rep_sup = IF(@col_rep_sup = 0, 'ALTER TABLE `reportes` ADD COLUMN `id_supervisor_evaluador` INT NULL AFTER `tiempo_total_seg`;', 'SELECT 1;');
PREPARE stmt_r1 FROM @sql_rep_sup; EXECUTE stmt_r1; DEALLOCATE PREPARE stmt_r1;

SET @col_rep_prec = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='reportes' AND COLUMN_NAME='precision_global_porcentaje');
SET @sql_rep_prec = IF(@col_rep_prec = 0, 'ALTER TABLE `reportes` ADD COLUMN `precision_global_porcentaje` DECIMAL(5,2) DEFAULT 0.00 NOT NULL AFTER `id_supervisor_evaluador`;', 'SELECT 1;');
PREPARE stmt_r2 FROM @sql_rep_prec; EXECUTE stmt_r2; DEALLOCATE PREPARE stmt_r2;

SET @col_rep_senas = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='reportes' AND COLUMN_NAME='señas_dominadas_total');
SET @sql_rep_senas = IF(@col_rep_senas = 0, 'ALTER TABLE `reportes` ADD COLUMN `señas_dominadas_total` INT DEFAULT 0 NOT NULL AFTER `precision_global_porcentaje`;', 'SELECT 1;');
PREPARE stmt_r3 FROM @sql_rep_senas; EXECUTE stmt_r3; DEALLOCATE PREPARE stmt_r3;

SET @col_rep_obs = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='bd_handy' AND TABLE_NAME='reportes' AND COLUMN_NAME='observaciones_pedagogicas');
SET @sql_rep_obs = IF(@col_rep_obs = 0, 'ALTER TABLE `reportes` ADD COLUMN `observaciones_pedagogicas` TEXT NULL AFTER `señas_dominadas_total`;', 'SELECT 1;');
PREPARE stmt_r4 FROM @sql_rep_obs; EXECUTE stmt_r4; DEALLOCATE PREPARE stmt_r4;

SET FOREIGN_KEY_CHECKS = 1;
