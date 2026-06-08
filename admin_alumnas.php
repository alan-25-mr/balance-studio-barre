<?php
require_once __DIR__ . '/config/database.php';
session_start();

// Validar seguridad de sesión
if (!isset($_SESSION['coach_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();

// Cargar mensajes flash persistidos en sesión
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$logged_coach_id = intval($_SESSION['coach_id']);

// Procesar POST (Agregar, Activar, Editar o Eliminar Alumna)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $paquete_id = $_POST['paquete_id'] ?? '';
        $lesion = trim($_POST['lesion'] ?? '');
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $estatus = $_POST['estatus'] ?? 'Activa';
        $sexo = $_POST['sexo'] ?? 'Mujer';

        if (empty($nombre) || empty($apellidos) || empty($telefono)) {
            $_SESSION['error'] = 'Nombre, apellidos y teléfono son obligatorios.';
            header('Location: admin_alumnas.php');
            exit;
        }

        if ($fecha_nacimiento === '') {
            $fecha_nacimiento = null;
        }

        // Valores por defecto
        $clases = 0;
        $monto = 0.0;
        $vencimiento = null;
        $pkg_id = null;

        if ($paquete_id && $paquete_id !== 'manual') {
            $pkg_id = $paquete_id;
            $stmt = $pdo->prepare("SELECT clases_incluidas, precio, duracion_dias FROM paquetes WHERE id = ?");
            $stmt->execute([$paquete_id]);
            $pkg = $stmt->fetch();
            if ($pkg) {
                $clases = $pkg['clases_incluidas'];
                $monto = $pkg['precio'];
                
                $stmt_date = $pdo->prepare("SELECT DATE_ADD(CURDATE(), INTERVAL ? DAY) AS v");
                $stmt_date->execute([$pkg['duracion_dias']]);
                $vencimiento = $stmt_date->fetch()['v'];
            }
        }

        // Custom overrides si se selecciona plan personalizado (manual)
        if (isset($_POST['clases_override']) && $_POST['clases_override'] !== '') {
            $clases = intval($_POST['clases_override']);
        }
        if (isset($_POST['monto_override']) && $_POST['monto_override'] !== '') {
            $monto = floatval($_POST['monto_override']);
        }
        if (isset($_POST['vencimiento_override']) && $_POST['vencimiento_override'] !== '') {
            $vencimiento = $_POST['vencimiento_override'];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO alumnas (
                    nombre, apellidos, fecha_nacimiento, telefono, paquete_id,
                    clases_restantes, lesion, fecha_registro, fecha_vencimiento, monto, estatus, sexo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nombre, $apellidos, $fecha_nacimiento, $telefono, $pkg_id,
                $clases, !empty($lesion) ? $lesion : null, $vencimiento, $monto, $estatus, $sexo
            ]);
            
            $alumnaId = $pdo->lastInsertId();
            
            // Generar alumna_id secuencialmente
            $alumna_id_code = '20' . $alumnaId;
            $stmt_id = $pdo->prepare("UPDATE alumnas SET alumna_id = ? WHERE id = ?");
            $stmt_id->execute([$alumna_id_code, $alumnaId]);
            
            $_SESSION['success'] = 'Alumna registrada exitosamente.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al registrar: ' . $e->getMessage();
        }

        header('Location: admin_alumnas.php');
        exit;

    } elseif ($action === 'activar') {
        $alumna_id = intval($_POST['alumna_id'] ?? 0);
        if ($alumna_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE alumnas SET estatus = 'Activa' WHERE id = ?");
                $stmt->execute([$alumna_id]);
                $_SESSION['success'] = 'Alumna activada correctamente.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al activar alumna: ' . $e->getMessage();
            }
        }
        header('Location: admin_alumnas.php');
        exit;
    } elseif ($action === 'edit') {
        if ($logged_coach_id === 2) {
            $_SESSION['error'] = 'No tienes permisos para editar alumnas.';
            header('Location: admin_alumnas.php');
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $lesion = trim($_POST['lesion'] ?? '');
        $estatus = $_POST['estatus'] ?? 'Activa';
        $clases_restantes = intval($_POST['clases_restantes'] ?? 0);
        $monto = floatval($_POST['monto'] ?? 0.0);
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?: null;
        $sexo = $_POST['sexo'] ?? 'Mujer';
        
        if ($id > 0 && !empty($nombre) && !empty($apellidos) && !empty($telefono)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE alumnas SET
                    nombre = ?,
                    apellidos = ?,
                    telefono = ?,
                    estatus = ?,
                    clases_restantes = ?,
                    monto = ?,
                    fecha_vencimiento = ?,
                    lesion = ?,
                    sexo = ?
                WHERE id = ?");
                $stmt->execute([$nombre, $apellidos, $telefono, $estatus, $clases_restantes, $monto, $fecha_vencimiento, $lesion, $sexo, $id]);
                $_SESSION['success'] = 'Alumna actualizada exitosamente.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al actualizar: ' . $e->getMessage();
            }
        }
        header('Location: admin_alumnas.php');
        exit;
    } elseif ($action === 'delete') {
        if ($logged_coach_id === 2) {
            $_SESSION['error'] = 'No tienes permisos para eliminar alumnas.';
            header('Location: admin_alumnas.php');
            exit;
        }
        $id = intval($_POST['alumna_id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM alumnas WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Alumna eliminada permanentemente.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
            }
        }
        header('Location: admin_alumnas.php');
        exit;
    }
}

