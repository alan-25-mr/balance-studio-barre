<?php
/**
 * Balance Studio — API de Reservaciones
 */

session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['coach_id']) && !isset($_SESSION['alumna_id'])) {
    jsonResponse(['error' => 'Acceso denegado.'], 403);
}

if (in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'DELETE']) && !isset($_SESSION['coach_id'])) {
    jsonResponse(['error' => 'Acceso restringido a administradores.'], 403);
}

require_once __DIR__ . '/../config/reservaciones_helper.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {

    case 'GET':
        if (isset($_GET['id_clase'], $_GET['fecha_clase'])) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS ocupados
                FROM reservaciones
                WHERE id_clase = ? AND fecha_clase = ? AND estatus = 'Confirmada'
            ");
            $stmt->execute([$_GET['id_clase'], $_GET['fecha_clase']]);
            jsonResponse($stmt->fetch());
        }

        $sql = "
            SELECT r.id_reserva, r.id_clase, r.id_alumna, r.fecha_clase, r.estatus,
                   CONCAT(a.nombre, ' ', a.apellidos) AS alumna_nombre,
                   h.dia_semana, h.hora_inicio, h.tipo_clase
            FROM reservaciones r
            JOIN alumnas a ON r.id_alumna = a.id
            JOIN horarios h ON r.id_clase = h.id
            WHERE r.estatus = 'Confirmada'
        ";
        $params = [];

        if (!empty($_GET['id_clase'])) {
            $sql .= " AND r.id_clase = ?";
            $params[] = $_GET['id_clase'];
        }
        if (!empty($_GET['fecha_desde'])) {
            $sql .= " AND r.fecha_clase >= ?";
            $params[] = $_GET['fecha_desde'];
        }

        $sql .= " ORDER BY r.fecha_clase ASC, h.hora_inicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());
        break;

    case 'POST':
        jsonResponse(['error' => 'Acción no soportada en este endpoint'], 400);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
