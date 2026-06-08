<?php
/**
 * Balance Studio — API de Horarios
 * CRUD: GET, POST, PUT, DELETE
 */

session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !isset($_SESSION['coach_id'])) {
    jsonResponse(['error' => 'Acceso denegado.'], 403);
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("
                SELECT h.*, CONCAT(c.nombre, ' ', c.apellidos) AS coach_nombre
                FROM horarios h
                LEFT JOIN coaches c ON h.coach_id = c.id
                WHERE h.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $horario = $stmt->fetch();
            jsonResponse($horario ?: ['error' => 'No encontrado'], $horario ? 200 : 404);
        } else {
            $sql = "
                SELECT h.*, CONCAT(c.nombre, ' ', c.apellidos) AS coach_nombre
                FROM horarios h
                LEFT JOIN coaches c ON h.coach_id = c.id
                WHERE h.activo = 1
            ";
            $params = [];

            if (isset($_GET['coach_id'])) {
                $sql .= " AND h.coach_id = ?";
                $params[] = $_GET['coach_id'];
            }

            $sql .= " ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), h.hora_inicio ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['coach_id']) || empty($data['dia_semana']) || empty($data['hora_inicio']) || empty($data['hora_fin']) || empty($data['tipo_clase'])) {
            jsonResponse(['error' => 'Todos los campos son obligatorios'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO horarios (coach_id, dia_semana, hora_inicio, hora_fin, tipo_clase, capacidad)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['coach_id'],
            $data['dia_semana'],
            $data['hora_inicio'],
            $data['hora_fin'],
            trim($data['tipo_clase']),
            isset($data['capacidad']) ? $data['capacidad'] : 15
        ]);

        jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Horario creado'], 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE horarios SET
                coach_id = ?,
                dia_semana = ?,
                hora_inicio = ?,
                hora_fin = ?,
                tipo_clase = ?,
                capacidad = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['coach_id'],
            $data['dia_semana'],
            $data['hora_inicio'],
            $data['hora_fin'],
            trim($data['tipo_clase']),
            isset($data['capacidad']) ? $data['capacidad'] : 15,
            $data['id']
        ]);

        jsonResponse(['message' => 'Horario actualizado']);
        break;

    case 'DELETE':
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM horarios WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        jsonResponse(['message' => 'Horario eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
