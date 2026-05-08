<?php
/**
 * Balance Studio — API de Alumnas
 * CRUD: GET, POST, PUT, DELETE
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {

    // ── Listar alumnas (o una por ID) ──
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("
                SELECT a.*, p.nombre AS paquete_nombre
                FROM alumnas a
                LEFT JOIN paquetes p ON a.paquete_id = p.id
                WHERE a.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $alumna = $stmt->fetch();
            jsonResponse($alumna ?: ['error' => 'No encontrada'], $alumna ? 200 : 404);
        } else {
            $stmt = $pdo->query("
                SELECT a.*, p.nombre AS paquete_nombre
                FROM alumnas a
                LEFT JOIN paquetes p ON a.paquete_id = p.id
                ORDER BY a.created_at DESC
            ");
            jsonResponse($stmt->fetchAll());
        }
        break;

    // ── Crear alumna ──
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre']) || empty($data['apellidos']) || empty($data['telefono'])) {
            jsonResponse(['error' => 'Nombre, apellidos y teléfono son obligatorios'], 400);
        }

        // --- CORRECCIÓN DE BUG: Validar que el paquete existe ---
        if (!empty($data['paquete_id'])) {
            $checkPkg = $pdo->prepare("SELECT id FROM paquetes WHERE id = ?");
            $checkPkg->execute([$data['paquete_id']]);
            if (!$checkPkg->fetch()) {
                jsonResponse(['error' => 'El paquete seleccionado no es válido'], 400);
            }
        }
        // ------------------------------------------------------

        $stmt = $pdo->prepare("
            INSERT INTO alumnas (nombre, apellidos, fecha_nacimiento, telefono, paquete_id, lesion, fecha_registro, fecha_vencimiento, monto, estatus)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)
        ");

        $stmt->execute([
            trim($data['nombre']),
            trim($data['apellidos']),
            !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
            trim($data['telefono']),
            !empty($data['paquete_id']) ? $data['paquete_id'] : null,
            !empty($data['lesion']) ? trim($data['lesion']) : null,
            !empty($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null,
            !empty($data['monto']) ? $data['monto'] : 0,
            !empty($data['estatus']) ? $data['estatus'] : 'Activa'
        ]);

        jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Alumna registrada'], 201);
        break;

    // ── Actualizar alumna ──
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE alumnas SET
                nombre = ?,
                apellidos = ?,
                fecha_nacimiento = ?,
                telefono = ?,
                paquete_id = ?,
                lesion = ?,
                fecha_vencimiento = ?,
                monto = ?,
                estatus = ?
            WHERE id = ?
        ");

        $stmt->execute([
            trim($data['nombre']),
            trim($data['apellidos']),
            !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
            trim($data['telefono']),
            !empty($data['paquete_id']) ? $data['paquete_id'] : null,
            !empty($data['lesion']) ? trim($data['lesion']) : null,
            !empty($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null,
            !empty($data['monto']) ? $data['monto'] : 0,
            !empty($data['estatus']) ? $data['estatus'] : 'Activa',
            $data['id']
        ]);

        jsonResponse(['message' => 'Alumna actualizada']);
        break;

    // ── Eliminar alumna ──
    case 'DELETE':
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'ID requerido'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM alumnas WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        jsonResponse(['message' => 'Alumna eliminada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
