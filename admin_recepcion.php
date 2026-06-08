<?php
require_once __DIR__ . '/config/database.php';
session_start();

// Validar seguridad de sesión
if (!isset($_SESSION['coach_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();

// Cargar mensajes flash
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Obtener fecha y hora actuales
$hoy = date('Y-m-d');
$logged_coach_id = intval($_SESSION['coach_id']);
$is_jefa = ($logged_coach_id === 1); // Stéphanie es la ID 1

// Determinar coach seleccionado
$coach_id = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : $logged_coach_id;

// Si no es jefa y trata de ver a otro coach, forzar a sí misma
if (!$is_jefa && $coach_id !== $logged_coach_id) {
    $coach_id = $logged_coach_id;
}

// Procesar POST (Pase de Lista)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'pase_lista') {
        $alumna_id_code = trim($_POST['alumna_id_code'] ?? '');
        if (!empty($alumna_id_code)) {
            try {
                // Buscar a la alumna por su alumna_id (código público), nombre o teléfono
                $stmt_al = $pdo->prepare("SELECT id, nombre, apellidos FROM alumnas WHERE alumna_id = ? OR nombre = ? OR telefono = ? LIMIT 1");
                $stmt_al->execute([$alumna_id_code, $alumna_id_code, $alumna_id_code]);
                $alumna = $stmt_al->fetch();
                
                if ($alumna) {
                    // Buscar reservaciones confirmadas para hoy
                    $stmt_res = $pdo->prepare("
                        SELECT r.id_reserva, h.tipo_clase, h.hora_inicio, c.nombre AS coach_nombre
                        FROM reservaciones r
                        INNER JOIN horarios h ON r.id_clase = h.id
                        INNER JOIN coaches c ON h.coach_id = c.id
                        WHERE r.id_alumna = ? AND r.fecha_clase = ? AND r.estatus = 'Confirmada'
                        LIMIT 1
                    ");
                    $stmt_res->execute([$alumna['id'], $hoy]);
                    $reserva = $stmt_res->fetch();
                    
                    if ($reserva) {
                        // Marcar reservación como asistida
                        $stmt_up = $pdo->prepare("UPDATE reservaciones SET estatus = 'Asistida' WHERE id_reserva = ?");
                        $stmt_up->execute([$reserva['id_reserva']]);
                        
                        $_SESSION['success'] = "Asistencia registrada: " . htmlspecialchars($alumna['nombre'] . ' ' . $alumna['apellidos']) . " en clase " . htmlspecialchars($reserva['tipo_clase']) . " de las " . substr($reserva['hora_inicio'], 0, 5) . " con " . htmlspecialchars($reserva['coach_nombre']);
                    } else {
                        $_SESSION['error'] = "No se encontró ninguna reservación confirmada para hoy para " . htmlspecialchars($alumna['nombre'] . ' ' . $alumna['apellidos']) . ".";
                    }
                } else {
                    $_SESSION['error'] = "No se encontró ninguna alumna con el ID, Nombre o Teléfono proporcionado: " . htmlspecialchars($alumna_id_code);
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al procesar asistencia: ' . $e->getMessage();
            }
        }
        header("Location: admin_recepcion.php?coach_id=" . $coach_id);
        exit;
    }
}

// Cargar coaches disponibles según privilegios
if ($is_jefa) {
    $stmt = $pdo->query("SELECT id, nombre, apellidos FROM coaches WHERE activo = 1 ORDER BY nombre");
    $coaches = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, nombre, apellidos FROM coaches WHERE id = ? AND activo = 1");
    $stmt->execute([$logged_coach_id]);
    $coaches = $stmt->fetchAll();
}

// Cargar información del coach seleccionado y sus alumnas inscritas
$coach_sel = null;
$mis_clases = [];

if ($coach_id > 0) {
    $stmt = $pdo->prepare("SELECT id, nombre, apellidos, especialidad FROM coaches WHERE id = ?");
    $stmt->execute([$coach_id]);
    $coach_sel = $stmt->fetch();

    if ($coach_sel) {
        // Alumnas inscritas en las clases del coach (mis clases)
        $stmt_mis_clases = $pdo->prepare("
            SELECT r.id_reserva, r.fecha_clase, h.dia_semana, h.hora_inicio, h.tipo_clase,
                   a.nombre, a.apellidos, a.telefono, a.fecha_vencimiento
            FROM reservaciones r
            INNER JOIN horarios h ON r.id_clase = h.id
            INNER JOIN alumnas a ON r.id_alumna = a.id
            WHERE h.coach_id = ? AND r.estatus = 'Confirmada'
            ORDER BY r.fecha_clase ASC, h.hora_inicio ASC
        ");
        $stmt_mis_clases->execute([$coach_id]);
        $mis_clases = $stmt_mis_clases->fetchAll();
    }
}

// Alumnas esperadas para el día de hoy
if ($is_jefa) {
    // Si es jefa, puede filtrar por el coach seleccionado o ver todas
    if ($coach_id === $logged_coach_id && !isset($_GET['coach_id'])) {
        $stmt_hoy = $pdo->prepare("
            SELECT r.id_reserva, r.estatus, h.hora_inicio, h.tipo_clase,
                   a.nombre, a.apellidos, a.alumna_id, c.nombre AS coach_nombre
            FROM reservaciones r
            INNER JOIN alumnas a ON r.id_alumna = a.id
            INNER JOIN horarios h ON r.id_clase = h.id
            INNER JOIN coaches c ON h.coach_id = c.id
            WHERE r.fecha_clase = ? AND r.estatus IN ('Confirmada', 'Asistida')
            ORDER BY h.hora_inicio
        ");
        $stmt_hoy->execute([$hoy]);
    } else {
        $stmt_hoy = $pdo->prepare("
            SELECT r.id_reserva, r.estatus, h.hora_inicio, h.tipo_clase,
                   a.nombre, a.apellidos, a.alumna_id, c.nombre AS coach_nombre
            FROM reservaciones r
            INNER JOIN alumnas a ON r.id_alumna = a.id
            INNER JOIN horarios h ON r.id_clase = h.id
            INNER JOIN coaches c ON h.coach_id = c.id
            WHERE r.fecha_clase = ? AND h.coach_id = ? AND r.estatus IN ('Confirmada', 'Asistida')
            ORDER BY h.hora_inicio
        ");
        $stmt_hoy->execute([$hoy, $coach_id]);
    }
} else {
    $stmt_hoy = $pdo->prepare("
        SELECT r.id_reserva, r.estatus, h.hora_inicio, h.tipo_clase,
               a.nombre, a.apellidos, a.alumna_id, c.nombre AS coach_nombre
        FROM reservaciones r
        INNER JOIN alumnas a ON r.id_alumna = a.id
        INNER JOIN horarios h ON r.id_clase = h.id
        INNER JOIN coaches c ON h.coach_id = c.id
        WHERE r.fecha_clase = ? AND h.coach_id = ? AND r.estatus IN ('Confirmada', 'Asistida')
        ORDER BY h.hora_inicio
    ");
    $stmt_hoy->execute([$hoy, $logged_coach_id]);
}
$alumnas_hoy = $stmt_hoy->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Studio — Recepción Coaches</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
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
                <a href="admin_alumnas.php" class="nav-link">Administración</a>
                <a href="admin_agenda.php" class="nav-link">Agenda de Clases</a>
                <a href="admin_recepcion.php" class="nav-link active">Recepción</a>
                <a href="logout.php" class="nav-link" style="color: #cd2c2c;">Cerrar Sesión</a>
            </nav>
        </div>
    </header>

    <main class="app-container" style="margin-top: 40px; max-width: 900px;">
        <section class="page-title-section">
            <h1 class="page-title">Recepción de Coaches</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Pase de lista diario para alumnas y control de asistencia a clases</p>
        </section>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">✕ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Pase de Lista (Alumnas) -->
        <div class="card">
            <h2 class="card-title" style="font-size: 0.9rem;">Pase de Lista — Registro de Asistencia de Alumnas</h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 15px;">
                Ingresa el ID, Nombre o Teléfono de la alumna para registrar su entrada a la clase de hoy.
            </p>
            <form method="POST" action="admin_recepcion.php?coach_id=<?= $coach_id ?>">
                <input type="hidden" name="action" value="pase_lista">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="alumna_id_code" class="form-input" placeholder="Ej. 201, Fany, etc." required style="flex: 1;">
                    <button type="submit" class="btn btn-green">Registrar Asistencia</button>
                </div>
            </form>
        </div>

        <!-- 1. Selección de Perfil -->
        <div class="card">
            <h2 class="card-title" style="font-size: 0.9rem;">Selecciona el perfil de coach a consultar</h2>
            <form method="GET" action="admin_recepcion.php">
                <select name="coach_id" class="form-select" onchange="this.form.submit()" required>
                    <option value="" disabled>— Elegir coach —</option>
                    <?php foreach ($coaches as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($coach_id === intval($c['id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?> <?= htmlspecialchars($c['apellidos']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($coach_sel): ?>
            <!-- 3. Clases y Alumnas Inscritas -->
            <div class="card">
                <h2 class="card-title" style="font-size: 0.9rem;">Alumnas Inscritas en mis clases (<?= htmlspecialchars($coach_sel['nombre']) ?>)</h2>
                <?php if (empty($mis_clases)): ?>
                    <p style="color:var(--text-muted); font-style: italic;">No hay alumnas inscritas en tus clases programadas.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Fecha y Hora</th>
                                    <th>Fecha de Vencimiento</th>
                                    <th>Teléfono</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mis_clases as $mc): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($mc['nombre']) ?> <?= htmlspecialchars($mc['apellidos']) ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($mc['tipo_clase']) ?><br>
                                            <span style="font-size:0.8rem; color: var(--text-muted);"><?= htmlspecialchars($mc['dia_semana']) ?> <?= substr($mc['hora_inicio'], 0, 5) ?> hrs (<?= htmlspecialchars($mc['fecha_clase']) ?>)</span>
                                        </td>
                                        <td><?= $mc['fecha_vencimiento'] ? htmlspecialchars($mc['fecha_vencimiento']) : '—' ?></td>
                                        <td><?= htmlspecialchars($mc['telefono']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Alumnas que deben asistir hoy -->
        <div class="card">
            <h2 class="card-title" style="font-size: 0.9rem;">Lista de asistencia para el día de hoy (<?= date('d/m/Y') ?>)</h2>
            <?php if (empty($alumnas_hoy)): ?>
                <p style="color:var(--text-muted); font-style: italic;">No hay alumnas programadas para el día de hoy.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Clase</th>
                                <th>Hora</th>
                                <th>Coach</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnas_hoy as $ah): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ah['alumna_id'] ?: '—') ?></strong></td>
                                    <td><?= htmlspecialchars($ah['nombre']) ?> <?= htmlspecialchars($ah['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($ah['tipo_clase']) ?></td>
                                    <td><?= substr($ah['hora_inicio'], 0, 5) ?> hrs</td>
                                    <td><?= htmlspecialchars($ah['coach_nombre']) ?></td>
                                    <td>
                                        <?php if ($ah['estatus'] === 'Asistida'): ?>
                                            <span class="badge badge-active" style="background-color: #e6f7ed; color: #1f874c; border: 1px solid #c7ebd4;">✓ Asistió</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending" style="background-color: #fcebeb; color: #cd2c2c; border: 1px solid #e0b4ae;">Falta</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <footer style="background: var(--black); color: var(--white); padding: 40px 0; margin-top: 60px; text-align: center;">
        <div class="app-container">
            <p style="opacity: 0.6; font-size: 0.85rem;">© 2026 Balance Studio. Panel de Coaches.</p>
        </div>
    </footer>
</body>
</html>
