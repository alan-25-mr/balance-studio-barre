<?php
/**
 * Balance Studio — API de Coaches
 * CRUD: GET, POST, PUT, DELETE
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM coaches WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $coach = $stmt->fetch();
            jsonResponse($coach ?: ['error' => 'No encontrado'], $coach ? 200 : 404);
        } else {
            $stmt = $pdo->query("SELECT * FROM coaches WHERE activo = 1 ORDER BY nombre ASC");
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre']) || empty($data['apellidos'])) {
            jsonResponse(['error' => 'Nombre y apellidos son obligatorios'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO coaches (nombre, apellidos, especialidad, telefono)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($data['nombre']),
            trim($data['apellidos']),
            !empty($data['especialidad']) ? trim($data['especialidad']) : null,
            !empty($data['telefono']) ? trim($data['telefono']) : null
        ]);

        jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Coach registrado'], 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE coaches SET
                nombre = ?,
                apellidos = ?,
                especialidad = ?,
                telefono = ?
            WHERE id = ?
        ");

        $stmt->execute([
            trim($data['nombre']),
            trim($data['apellidos']),
            !empty($data['especialidad']) ? trim($data['especialidad']) : null,
            !empty($data['telefono']) ? trim($data['telefono']) : null,
            $data['id']
        ]);

        jsonResponse(['message' => 'Coach actualizado']);
        break;

    case 'DELETE':
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        // Soft delete
        $stmt = $pdo->prepare("UPDATE coaches SET activo = 0 WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        jsonResponse(['message' => 'Coach eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
