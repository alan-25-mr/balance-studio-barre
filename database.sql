-- =============================================
-- BALANCE STUDIO - Base de Datos
-- =============================================

CREATE DATABASE IF NOT EXISTS balance_studio
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE balance_studio;

-- =============================================
-- Tabla: paquetes
-- =============================================
CREATE TABLE IF NOT EXISTS paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    clases_incluidas INT DEFAULT 0,
    duracion_dias INT DEFAULT 30,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Tabla: coaches
-- =============================================
CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    especialidad VARCHAR(150),
    telefono VARCHAR(20),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Tabla: alumnas
-- =============================================
CREATE TABLE IF NOT EXISTS alumnas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    telefono VARCHAR(20) NOT NULL,
    paquete_id INT,
    lesion TEXT,
    fecha_registro DATE NOT NULL,
    fecha_vencimiento DATE,
    monto DECIMAL(10,2) DEFAULT 0.00,
    estatus ENUM('Activa','Inactiva','Pendiente') DEFAULT 'Activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- Tabla: horarios
-- =============================================
CREATE TABLE IF NOT EXISTS horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    tipo_clase VARCHAR(100) NOT NULL,
    capacidad INT DEFAULT 15,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Datos de ejemplo: Paquetes
-- =============================================
INSERT INTO paquetes (nombre, descripcion, precio, clases_incluidas, duracion_dias) VALUES
('Clase Unitaria', '', 55.00, 1, 1),
('4 clases', '', 200.00, 4, 30),
('8 clases', '', 380.00, 8, 30),
('12 clases', '', 550.00, 12, 30),
('20 clases', '', 890.00, 20, 30);

-- =============================================
-- Datos de ejemplo: Coaches
-- =============================================
INSERT INTO coaches (nombre, apellidos, especialidad) VALUES
('Ana', 'García López', 'Barre Clásico'),
('María', 'Fernández Ruiz', 'Barre Fitness'),
('Laura', 'Martínez Díaz', 'Pilates & Barre');

-- =============================================
-- Datos de ejemplo: Horarios
-- =============================================
INSERT INTO horarios (coach_id, dia_semana, hora_inicio, hora_fin, tipo_clase) VALUES
(1, 'Lunes', '07:00', '08:00', 'Barre Clásico'),
(1, 'Miércoles', '07:00', '08:00', 'Barre Clásico'),
(1, 'Viernes', '07:00', '08:00', 'Barre Clásico'),
(2, 'Martes', '09:00', '10:00', 'Barre Fitness'),
(2, 'Jueves', '09:00', '10:00', 'Barre Fitness'),
(2, 'Sábado', '10:00', '11:00', 'Barre Fitness'),
(3, 'Lunes', '18:00', '19:00', 'Pilates & Barre'),
(3, 'Miércoles', '18:00', '19:00', 'Pilates & Barre'),
(3, 'Viernes', '18:00', '19:00', 'Pilates & Barre');

-- Alumna de ejemplo
INSERT INTO alumnas (nombre, apellidos, telefono, paquete_id, lesion, fecha_registro, fecha_vencimiento, monto, estatus) VALUES
('Fany', 'Salas', '2821031529', 3, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1200.00, 'Activa');
