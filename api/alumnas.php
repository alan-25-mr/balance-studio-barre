<?php
/**
 * Balance Studio — API de Alumnas
 */
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['coach_id']) && !isset($_SESSION['alumna_id'])) {
    jsonResponse(['error' => 'Acceso denegado.'], 403);
}

if (in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'DELETE']) && !isset($_SESSION['coach_id'])) {
    jsonResponse(['error' => 'Solo los coaches tienen permiso para realizar esta acción.'], 403);
}

require_once __DIR__ . '/../config/reservaciones_helper.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

switch ($method) {

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
        }

        $stmt = $pdo->query("
            SELECT a.*, p.nombre AS paquete_nombre
            FROM alumnas a
            LEFT JOIN paquetes p ON a.paquete_id = p.id
            ORDER BY a.created_at DESC
        ");
        jsonResponse($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $alumnaId = $_SESSION['alumna_id'] ?? $data['alumna_id'] ?? null;

        if (!$alumnaId) {
            if (empty($data['nombre']) || empty($data['apellidos']) || empty($data['telefono'])) {
                jsonResponse(['error' => 'Nombre, apellidos y teléfono son obligatorios'], 400);
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM alumnas WHERE id = ?");
            $stmt->execute([$alumnaId]);
            $existingAlumna = $stmt->fetch();
            if (!$existingAlumna) {
                jsonResponse(['error' => 'Alumna no encontrada'], 404);
            }
            $data['nombre'] = $existingAlumna['nombre'];
            $data['apellidos'] = $existingAlumna['apellidos'];
            $data['telefono'] = $existingAlumna['telefono'];
        }

        $paquete = null;
        if (!empty($data['paquete_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM paquetes WHERE id = ? AND activo = 1");
            $stmt->execute([$data['paquete_id']]);
            $paquete = $stmt->fetch();
            if (!$paquete) {
                jsonResponse(['error' => 'El paquete seleccionado no es válido'], 400);
            }
        }

        $estatus = !empty($data['estatus']) ? $data['estatus'] : 'Pendiente';
        $clasesRestantes = 0;
        $monto = 0;
        $fechaVencimiento = null;

        if ($paquete) {
            $clasesRestantes = (int) $paquete['clases_incluidas'];
            $monto = (float) $paquete['precio'];
            $stmt = $pdo->prepare("SELECT DATE_ADD(CURDATE(), INTERVAL ? DAY) AS vence");
            $stmt->execute([(int) $paquete['duracion_dias']]);
            $fechaVencimiento = $stmt->fetchColumn();
        }

        if (!empty($data['monto'])) {
            $monto = (float) $data['monto'];
        }
        if (!empty($data['fecha_vencimiento'])) {
            $fechaVencimiento = $data['fecha_vencimiento'];
        }

        $reservacionesInput = $data['reservaciones'] ?? [];

        if ($paquete && empty($reservacionesInput)) {
            jsonResponse(['error' => 'Debes elegir tus días de clase'], 400);
        }

        if ($paquete && count($reservacionesInput) !== (int) $paquete['clases_incluidas']) {
            jsonResponse([
                'error' => 'Debes seleccionar exactamente ' . $paquete['clases_incluidas'] . ' clase(s) según tu paquete',
            ], 400);
        }

        try {
            $pdo->beginTransaction();

            if ($alumnaId) {
                // Actualizar paquete, clases y vigencia de alumna logueada
                $stmt = $pdo->prepare("
                    UPDATE alumnas SET
                        paquete_id = ?,
                        clases_restantes = ?,
                        fecha_vencimiento = ?,
                        monto = ?,
                        estatus = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $paquete ? $paquete['id'] : null,
                    $clasesRestantes,
                    $fechaVencimiento,
                    $monto,
                    $estatus,
                    $alumnaId
                ]);
            } else {
                // Crear nueva alumna
                $stmt = $pdo->prepare("
                    INSERT INTO alumnas (
                        nombre, apellidos, fecha_nacimiento, telefono, paquete_id,
                        clases_restantes, lesion, fecha_registro, fecha_vencimiento, monto, estatus
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)
                ");
                $stmt->execute([
                    trim($data['nombre']),
                    trim($data['apellidos']),
                    !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                    trim($data['telefono']),
                    $paquete ? $paquete['id'] : null,
                    $clasesRestantes,
                    !empty($data['lesion']) ? trim($data['lesion']) : null,
                    $fechaVencimiento,
                    $monto,
                    $estatus,
                ]);
                $alumnaId = (int) $pdo->lastInsertId();
            }

            $creadas = crearReservaciones($pdo, $alumnaId, $reservacionesInput, $estatus === 'Activa');

            $pdo->commit();

            jsonResponse([
                'id'            => $alumnaId,
                'message'       => 'Registro completado',
                'reservaciones' => $creadas,
            ], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['error' => $e->getMessage()], 400);
        }
        break;

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
            $data['id'],
        ]);

        jsonResponse(['message' => 'Alumna actualizada']);
        break;

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
