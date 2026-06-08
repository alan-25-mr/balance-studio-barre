<?php
/**
 * Balance Studio — API de Paquetes
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
            $stmt = $pdo->prepare("SELECT * FROM paquetes WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $paquete = $stmt->fetch();
            jsonResponse($paquete ?: ['error' => 'No encontrado'], $paquete ? 200 : 404);
        } else {
            $stmt = $pdo->query("SELECT * FROM paquetes WHERE activo = 1 ORDER BY precio ASC");
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre']) || !isset($data['precio'])) {
            jsonResponse(['error' => 'Nombre y precio son obligatorios'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO paquetes (nombre, descripcion, precio, clases_incluidas, duracion_dias)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($data['nombre']),
            !empty($data['descripcion']) ? trim($data['descripcion']) : null,
            $data['precio'],
            isset($data['clases_incluidas']) ? $data['clases_incluidas'] : 0,
            isset($data['duracion_dias']) ? $data['duracion_dias'] : 30
        ]);

        jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Paquete creado'], 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE paquetes SET
                nombre = ?,
                descripcion = ?,
                precio = ?,
                clases_incluidas = ?,
                duracion_dias = ?
            WHERE id = ?
        ");

        $stmt->execute([
            trim($data['nombre']),
            !empty($data['descripcion']) ? trim($data['descripcion']) : null,
            $data['precio'],
            isset($data['clases_incluidas']) ? $data['clases_incluidas'] : 0,
            isset($data['duracion_dias']) ? $data['duracion_dias'] : 30,
            $data['id']
        ]);

        jsonResponse(['message' => 'Paquete actualizado']);
        break;

    case 'DELETE':
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("UPDATE paquetes SET activo = 0 WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        jsonResponse(['message' => 'Paquete eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
