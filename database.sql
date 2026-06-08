-- balance_final — solo agrega lo que falte (no crea otra base)
USE balance_final;

ALTER TABLE alumnas
  ADD COLUMN IF NOT EXISTS clases_restantes INT DEFAULT 0 AFTER paquete_id;

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

CREATE TABLE IF NOT EXISTS asistencia_coaches (
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

-- Cambios versión 2.0
ALTER TABLE alumnas ADD COLUMN IF NOT EXISTS email VARCHAR(150) UNIQUE DEFAULT NULL;
ALTER TABLE alumnas ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL;
ALTER TABLE coaches ADD COLUMN IF NOT EXISTS email VARCHAR(150) UNIQUE DEFAULT NULL;
ALTER TABLE coaches ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL;

-- Actualizar contraseñas de las coaches por defecto (SHA-256 de steph123 y fati123)
UPDATE coaches SET email = 'stephanie@balancestudio.com', password = 'f3f81b4178264e7640cd3486879336ab4a582c4033093cccd03487c3c95a2b8e' WHERE id = 1;
UPDATE coaches SET email = 'fatima@balancestudio.com', password = '8a8ecb5079178266b93e4958be8c95c9fe0383baf713a563b16f88a157630ef3' WHERE id = 2;


