-- ====================================================================
-- BASE DE DATOS UNIFICADA COMPLETA: balance_final
-- Contiene todas las tablas, relaciones, columnas de seguridad y datos semilla
-- ====================================================================

CREATE DATABASE IF NOT EXISTS balance_final
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE balance_final;

-- --------------------------------------------------------
-- Tabla: paquetes
-- --------------------------------------------------------
DROP TABLE IF EXISTS reservaciones;
DROP TABLE IF EXISTS asistencia_coaches;
DROP TABLE IF EXISTS horarios;
DROP TABLE IF EXISTS alumnas;
DROP TABLE IF EXISTS coaches;
DROP TABLE IF EXISTS paquetes;

CREATE TABLE paquetes (
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
CREATE TABLE coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    especialidad VARCHAR(150),
    telefono VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) UNIQUE DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: alumnas
-- --------------------------------------------------------
CREATE TABLE alumnas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumna_id VARCHAR(10) UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    telefono VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) UNIQUE DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    paquete_id INT DEFAULT NULL,
    clases_restantes INT DEFAULT 0,
    lesion TEXT,
    fecha_registro DATE NOT NULL,
    fecha_vencimiento DATE DEFAULT NULL,
    monto DECIMAL(10,2) DEFAULT 0.00,
    sexo ENUM('Mujer','Hombre') DEFAULT 'Mujer',
    estatus ENUM('Activa','Inactiva','Pendiente') DEFAULT 'Activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: horarios
-- --------------------------------------------------------
CREATE TABLE horarios (
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
CREATE TABLE reservaciones (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_clase INT NOT NULL,
    id_alumna INT NOT NULL,
    fecha_clase DATE NOT NULL,
    estatus ENUM('Confirmada','Cancelada') DEFAULT 'Confirmada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_clase) REFERENCES horarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_alumna) REFERENCES alumnas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: asistencia_coaches
-- --------------------------------------------------------
CREATE TABLE asistencia_coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME NOT NULL,
    id_horario INT NULL,
    notas VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE,
    FOREIGN KEY (id_horario) REFERENCES horarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_coach_fecha (coach_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- INSERCIÓN DE DATOS SEMILLA (SEED DATA)
-- ====================================================================

-- 1. Insertar Paquetes
INSERT INTO paquetes (id, nombre, descripcion, precio, clases_incluidas, duracion_dias) VALUES
(1, '1 clase', 'Ideal para probar una sesión', 55.00, 1, 7),
(2, '4 clases', 'Perfecto para empezar', 200.00, 4, 30),
(3, '8 clases', 'Nuestro plan más popular', 380.00, 8, 30),
(4, '12 clases', 'Para un compromiso real', 550.00, 12, 30),
(5, '20 clases', 'Resultados máximos garantizados', 890.00, 20, 30);

-- 2. Insertar Coaches
-- Contraseñas en SHA-256: 
-- Coach Fany: 'steph123' -> f3f81b4178264e7640cd3486879336ab4a582c4033093cccd03487c3c95a2b8e
-- Coach Fati: 'fati123'  -> 8a8ecb5079178266b93e4958be8c95c9fe0383baf713a563b16f88a157630ef3
INSERT INTO coaches (id, coach_id, nombre, apellidos, especialidad, telefono, email, password) VALUES
(1, '101', 'Coach Fany', 'Salas', 'Pilates, Barré & Funcional', '1111111111', 'stephanie@balancestudio.com', 'f3f81b4178264e7640cd3486879336ab4a582c4033093cccd03487c3c95a2b8e'),
(2, '102', 'Coach Fati', 'Sánchez González', 'Barré', '2222222222', 'fatima@balancestudio.com', '8a8ecb5079178266b93e4958be8c95c9fe0383baf713a563b16f88a157630ef3');

-- 3. Insertar Horarios
INSERT INTO horarios (coach_id, dia_semana, hora_inicio, hora_fin, tipo_clase, capacidad) VALUES
-- Coach Fany: Lunes a Viernes 6am, 7am, 5pm, 6pm, 7pm, 8pm
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
-- Coach Fati: Lunes, Miércoles y Viernes 8am
(2, 'Lunes', '08:00:00', '09:00:00', 'Barré', 15),
(2, 'Miércoles', '08:00:00', '09:00:00', 'Barré', 15),
(2, 'Viernes', '08:00:00', '09:00:00', 'Barré', 15);

-- 4. Alumnas iniciales de prueba
-- Contraseña en SHA-256 de 'alumna123': '5d852033bc656b2fc631bf3bb7d72740ff30455c179cf0214a1a8c3d90610332'
INSERT INTO alumnas (id, alumna_id, nombre, apellidos, fecha_nacimiento, telefono, email, password, paquete_id, clases_restantes, lesion, fecha_registro, fecha_vencimiento, monto, sexo, estatus) VALUES
(1, '201', 'Ana María', 'Gómez', '1998-05-15', '3333333333', 'ana@gmail.com', '5d852033bc656b2fc631bf3bb7d72740ff30455c179cf0214a1a8c3d90610332', 3, 8, 'Ninguna', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 380.00, 'Mujer', 'Activa'),
(2, '202', 'Sofía', 'López', '1995-10-22', '4444444444', 'sofia@gmail.com', '5d852033bc656b2fc631bf3bb7d72740ff30455c179cf0214a1a8c3d90610332', 4, 12, 'Dolor lumbar leve', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 550.00, 'Mujer', 'Activa');

-- 5. Reservaciones iniciales de ejemplo
INSERT INTO reservaciones (id_reserva, id_clase, id_alumna, fecha_clase, estatus) VALUES
(1, 1, 1, CURDATE(), 'Confirmada'),
(2, 2, 1, CURDATE(), 'Confirmada');