// Cargar catálogos
$stmt = $pdo->query("SELECT id, nombre, precio, clases_incluidas FROM paquetes WHERE activo = 1 ORDER BY clases_incluidas ASC");
$paquetes = $stmt->fetchAll();

// Aumentar límite de GROUP_CONCAT para alumnas con muchas clases
$pdo->exec("SET SESSION group_concat_max_len = 100000");

// Cargar alumnas con filtrado por Coach si es el caso de Fati
if ($logged_coach_id === 2) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.alumna_id, a.nombre, a.apellidos,
               COALESCE(a.clases_restantes, 0) AS clases_restantes, a.telefono,
               a.monto, a.fecha_vencimiento, a.estatus, a.lesion, a.sexo,
               p.nombre AS paquete_nombre,
               a.fecha_registro,
               (SELECT GROUP_CONCAT(
                    CONCAT(h.dia_semana, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), ' (', DATE_FORMAT(r.fecha_clase, '%d/%m/%Y'), ')')
                    ORDER BY r.fecha_clase ASC, h.hora_inicio ASC SEPARATOR '||SPLIT||')
                FROM reservaciones r
                INNER JOIN horarios h ON r.id_clase = h.id
                WHERE r.id_alumna = a.id AND r.estatus = 'Confirmada' AND h.coach_id = 2) AS horarios_txt
        FROM alumnas a
        LEFT JOIN paquetes p ON a.paquete_id = p.id
        WHERE EXISTS (
            SELECT 1 FROM reservaciones r2
            INNER JOIN horarios h2 ON r2.id_clase = h2.id
            WHERE r2.id_alumna = a.id AND r2.estatus = 'Confirmada' AND h2.coach_id = 2
        )
        ORDER BY a.id DESC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT a.id, a.alumna_id, a.nombre, a.apellidos,
               COALESCE(a.clases_restantes, 0) AS clases_restantes, a.telefono,
               a.monto, a.fecha_vencimiento, a.estatus, a.lesion, a.sexo,
               p.nombre AS paquete_nombre,
               a.fecha_registro,
               (SELECT GROUP_CONCAT(
                    CONCAT(h.dia_semana, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), ' (', DATE_FORMAT(r.fecha_clase, '%d/%m/%Y'), ')')
                    ORDER BY r.fecha_clase ASC, h.hora_inicio ASC SEPARATOR '||SPLIT||')
                FROM reservaciones r
                INNER JOIN horarios h ON r.id_clase = h.id
                WHERE r.id_alumna = a.id AND r.estatus = 'Confirmada') AS horarios_txt
        FROM alumnas a
        LEFT JOIN paquetes p ON a.paquete_id = p.id
        ORDER BY a.id DESC
    ");
    $stmt->execute();
}
$alumnas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Studio — Gestión de Alumnos</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* CSS del Sistema de Diseño Unificado */
        .invalid-field {
            border-color: #cd2c2c !important;
            background-color: #fcebeb !important;
            box-shadow: 0 0 0 3px rgba(205, 44, 44, 0.15) !important;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        @media (max-width: 992px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        .grid-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 576px) {
            .grid-form { grid-template-columns: 1fr; }
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fafafa;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            text-align: center;
        }
        .stat-value {
            font-family: var(--font-title);
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--green-dark);
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .search-box {
            position: relative;
            max-width: 380px;
            width: 100%;
        }
        .search-input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--bg-card);
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition);
        }
        .search-input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(156, 175, 136, 0.15);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .table-toolbar { flex-direction: column; align-items: stretch; }
        }
        .lesion-text {
            color: #cd2c2c;
            font-weight: 500;
            font-size: 0.85rem;
            background-color: #fcebeb;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-error {
            background: #fcebeb;
            color: #cd2c2c;
            border: 1px solid #e0b4ae;
        }
        .alert-success {
            background: #e6f7ed;
            color: #1f874c;
            border: 1px solid #c7ebd4;
        }
    </style>
