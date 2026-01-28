-- VMC Sistema de Gestión - Base de Datos
-- Configuración inicial de la base de datos

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS vmcserve_vmc CHARACTER SET utf8 COLLATE utf8_spanish_ci;
USE vmcserve_vmc;

-- Tabla usuarios para autenticación
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(50) NOT NULL,
  `pass` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Tabla clientes
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8_spanish_ci DEFAULT NULL,
  `razon_social` varchar(50) COLLATE utf8_spanish_ci DEFAULT NULL,
  `codigo` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `tlf` int(9) DEFAULT NULL,
  `observaciones` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_tlf` (`tlf`)
) ENGINE=MyISAM AUTO_INCREMENT=453 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Tabla incidencias
CREATE TABLE `incidencias` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `idcliente` int(10) DEFAULT NULL,
  `fecha` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `incidencia` longtext COLLATE utf8_spanish_ci,
  `realizado` tinyint(1) DEFAULT 0,
  `respuesta` longtext COLLATE utf8_spanish_ci,
  `cobrado` tinyint(1) DEFAULT 0,
  `finalizada` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_idcliente` (`idcliente`),
  KEY `idx_realizado` (`realizado`),
  KEY `idx_cobrado` (`cobrado`),
  KEY `idx_finalizada` (`finalizada`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (hasheada con password_hash)
INSERT INTO usuarios (user, pass) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertar algunos clientes de prueba
INSERT INTO clientes (nombre, razon_social, codigo, tlf, observaciones) VALUES
('Juan Pérez', 'Pérez SL', 'CLI001', 612345678, 'Cliente de prueba'),
('María García', 'García e Hijos SA', 'CLI002', 687654321, 'Cliente VIP'),
('Carlos López', 'López Servicios', 'CLI003', 654321987, 'Mantenimiento regular');

-- Insertar algunas incidencias de prueba
INSERT INTO incidencias (idcliente, fecha, incidencia, realizado, respuesta, cobrado, finalizada) VALUES
(1, '15/01/2026', 'Error en sistema de facturación', 1, 'Solucionado mediante actualización', 1, 1),
(1, '20/01/2026', 'Configuración de red', 0, '', 0, 0),
(2, '18/01/2026', 'Instalación de nuevo software', 1, 'Instalado correctamente', 1, 1),
(3, '22/01/2026', 'Mantenimiento preventivo', 0, '', 0, 0);
