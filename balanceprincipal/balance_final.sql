-- =============================================
-- BASE DE DATOS UNIFICADA: balance_final
-- Compatible con portal de clientes (PHP) y admin (Python)
-- =============================================

CREATE DATABASE IF NOT EXISTS balance_final
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE balance_final;

-- --------------------------------------------------------
-- Tabla: paquetes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    clases_incluidas INT DEFAULT 0,
    duracion_dias INT DEFAULT 30,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: coaches
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    especialidad VARCHAR(150),
    telefono VARCHAR(20),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: alumnas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS alumnas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumna_id VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    telefono VARCHAR(20) NOT NULL,
    paquete_id INT,
    clases_restantes INT DEFAULT 0,
    lesion TEXT,
    fecha_registro DATE NOT NULL,
    fecha_vencimiento DATE,
    monto DECIMAL(10,2) DEFAULT 0.00,
    estatus ENUM('Activa','Inactiva','Pendiente') DEFAULT 'Activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: horarios
-- --------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: reservaciones
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservaciones (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_clase INT NOT NULL,
    id_alumna INT NOT NULL,
    fecha_clase DATE NOT NULL,
    estatus ENUM('Confirmada','Cancelada') DEFAULT 'Confirmada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_clase) REFERENCES horarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_alumna) REFERENCES alumnas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DATOS DE EJEMPLO E INICIALIZACIÓN
-- =============================================

-- 1. Insertar Paquetes
INSERT INTO paquetes (id, nombre, descripcion, precio, clases_incluidas, duracion_dias) VALUES
(1, '1 clase', 'Ideal para probar una sesión', 55.00, 1, 7),
(2, '4 clases', 'Perfecto para empezar', 200.00, 4, 30),
(3, '8 clases', 'Nuestro plan más popular', 380.00, 8, 30),
(4, '12 clases', 'Para un compromiso real', 550.00, 12, 30),
(5, '20 clases', 'Resultados máximos garantizados', 890.00, 20, 30)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), precio=VALUES(precio);

-- 2. Insertar Coaches
INSERT INTO coaches (coach_id, nombre, apellidos, especialidad) VALUES
('101', 'Coach Fany', 'Salas', 'Pilates, Barré & Funcional'),
('102', 'Coach Fati', 'Sánchez González', 'Barré')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellidos=VALUES(apellidos);

-- 3. Insertar Horarios
INSERT INTO horarios (coach_id, dia_semana, hora_inicio, hora_fin, tipo_clase, capacidad) VALUES
-- Stéphanie Lunes a Viernes 6am, 7am, 5pm, 6pm, 7pm, 8pm
(1, 'Lunes', '06:00:00', '07:00:00', 'Barré & Pilates', 15),
(1, 'Lunes', '07:00:00', '08:00:00', 'Barré & Pilates', 15),
(1, 'Lunes', '17:00:00', '18:00:00', 'Pilates & Funcional', 15),
(1, 'Lunes', '18:00:00', '19:00:00', 'Pilates & Funcional', 15),
(1, 'Lunes', '19:00:00', '20:00:00', 'Pilates & Funcional', 15),
(1, 'Lunes', '20:00:00', '21:00:00', 'Pilates & Funcional', 15),
(1, 'Martes', '06:00:00', '07:00:00', 'Barré & Pilates', 15),
(1, 'Martes', '07:00:00', '08:00:00', 'Barré & Pilates', 15),
(1, 'Martes', '17:00:00', '18:00:00', 'Pilates & Funcional', 15),
(1, 'Martes', '18:00:00', '19:00:00', 'Pilates & Funcional', 15),
(1, 'Martes', '19:00:00', '20:00:00', 'Pilates & Funcional', 15),
(1, 'Martes', '20:00:00', '21:00:00', 'Pilates & Funcional', 15),
(1, 'Miércoles', '06:00:00', '07:00:00', 'Barré & Pilates', 15),
(1, 'Miércoles', '07:00:00', '08:00:00', 'Barré & Pilates', 15),
(1, 'Miércoles', '17:00:00', '18:00:00', 'Pilates & Funcional', 15),
(1, 'Miércoles', '18:00:00', '19:00:00', 'Pilates & Funcional', 15),
(1, 'Miércoles', '19:00:00', '20:00:00', 'Pilates & Funcional', 15),
(1, 'Miércoles', '20:00:00', '21:00:00', 'Pilates & Funcional', 15),
(1, 'Jueves', '06:00:00', '07:00:00', 'Barré & Pilates', 15),
(1, 'Jueves', '07:00:00', '08:00:00', 'Barré & Pilates', 15),
(1, 'Jueves', '17:00:00', '18:00:00', 'Pilates & Funcional', 15),
(1, 'Jueves', '18:00:00', '19:00:00', 'Pilates & Funcional', 15),
(1, 'Jueves', '19:00:00', '20:00:00', 'Pilates & Funcional', 15),
(1, 'Jueves', '20:00:00', '21:00:00', 'Pilates & Funcional', 15),
(1, 'Viernes', '06:00:00', '07:00:00', 'Barré & Pilates', 15),
(1, 'Viernes', '07:00:00', '08:00:00', 'Barré & Pilates', 15),
(1, 'Viernes', '17:00:00', '18:00:00', 'Pilates & Funcional', 15),
(1, 'Viernes', '18:00:00', '19:00:00', 'Pilates & Funcional', 15),
(1, 'Viernes', '19:00:00', '20:00:00', 'Pilates & Funcional', 15),
(1, 'Viernes', '20:00:00', '21:00:00', 'Pilates & Funcional', 15),
-- Fátima Lunes, Miércoles y Viernes 8am
(2, 'Lunes', '08:00:00', '09:00:00', 'Barré', 15),
(2, 'Miércoles', '08:00:00', '09:00:00', 'Barré', 15),
(2, 'Viernes', '08:00:00', '09:00:00', 'Barré', 15);

-- 4. Alumna de ejemplo activa con clases
INSERT INTO alumnas (alumna_id, nombre, apellidos, telefono, paquete_id, clases_restantes, lesion, fecha_registro, fecha_vencimiento, monto, estatus) VALUES
('201', 'Fany', 'Salas', '2821031529', 3, 6, '', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 380.00, 'Activa')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), clases_restantes=VALUES(clases_restantes);

-- 5. Reservaciones iniciales
INSERT INTO reservaciones (id_reserva, id_clase, id_alumna, fecha_clase, estatus) VALUES
(1, 1, 1, CURDATE(), 'Confirmada'),
(2, 2, 1, CURDATE(), 'Confirmada')
ON DUPLICATE KEY UPDATE id_clase=VALUES(id_clase);