</head>
<body>

    <!-- ── Header Premium ── -->
    <header class="main-header">
        <div class="header-inner">
            <a href="index.php" class="logo">Balance <span>Studio</span></a>
            <nav class="main-nav">
                <a href="admin_alumnas.php" class="nav-link active">Administración</a>
                <a href="admin_agenda.php" class="nav-link">Agenda de Clases</a>
                <a href="admin_recepcion.php" class="nav-link">Recepción</a>
                <a href="logout.php" class="nav-link" style="color: #cd2c2c;">Cerrar Sesión</a>
            </nav>
        </div>
    </header>

    <main class="app-container" style="margin-top: 40px;">
        <section class="page-title-section">
            <h1 class="page-title">Gestión de Alumnos</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Panel administrativo de coaches para registro, activación e historial de asistencia</p>
        </section>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">✕ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Formulario de Registro -->
            <section class="card">
                <h2 class="card-title" style="font-size: 0.9rem;">Registrar Alumno</h2>
                <form action="admin_alumnas.php" method="POST" id="formRegistro">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid-form">
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre(s)</label>
                            <input type="text" class="form-input" id="nombre" name="nombre" placeholder="Ej. Ana María" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="apellidos">Apellidos</label>
                            <input type="text" class="form-input" id="apellidos" name="apellidos" placeholder="Ej. Pérez Gómez" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono</label>
                            <input type="tel" class="form-input" id="telefono" name="telefono" placeholder="10 dígitos" required pattern="[0-9]{10}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" class="form-input" id="fecha_nacimiento" name="fecha_nacimiento">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="paquete_id">Plan / Paquete de clases</label>
                            <select class="form-select" id="paquete_id" name="paquete_id" onchange="toggleManualOverrides(this)" required>
                                <option value="" selected disabled>Seleccionar plan...</option>
                                <?php foreach ($paquetes as $pkg): ?>
                                    <option value="<?= $pkg['id'] ?>">
                                        <?= htmlspecialchars($pkg['nombre']) ?> — $<?= number_format($pkg['precio'], 2) ?> (<?= $pkg['clases_incluidas'] ?> clases)
                                    </option>
                                <?php endforeach; ?>
                                <option value="manual">-- Configurar personalizado (Manual) --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="estatus">Estatus inicial</label>
                            <select class="form-select" id="estatus" name="estatus">
                                <option value="Activa" selected>Activa</option>
                                <option value="Inactiva">Inactiva</option>
                                <option value="Pendiente">Pendiente (Sin pagar)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="sexo">Género</label>
                            <select class="form-select" id="sexo" name="sexo" required>
                                <option value="Mujer" selected>Mujer</option>
                                <option value="Hombre">Hombre</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos Opcionales Personalizados (Manual Overrides) -->
                    <div class="grid-form" id="manual-overrides" style="display: none; margin-top: 20px; padding: 20px; background-color: #f9f9f9; border-radius: var(--radius-sm); border: 1px dashed var(--border-color);">
                        <div class="form-group">
                            <label class="form-label" for="clases_override">Clases a cargar</label>
                            <input type="number" class="form-input" id="clases_override" name="clases_override" placeholder="Ej. 10">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="monto_override">Monto cobrado ($)</label>
                            <input type="number" step="0.01" class="form-input" id="monto_override" name="monto_override" placeholder="Ej. 450.00">
                        </div>
                        <div class="form-group full-width" style="margin-top: 10px;">
                            <label class="form-label" for="vencimiento_override">Fecha de Vencimiento</label>
                            <input type="date" class="form-input" id="vencimiento_override" name="vencimiento_override">
                        </div>
                    </div>

                    <div class="form-group full-width" style="margin-top: 20px;">
                        <label class="form-label" for="lesion">Lesiones / Padecimientos médicos (Opcional)</label>
                        <textarea class="form-textarea" id="lesion" name="lesion" rows="2" placeholder="Escribe si la alumna presenta alguna condición especial para cuidar de ella..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-green" style="margin-top: 24px; width: 100%; justify-content: center;">Registrar e inicializar alumno</button>
                </form>
            </section>

            <!-- Resumen y Estadísticas -->
            <section class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h2 class="card-title" style="font-size: 0.9rem;">Resumen del Estudio</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value" id="stat-activas">0</div>
                            <div class="stat-label">Activas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stat-pendientes">0</div>
                            <div class="stat-label">Pendientes</div>
                        </div>
                    </div>
                </div>
                
                <div style="background-color: rgba(156,175,136,0.1); padding: 24px; border-radius: var(--radius-md); border: 1px solid rgba(156, 175, 136, 0.2); margin-top: 20px;">
                    <h3 style="font-family: var(--font-title); font-size: 1.4rem; margin-bottom: 12px; font-weight: 500;">Instrucciones rápidas</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px;">1. **Registro:** Al seleccionar un plan, el sistema calcula automáticamente la vigencia (30 días) y carga las clases correspondientes.</p>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px;">2. **Estatus Pendiente:** Representa alumnas registradas desde la web sin validar el pago. Al activarlas en este panel, se cargará su plan contratado.</p>
                    <p style="font-size: 0.85rem; color: var(--text-secondary);">3. **Recepción:** Abre el panel de recepción en la entrada del estudio para que las coaches registren su asistencia.</p>
                </div>
            </section>
        </div>

        <!-- Tabla de Alumnas -->
        <section class="card">
            <div class="table-toolbar">
                <h2 style="font-family: var(--font-title); font-size: 1.8rem; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase;">Lista de Alumnas</h2>
                
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" id="search-input" placeholder="Buscar por nombre o teléfono..." onkeyup="filterAlumnas()">
                </div>
            </div>

            <div class="table-container">
                <table class="data-table" id="alumnas-table">
                    <thead>
                        <tr>
                            <th>Nombre de la Alumna</th>
                            <th>Teléfono</th>
                            <th>Plan Contratado</th>
                            <th>Días / horarios elegidos</th>
                            <th>Clases Restantes</th>
                            <th>Monto</th>
                            <th>Vencimiento</th>
                            <th>Estatus</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnas as $al): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($al['nombre']) ?> <?= htmlspecialchars($al['apellidos']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($al['telefono']) ?></td>
                            <td><span style="opacity: 0.85;"><?= htmlspecialchars($al['paquete_nombre'] ?? 'Ninguno') ?></span></td>
                            <td style="text-align:center;">
                                <?php if ($al['horarios_txt']): ?>
                                    <button class="btn btn-outline btn-sm" style="white-space:nowrap;"
                                        onclick="showHorariosModal(<?php echo htmlspecialchars(json_encode($al['horarios_txt'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>)">
                                        📅 Ver horarios
                                    </button>
                                <?php else: ?>
                                    <span style="color:#bbb; font-size:0.8rem;">Sin horarios</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 600; color: <?= ($al['clases_restantes'] == 0 ? '#cd2c2c' : 'var(--text-primary)') ?>;">
                                <?= $al['clases_restantes'] ?> ses.
                            </td>
                            <td>$<?= number_format($al['monto'], 2) ?></td>
                            <td><?= $al['fecha_vencimiento'] ? htmlspecialchars($al['fecha_vencimiento']) : '—' ?></td>
                            <td>
                                <?php if ($al['estatus'] === 'Activa'): ?>
                                    <span class="badge badge-active">Activa</span>
                                <?php elseif ($al['estatus'] === 'Pendiente'): ?>
                                    <span class="badge badge-pending">Pendiente</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($logged_coach_id === 1): ?>
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($al)) ?>)" class="btn btn-outline btn-sm" style="padding: 6px 12px; margin-right: 4px;">Editar</button>
                                    <form action="admin_alumnas.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar permanentemente a esta alumna?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="alumna_id" value="<?= $al['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="background:#cd2c2c; color:white; padding: 6px 12px;">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($al['estatus'] === 'Pendiente'): ?>
                                    <form action="admin_alumnas.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="activar">
                                        <input type="hidden" name="alumna_id" value="<?= $al['id'] ?>">
                                        <button type="submit" class="btn btn-green btn-sm" style="padding: 6px 12px;">Activar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($logged_coach_id !== 1 && $al['estatus'] !== 'Pendiente'): ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($alumnas)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; color: var(--text-muted); padding: 40px;">
                                No hay alumnas registradas en el sistema.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer style="background: var(--black); color: var(--white); padding: 40px 0; margin-top: 60px; text-align: center;">
        <div class="app-container">
            <p style="opacity: 0.6; font-size: 0.85rem;">© 2026 Balance Studio. Panel de Coaches.</p>
        </div>
    </footer>

    <script>
        // Calcular estadísticas en tiempo real
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.getElementById('alumnas-table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let activas = 0;
            let pendientes = 0;

            for (let i = 0; i < rows.length; i++) {
                const statusBadge = rows[i].querySelector('.badge');
                if (statusBadge) {
                    const text = statusBadge.textContent.trim();
                    if (text === 'Activa') activas++;
                    if (text === 'Pendiente') pendientes++;
                }
            }

            document.getElementById('stat-activas').textContent = activas;
            document.getElementById('stat-pendientes').textContent = pendientes;

            // Validación visual de formulario
            const form = document.getElementById('formRegistro');
            form.addEventListener('submit', (e) => {
                let hasError = false;
                form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.classList.remove('invalid-field');
                    if (input.hasAttribute('required') && !input.value.trim()) {
                        input.classList.add('invalid-field');
                        hasError = true;
                    }
                    if (input.type === 'tel' && input.value.trim()) {
                        if (!/^[0-9]{10}$/.test(input.value.trim())) {
                            input.classList.add('invalid-field');
                            hasError = true;
                        }
                    }
                });
                if (hasError) {
                    e.preventDefault();
                    alert('Por favor, ingresa los datos de forma correcta. Revisa los campos resaltados en rojo (el teléfono debe contener 10 dígitos numéricos).');
                }
            });

            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('input', () => input.classList.remove('invalid-field'));
            });
        });

        // Alternar campos manuales si se selecciona personalizado
        function toggleManualOverrides(select) {
            const manualDiv = document.getElementById('manual-overrides');
            if (select.value === 'manual') {
                manualDiv.style.display = 'grid';
                document.getElementById('clases_override').setAttribute('required', 'required');
                document.getElementById('monto_override').setAttribute('required', 'required');
                document.getElementById('vencimiento_override').setAttribute('required', 'required');
            } else {
                manualDiv.style.display = 'none';
                document.getElementById('clases_override').removeAttribute('required');
                document.getElementById('monto_override').removeAttribute('required');
                document.getElementById('vencimiento_override').removeAttribute('required');
            }
        }

        // Filtro de búsqueda en la tabla
        function filterAlumnas() {
            const filter = document.getElementById('search-input').value.toLowerCase();
            const rows = document.getElementById('alumnas-table').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCol = rows[i].getElementsByTagName('td')[0];
                const telCol = rows[i].getElementsByTagName('td')[1];
                if (nameCol && telCol) {
                    const name = nameCol.textContent || nameCol.innerText;
                    const tel = telCol.textContent || telCol.innerText;
                    if (name.toLowerCase().indexOf(filter) > -1 || tel.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }

        function openEditModal(alumna) {
            document.getElementById('edit-id').value = alumna.id;
            document.getElementById('edit-nombre').value = alumna.nombre;
            document.getElementById('edit-apellidos').value = alumna.apellidos;
            document.getElementById('edit-telefono').value = alumna.telefono;
            document.getElementById('edit-estatus').value = alumna.estatus;
            document.getElementById('edit-clases').value = alumna.clases_restantes;
            document.getElementById('edit-monto').value = alumna.monto;
            document.getElementById('edit-vencimiento').value = alumna.fecha_vencimiento || '';
            document.getElementById('edit-lesion').value = alumna.lesion || '';
            document.getElementById('edit_sexo').value = alumna.sexo || 'Mujer';
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function showHorariosModal(content) {
            const container = document.getElementById('horarios-content');
            if (!content) {
                container.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Sin horarios registrados.</p>';
            } else {
                const items = content.split('||SPLIT||').filter(l => l.trim());
                container.innerHTML = items.map(item => `
                    <div style="
                        background: #f5f9f3;
                        border: 1px solid rgba(156,175,136,0.3);
                        border-radius: 8px;
                        padding: 10px 14px;
                        margin-bottom: 8px;
                        font-size: 0.88rem;
                        color: var(--black);
                        font-weight: 500;
                        letter-spacing: 0.02em;
                    ">${item.trim()}</div>
                `).join('');
            }
            document.getElementById('modalHorarios').style.display = 'flex';
        }

        function closeHorariosModal() {
            document.getElementById('modalHorarios').style.display = 'none';
        }
    </script>

    <!-- Modal de Edición -->
    <div id="editModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="max-width:500px; width:100%; margin:20px; padding:24px; text-align:left;">
            <h2 class="card-title" style="font-size: 1.2rem; margin-bottom: 15px;">Editar Alumno</h2>
            <form action="admin_alumnas.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="grid-form">
                    <div class="form-group">
                        <label class="form-label" for="edit-nombre">Nombre</label>
                        <input type="text" class="form-input" name="nombre" id="edit-nombre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-apellidos">Apellidos</label>
                        <input type="text" class="form-input" name="apellidos" id="edit-apellidos" required>
                    </div>
                </div>
                <div class="grid-form" style="margin-top:10px;">
                    <div class="form-group">
                        <label class="form-label" for="edit-telefono">Teléfono</label>
                        <input type="text" class="form-input" name="telefono" id="edit-telefono" required pattern="[0-9]{10}">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" for="edit_sexo">Género</label>
                        <select name="sexo" id="edit_sexo" class="form-select" required>
                            <option value="Mujer">Mujer</option>
                            <option value="Hombre">Hombre</option>
                        </select>
                    </div>
                </div>
                <div class="grid-form" style="margin-top:10px;">
                    <div class="form-group">
                        <label class="form-label" for="edit-estatus">Estatus</label>
                        <select class="form-select" name="estatus" id="edit-estatus">
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                            <option value="Pendiente">Pendiente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-clases">Clases Restantes</label>
                        <input type="number" class="form-input" name="clases_restantes" id="edit-clases" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-monto">Monto Cobrado ($)</label>
                        <input type="number" step="0.01" class="form-input" name="monto" id="edit-monto" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top:10px;">
                    <label class="form-label" for="edit-vencimiento">Fecha de Vencimiento</label>
                    <input type="date" class="form-input" name="fecha_vencimiento" id="edit-vencimiento">
                </div>
                <div class="form-group" style="margin-top:10px;">
                    <label class="form-label" for="edit-lesion">Lesiones / Condición Médica</label>
                    <textarea class="form-textarea" name="lesion" id="edit-lesion" rows="2"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn btn-green">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Horarios -->
    <div id="modalHorarios" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1001; justify-content:center; align-items:center;">
        <div class="card" style="max-width:480px; width:100%; margin:20px; padding:32px; text-align:left; position:relative;">
            <button onclick="closeHorariosModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">✕</button>
            <h2 class="card-title" style="font-size:1.4rem; margin-bottom:6px;">📅 Horarios Elegidos</h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:20px;">Días y horarios confirmados para este alumno</p>
            <div id="horarios-content" style="max-height:360px; overflow-y:auto;"></div>
            <div style="margin-top:24px; text-align:right;">
                <button class="btn btn-outline" onclick="closeHorariosModal()">Cerrar</button>
            </div>
        </div>
    </div>
</body>
</html>
