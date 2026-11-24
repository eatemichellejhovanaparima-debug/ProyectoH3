-- Migración Segura para Seguimiento de IA y Progreso Detallado
-- Conserva usuarios, contenidos, progreso y reportes
USE bd_handy;

-- 1. Agregar columnas adicionales a 'progreso' sin alterar datos existentes
ALTER TABLE `progreso`
    ADD COLUMN IF NOT EXISTS `aciertos` INT DEFAULT 0 NOT NULL AFTER `intentos`,
    ADD COLUMN IF NOT EXISTS `errores` INT DEFAULT 0 NOT NULL AFTER `aciertos`,
    ADD COLUMN IF NOT EXISTS `mejor_confianza` DECIMAL(5,4) DEFAULT 0.0000 NOT NULL AFTER `errores`;

-- 2. Agregar columnas adicionales a 'reportes'
ALTER TABLE `reportes`
    ADD COLUMN IF NOT EXISTS `aciertos_totales` INT DEFAULT 0 NOT NULL AFTER `puntuacion_total`,
    ADD COLUMN IF NOT EXISTS `errores_totales` INT DEFAULT 0 NOT NULL AFTER `aciertos_totales`,
    ADD COLUMN IF NOT EXISTS `precision_porcentaje` DECIMAL(5,2) DEFAULT 0.00 NOT NULL AFTER `tiempo_total_seg`,
    ADD COLUMN IF NOT EXISTS `senas_dominadas` INT DEFAULT 0 NOT NULL AFTER `precision_porcentaje`;

-- 3. Crear tabla de telemetría de intentos de cámara 'detecciones_ia'
CREATE TABLE IF NOT EXISTS `detecciones_ia` (
    `id_deteccion` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT(11) NOT NULL,
    `id_contenido` INT(11) NOT NULL,
    `letra_esperada` VARCHAR(50) NOT NULL,
    `letra_reconocida` VARCHAR(50) NOT NULL,
    `confianza` DECIMAL(5,4) NOT NULL,
    `tiempo_ms` INT NOT NULL DEFAULT 0,
    `resultado` ENUM('acierto','fallo','reintento') NOT NULL,
    `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_detecciones_usuario` (`id_usuario`),
    KEY `idx_detecciones_contenido` (`id_contenido`),
    CONSTRAINT `fk_det_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_det_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
