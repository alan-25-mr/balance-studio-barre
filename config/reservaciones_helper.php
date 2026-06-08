<?php
/**
 * Lógica compartida de reservaciones (PHP cliente + validaciones).
 */

require_once __DIR__ . '/fechas.php';

function ocupacionEnFecha(PDO $pdo, int $idClase, string $fecha): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservaciones
        WHERE id_clase = ? AND fecha_clase = ? AND estatus = 'Confirmada'
    ");
    $stmt->execute([$idClase, $fecha]);
    return (int) $stmt->fetchColumn();
}

function autoAsignarHorarios(PDO $pdo, int $paqueteId, ?int $coachId = null, ?string $horaPreferida = null): array
{
    $stmt = $pdo->prepare("SELECT clases_incluidas FROM paquetes WHERE id = ? AND activo = 1");
    $stmt->execute([$paqueteId]);
    $paquete = $stmt->fetch();

    if (!$paquete) {
        throw new Exception('Paquete no válido');
    }

    $necesarias = (int) $paquete['clases_incluidas'];
    if ($necesarias < 1) {
        throw new Exception('El paquete no tiene clases configuradas');
    }

    // Fetch available schedules
    $sql = "
        SELECT h.id, h.dia_semana, h.hora_inicio, h.hora_fin, h.tipo_clase, h.capacidad,
               CONCAT(c.nombre, ' ', c.apellidos) AS coach_nombre
        FROM horarios h
        LEFT JOIN coaches c ON h.coach_id = c.id
        WHERE h.activo = 1
    ";
    $params = [];
    if ($coachId !== null) {
        $sql .= " AND h.coach_id = ?";
        $params[] = $coachId;
    }
    // Filter by preferred hour if set
    if ($horaPreferida !== null) {
        $sql .= " AND SUBSTRING(h.hora_inicio, 1, 5) = ?";
        $params[] = $horaPreferida;
    }
    $sql .= " ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), h.hora_inicio";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $horarios = $stmt->fetchAll();

    // Sequential weekday order: Monday through Friday
    $ordenDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

    // Group available horarios by weekday
    $porDia = [];
    foreach ($ordenDias as $dia) {
        $porDia[$dia] = [];
    }
    foreach ($horarios as $h) {
        if (isset($porDia[$h['dia_semana']])) {
            $porDia[$h['dia_semana']][] = $h;
        }
    }

    // Pick one class per day, sequentially Mon→Fri, until we have enough
    $seleccion = [];
    $hoy = new DateTime('today');

    // Try the current week first, then expand to future weeks
    for ($semana = 0; $semana < 8 && count($seleccion) < $necesarias; $semana++) {
        foreach ($ordenDias as $dia) {
            if (count($seleccion) >= $necesarias) {
                break;
            }

            // Skip days with no schedules at the requested hour/coach
            if (empty($porDia[$dia])) {
                continue;
            }

            $base = clone $hoy;
            if ($semana > 0) {
                $base->modify('+' . ($semana * 7) . ' days');
            }
            $fecha = proximaFechaPorDiaSemana($dia, $base);
            if (!$fecha) {
                continue;
            }

            // Skip if we already have a class on this exact date
            $yaEnFecha = false;
            foreach ($seleccion as $s) {
                if ($s['fecha_clase'] === $fecha) {
                    $yaEnFecha = true;
                    break;
                }
            }
            if ($yaEnFecha) {
                continue;
            }

            // Pick the first available slot on this day that has capacity
            foreach ($porDia[$dia] as $h) {
                if (ocupacionEnFecha($pdo, (int) $h['id'], $fecha) >= (int) $h['capacidad']) {
                    continue;
                }

                $seleccion[] = [
                    'id_clase'     => (int) $h['id'],
                    'fecha_clase'  => $fecha,
                    'dia_semana'   => $h['dia_semana'],
                    'hora_inicio'  => substr($h['hora_inicio'], 0, 5),
                    'tipo_clase'   => $h['tipo_clase'],
                    'coach_nombre' => $h['coach_nombre'],
                ];
                break; // Only one class per day
            }
        }
    }

    if (count($seleccion) < $necesarias) {
        throw new Exception('No hay suficientes cupos disponibles para asignar todas las clases del paquete con la hora seleccionada');
    }

    return [
        'clases_requeridas' => $necesarias,
        'reservaciones'     => array_slice($seleccion, 0, $necesarias),
    ];
}

function crearReservaciones(PDO $pdo, int $alumnaId, array $items, bool $descontarClases): array
{
    $creadas = [];

    foreach ($items as $item) {
        $idClase = (int) ($item['id_clase'] ?? $item['id_horario'] ?? 0);
        if (!$idClase) {
            throw new Exception('Cada reservación debe incluir id_clase');
        }

        $stmt = $pdo->prepare("SELECT dia_semana, capacidad, tipo_clase, hora_inicio FROM horarios WHERE id = ? AND activo = 1");
        $stmt->execute([$idClase]);
        $horario = $stmt->fetch();
        if (!$horario) {
            throw new Exception('Horario no válido');
        }

        $fechaClase = !empty($item['fecha_clase'])
            ? $item['fecha_clase']
            : proximaFechaPorDiaSemana($horario['dia_semana']);

        if (!$fechaClase) {
            throw new Exception('No se pudo calcular la fecha para ' . $horario['dia_semana']);
        }

        if (ocupacionEnFecha($pdo, $idClase, $fechaClase) >= (int) $horario['capacidad']) {
            throw new Exception(
                $horario['tipo_clase'] . ' (' . $horario['dia_semana'] . ' ' . substr($horario['hora_inicio'], 0, 5) . ') del ' . $fechaClase . ' está llena'
            );
        }

        $stmt = $pdo->prepare("
            SELECT id_reserva FROM reservaciones
            WHERE id_clase = ? AND id_alumna = ? AND fecha_clase = ? AND estatus = 'Confirmada'
        ");
        $stmt->execute([$idClase, $alumnaId, $fechaClase]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe una reservación duplicada para esa fecha');
        }

        $stmt = $pdo->prepare("
            INSERT INTO reservaciones (id_clase, id_alumna, fecha_clase, estatus)
            VALUES (?, ?, ?, 'Confirmada')
        ");
        $stmt->execute([$idClase, $alumnaId, $fechaClase]);

        $creadas[] = [
            'id_reserva'  => (int) $pdo->lastInsertId(),
            'id_clase'    => $idClase,
            'fecha_clase' => $fechaClase,
            'dia_semana'  => $horario['dia_semana'],
        ];
    }

    if ($descontarClases && count($creadas) > 0) {
        $stmt = $pdo->prepare("
            UPDATE alumnas
            SET clases_restantes = GREATEST(0, clases_restantes - ?)
            WHERE id = ?
        ");
        $stmt->execute([count($creadas), $alumnaId]);
    }

    return $creadas;
}
