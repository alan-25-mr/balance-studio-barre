<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/fechas.php';
session_start();

// Validar seguridad de sesión
if (!isset($_SESSION['coach_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();
$logged_coach_id = intval($_SESSION['coach_id']);

// Cargar mensajes flash
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Auxiliares de formato
function fmt_hora($val) {
    return substr($val, 0, 5);
}

// Proxima fecha por dia de la semana (Helper PHP)
function proxima_fecha_por_dia_semana($diaSemana, $desde = null) {
    $desde = $desde ? new DateTime($desde) : new DateTime();
    $map = ['Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6];
    $target = $map[$diaSemana] ?? null;
    if (!$target) return $desde->format('Y-m-d');
    
    $current = intval($desde->format('N'));
    $diff = $target - $current;
    if ($diff < 0) {
        $diff += 7;
    }
    $desde->modify("+$diff days");
    return $desde->format('Y-m-d');
}

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reservar') {
        $id_clase = intval($_POST['id_clase'] ?? 0);
        $id_alumna = intval($_POST['id_alumna'] ?? 0);
        $fecha_clase = $_POST['fecha_clase'] ?? '';

        if ($id_clase > 0 && $id_alumna > 0) {
            try {
                // Obtener capacidad y dia de la clase
                $stmt = $pdo->prepare("SELECT capacidad, dia_semana FROM horarios WHERE id = ?");
                $stmt->execute([$id_clase]);
                $horario = $stmt->fetch();

                if ($horario) {
                    $capacidad = intval($horario['capacidad'] ?: 15);
                    if (empty($fecha_clase)) {
                        $fecha_clase = proxima_fecha_por_dia_semana($horario['dia_semana']);
                    }

                    // Contar reservaciones confirmadas
                    $stmt_count = $pdo->prepare("SELECT COUNT(*) AS total FROM reservaciones WHERE id_clase = ? AND fecha_clase = ? AND estatus = 'Confirmada'");
                    $stmt_count->execute([$id_clase, $fecha_clase]);
                    $ocupados = intval($stmt_count->fetch()['total']);

                    if ($ocupados < $capacidad) {
                        // Obtener clases restantes de la alumna
                        $stmt_alumna = $pdo->prepare("SELECT COALESCE(clases_restantes, 0) AS clases_restantes, estatus FROM alumnas WHERE id = ?");
                        $stmt_alumna->execute([$id_alumna]);
                        $alumna = $stmt_alumna->fetch();

                        if ($alumna && ($alumna['clases_restantes'] > 0 || $alumna['estatus'] === 'Pendiente')) {
                            
                            // Verificar que no esté agendada ya en este mismo día y hora
                            $stmt_dup = $pdo->prepare("SELECT id_reserva FROM reservaciones WHERE id_clase = ? AND id_alumna = ? AND fecha_clase = ? AND estatus = 'Confirmada'");
                            $stmt_dup->execute([$id_clase, $id_alumna, $fecha_clase]);
                            if ($stmt_dup->fetch()) {
                                $_SESSION['error'] = 'La alumna ya está reservada en esta clase para esa fecha.';
                            } else {
                                // Crear reservación
                                $stmt_res = $pdo->prepare("INSERT INTO reservaciones (id_clase, id_alumna, fecha_clase, estatus) VALUES (?, ?, ?, 'Confirmada')");
                                $stmt_res->execute([$id_clase, $id_alumna, $fecha_clase]);

                                // Restar clase si es activa
                                if ($alumna['estatus'] === 'Activa' && $alumna['clases_restantes'] > 0) {
                                    $stmt_up = $pdo->prepare("UPDATE alumnas SET clases_restantes = clases_restantes - 1 WHERE id = ?");
                                    $stmt_up->execute([$id_alumna]);
                                }

                                $_SESSION['success'] = "Reservación confirmada exitosamente para la fecha $fecha_clase.";
                            }
                        } else {
                            $_SESSION['error'] = 'La alumna no tiene clases restantes en su plan.';
                        }
                    } else {
                        $_SESSION['error'] = 'La clase ya se encuentra llena para esta fecha.';
                    }
                } else {
                    $_SESSION['error'] = 'Horario no encontrado.';
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al agendar reserva: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Campos inválidos.';
        }

        header('Location: admin_agenda.php');
        exit;
    } elseif ($action === 'cancelar') {
        if ($logged_coach_id === 2) {
            $_SESSION['error'] = 'No tienes permisos para cancelar reservaciones.';
            header('Location: admin_agenda.php');
            exit;
        }
        $id_reserva = intval($_POST['id_reserva'] ?? 0);
        if ($id_reserva > 0) {
            try {
                // Obtener datos de la reservación
                $stmt = $pdo->prepare("
                    SELECT r.*, a.estatus, a.id AS alumna_internal_id 
                    FROM reservaciones r 
                    INNER JOIN alumnas a ON r.id_alumna = a.id 
                    WHERE r.id_reserva = ?
                ");
                $stmt->execute([$id_reserva]);
                $reserva = $stmt->fetch();
                
                if ($reserva) {
                    // Eliminar reserva
                    $stmt_del = $pdo->prepare("DELETE FROM reservaciones WHERE id_reserva = ?");
                    $stmt_del->execute([$id_reserva]);
                    
                    // Si el estatus es activo, devolver la clase
                    if ($reserva['estatus'] === 'Activa') {
                        $stmt_up = $pdo->prepare("UPDATE alumnas SET clases_restantes = clases_restantes + 1 WHERE id = ?");
                        $stmt_up->execute([$reserva['alumna_internal_id']]);
                    }
                    $_SESSION['success'] = 'Clase cancelada exitosamente.';
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al cancelar clase: ' . $e->getMessage();
            }
        }
        header('Location: admin_agenda.php');
        exit;
    } elseif ($action === 'eliminar_alumna') {
        if ($logged_coach_id === 2) {
            $_SESSION['error'] = 'No tienes permisos para eliminar alumnas.';
            header('Location: admin_agenda.php');
            exit;
        }
        $alumna_id = intval($_POST['alumna_id'] ?? 0);
        if ($alumna_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM alumnas WHERE id = ?");
                $stmt->execute([$alumna_id]);
                $_SESSION['success'] = 'Alumna eliminada exitosamente.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al eliminar alumna: ' . $e->getMessage();
            }
        }
        header('Location: admin_agenda.php');
        exit;
    } elseif ($action === 'editar_alumna') {
        if ($logged_coach_id === 2) {
            $_SESSION['error'] = 'No tienes permisos para editar alumnas.';
            header('Location: admin_agenda.php');
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        
        if ($id > 0 && !empty($nombre) && !empty($apellidos) && !empty($telefono)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE alumnas SET
                        nombre = ?,
                        apellidos = ?,
                        telefono = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $apellidos, $telefono, $id]);
                $_SESSION['success'] = 'Alumno editado exitosamente.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al editar alumno: ' . $e->getMessage();
            }
        }
        header('Location: admin_agenda.php');
        exit;
    }
}

// Cargar catálogos para filtros
if ($logged_coach_id === 2) {
    $stmt_coaches = $pdo->query("SELECT id, nombre, apellidos FROM coaches WHERE id = 2");
} else {
    $stmt_coaches = $pdo->query("SELECT id, nombre, apellidos FROM coaches WHERE activo = 1 ORDER BY nombre");
}
$filter_coaches = $stmt_coaches->fetchAll();

$filter_packages = $pdo->query("SELECT id, nombre FROM paquetes WHERE activo = 1 ORDER BY precio")->fetchAll();

if ($logged_coach_id === 2) {
    $stmt_scheds = $pdo->query("
        SELECT id, dia_semana, hora_inicio, tipo_clase 
        FROM horarios 
        WHERE coach_id = 2 AND activo = 1 
        ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), hora_inicio
    ");
} else {
    $stmt_scheds = $pdo->query("
        SELECT id, dia_semana, hora_inicio, tipo_clase 
        FROM horarios 
        WHERE activo = 1 
        ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), hora_inicio
    ");
}
$filter_schedules = $stmt_scheds->fetchAll();

// Cargar alumnas para agregar manualmente
$stmt_al = $pdo->query("
    SELECT id, CONCAT(nombre, ' ', apellidos) AS nombre_completo, telefono 
    FROM alumnas 
    WHERE estatus IN ('Activa', 'Pendiente') 
    ORDER BY nombre
");
$alumnas_catalogo = $stmt_al->fetchAll();

// Paginación
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Query para contar total de registros
$count_query = "
    SELECT COUNT(DISTINCT a.id) AS total
    FROM alumnas a
    WHERE EXISTS (
        SELECT 1 FROM reservaciones r4
        INNER JOIN horarios h4 ON r4.id_clase = h4.id
        WHERE r4.id_alumna = a.id AND r4.estatus = 'Confirmada'
        " . ($logged_coach_id === 2 ? " AND h4.coach_id = 2" : "") . "
    )
";
$total_rows = intval($pdo->query($count_query)->fetch()['total']);
$total_pages = ceil($total_rows / $limit);

// Query principal paginada
$query = "
    SELECT a.id AS alumna_id, a.nombre AS alumna_nombre, a.apellidos AS alumna_apellidos, a.telefono AS alumna_telefono, a.fecha_vencimiento AS alumna_vencimiento, a.sexo AS sexo, a.fecha_nacimiento AS fecha_nacimiento, a.lesion AS lesion,
           p.id AS paquete_id, p.nombre AS paquete_nombre,
           (SELECT GROUP_CONCAT(
                DISTINCT CONCAT(LOWER(h2.dia_semana), '-', TIME_FORMAT(h2.hora_inicio, '%k:%i'), 'hrs (', DATE_FORMAT(r2.fecha_clase, '%d/%m/%Y'), ')')
                ORDER BY FIELD(h2.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'), h2.hora_inicio ASC SEPARATOR '<br>')
            FROM reservaciones r2
            INNER JOIN horarios h2 ON r2.id_clase = h2.id
            WHERE r2.id_alumna = a.id AND r2.estatus = 'Confirmada') AS clases_txt,
           (SELECT GROUP_CONCAT(DISTINCT CONCAT(c2.nombre, ' ', c2.apellidos) SEPARATOR ', ')
            FROM reservaciones r3
            INNER JOIN horarios h3 ON r3.id_clase = h3.id
            INNER JOIN coaches c2 ON h3.coach_id = c2.id
            WHERE r3.id_alumna = a.id AND r3.estatus = 'Confirmada') AS coaches_txt
    FROM alumnas a
    LEFT JOIN paquetes p ON a.paquete_id = p.id
    WHERE EXISTS (
        SELECT 1 FROM reservaciones r4
        INNER JOIN horarios h4 ON r4.id_clase = h4.id
        WHERE r4.id_alumna = a.id AND r4.estatus = 'Confirmada'
        " . ($logged_coach_id === 2 ? " AND h4.coach_id = 2" : "") . "
    )
    ORDER BY a.nombre ASC
    LIMIT $limit OFFSET $offset
";
$reservations = $pdo->query($query)->fetchAll();

// Contar registros web pendientes
$stmt_pend = $pdo->query("SELECT COUNT(*) AS c FROM alumnas WHERE estatus = 'Pendiente'");
$pendientes_count = intval($stmt_pend->fetch()['c']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Studio — Agenda de Clases</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 900px) {
            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
        .pendientes-banner {
            background: #fef5e6;
            border: 1px solid #f0d9a8;
            padding: 14px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-size: 0.9rem;
            color: #d48000;
        }
        .pendientes-banner a {
            color: #d48000;
            font-weight: 600;
            text-decoration: underline;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background-color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 40px;
            width: 90%;
            max-width: 460px;
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            background: none;
            border: none;
            transition: var(--transition);
        }
        .modal-close:hover {
            color: var(--black);
        }
        .modal-title {
            font-family: var(--font-title);
            font-size: 1.8rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--black);
        }
        .modal-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
        }
        .pagination-link {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: var(--white);
            color: var(--black);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        .pagination-link:hover {
            background: var(--bg-input);
            border-color: var(--text-muted);
        }
        .pagination-link.active {
            background: var(--green-dark);
            color: var(--white);
            border-color: var(--green-dark);
        }
        .pagination-disabled {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: var(--white);
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            cursor: not-allowed;
            font-weight: 600;
            font-size: 0.9rem;
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
                <a href="admin_agenda.php" class="nav-link active">Agenda de Clases</a>
                <a href="admin_recepcion.php" class="nav-link">Recepción</a>
                <a href="logout.php" class="nav-link" style="color: #cd2c2c;">Cerrar Sesión</a>
            </nav>
        </div>
    </header>

    <main class="app-container" style="margin-top: 40px; max-width: 1200px;">
        <section class="page-title-section">
            <h1 class="page-title">Agenda de Clases</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Visualización y filtrado de clientes inscritos por horarios, coaches y planes</p>
        </section>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">✕ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($pendientes_count > 0): ?>
            <div class="pendientes-banner">
                ⚠️ Hay <strong><?= $pendientes_count ?></strong> cliente(s) esperando confirmación de pago. 
                <a href="admin_alumnas.php">Ir a activarlos</a>.
            </div>
        <?php endif; ?>

        <!-- Panel de Filtros y Búsqueda -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label" for="search-input">Buscar cliente</label>
                    <input type="text" id="search-input" class="form-input" placeholder="Nombre o Teléfono..." onkeyup="filterReservations()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="filter-coach">Filtrar por Coach</label>
                    <select id="filter-coach" class="form-select" onchange="filterReservations()">
                        <option value="todos">Todos los coaches</option>
                        <?php foreach ($filter_coaches as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> <?= htmlspecialchars($c['apellidos']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="filter-package">Filtrar por Plan</label>
                    <select id="filter-package" class="form-select" onchange="filterReservations()">
                        <option value="todos">Todos los planes</option>
                        <?php foreach ($filter_packages as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="filter-schedule">Filtrar por Horario</label>
                    <select id="filter-schedule" class="form-select" onchange="filterReservations()">
                        <option value="todos">Todos los horarios</option>
                        <?php foreach ($filter_schedules as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['dia_semana']) ?> <?= substr($s['hora_inicio'],0,5) ?> (<?= htmlspecialchars($s['tipo_clase']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; justify-content: flex-end;">
                <button type="button" class="btn btn-green" onclick="abrirReserva()">
                    ➕ Agregar cliente a clase
                </button>
            </div>
        </div>

        <!-- Tabla Completa de Reservaciones -->
        <div class="card">
            <h2 class="card-title" style="font-size: 1.1rem; margin-bottom: 20px;">Lista de Clases Agendadas</h2>
            <div class="table-container">
                <table class="data-table" id="agenda-table">
                    <thead>
                        <tr>
                            <th>Nombre del Cliente</th>
                            <th>Teléfono</th>
                            <th>Plan Contratado</th>
                            <th>Vencimiento</th>
                            <th>Coaches</th>
                            <th>Clases</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                            <tr class="reservation-row"
                                data-nombre="<?php echo htmlspecialchars(strtolower($r['alumna_nombre'] . ' ' . $r['alumna_apellidos'])) ?>" data-sexo="<?php echo htmlspecialchars($r['sexo']) ?>"
                                data-telefono="<?= htmlspecialchars($r['alumna_telefono']) ?>"
                                data-coach-id="todos"
                                data-paquete-id="<?= $r['paquete_id'] ?>"
                                data-horario-id="todos">
                                <td><strong><?= htmlspecialchars($r['alumna_nombre']) ?> <?= htmlspecialchars($r['alumna_apellidos']) ?></strong></td>
                                <td><?= htmlspecialchars($r['alumna_telefono']) ?></td>
                                <td><span style="opacity:0.85;"><?= htmlspecialchars($r['paquete_nombre'] ?? 'Ninguno') ?></span></td>
                                <td><?= $r['alumna_vencimiento'] ? htmlspecialchars($r['alumna_vencimiento']) : '—' ?></td>
                                <td><strong><?= htmlspecialchars($r['coaches_txt']) ?></strong></td>
                                 <td style="text-align:center;">
                                     <button class="btn btn-outline btn-sm" style="white-space:nowrap;" onclick="showClasesModal(<?php echo htmlspecialchars(json_encode($r['clases_txt'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>)">📅 Ver fechas</button>
                                 </td>
                                <td>
                                    <?php if ($logged_coach_id === 1): ?>
                                        <div style="display:flex; gap:6px;">
                                            <button onclick="openEditAlumna(<?php echo htmlspecialchars(json_encode([
                                                 'id' => $r['alumna_id'],
                                                 'nombre' => $r['alumna_nombre'],
                                                 'apellidos' => $r['alumna_apellidos'],
                                                 'telefono' => $r['alumna_telefono'],
                                                 'sexo' => $r['sexo'],
                                                 'fecha_nacimiento' => $r['fecha_nacimiento'],
                                                 'lesion' => $r['lesion']
                                             ])) ?>)" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">Editar</button>
                                            
                                            <form action="admin_agenda.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar permanentemente a este cliente del sistema?');">
                                                <input type="hidden" name="action" value="eliminar_alumna">
                                                <input type="hidden" name="alumna_id" value="<?= $r['alumna_id'] ?>">
                                                <button type="submit" class="btn btn-sm" style="background:#cd2c2c; color:white; padding: 4px 8px; font-size: 0.75rem;">Eliminar</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style:italic; font-size:0.8rem;">Solo lectura</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reservations)): ?>
                            <tr id="empty-row">
                                <td colspan="7" style="text-align:center; color: var(--text-muted); padding: 40px;">
                                    No hay clientes programados en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Controles de Paginación -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <?php if ($page > 1): ?>
                        <a href="admin_agenda.php?page=<?= $page - 1 ?>" class="pagination-link">&laquo; Anterior</a>
                    <?php else: ?>
                        <span class="pagination-disabled">&laquo; Anterior</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="admin_agenda.php?page=<?= $i ?>" class="pagination-link <?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="admin_agenda.php?page=<?= $page + 1 ?>" class="pagination-link">Siguiente &raquo;</a>
                    <?php else: ?>
                        <span class="pagination-disabled">Siguiente &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Reservar -->
    <div id="modalReserva" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="cerrarReserva()">✕</button>
            <h3 class="modal-title">Agregar a Clase</h3>
            <p class="modal-subtitle">Asignar una clase y fecha específica a una alumna registrada</p>
            <form action="admin_agenda.php" method="POST">
                <input type="hidden" name="action" value="reservar">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" for="id_alumna">Seleccionar Alumna</label>
                    <select name="id_alumna" id="id_alumna" class="form-select" required>
                        <option value="" disabled selected>Elegir alumna...</option>
                        <?php foreach ($alumnas_catalogo as $ac): ?>
                            <option value="<?= $ac['id'] ?>">
                                <?= htmlspecialchars($ac['nombre_completo']) ?> (Tel: <?= htmlspecialchars($ac['telefono']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" for="id_clase">Seleccionar Clase / Horario</label>
                    <select name="id_clase" id="id_clase" class="form-select" required>
                        <option value="" disabled selected>Elegir horario...</option>
                        <?php foreach ($filter_schedules as $fs): ?>
                            <option value="<?= $fs['id'] ?>">
                                <?= htmlspecialchars($fs['dia_semana']) ?> <?= substr($fs['hora_inicio'], 0, 5) ?> hrs — <?= htmlspecialchars($fs['tipo_clase']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="fecha_clase">Fecha de la clase (Opcional, vacío para próxima disponible)</label>
                    <input type="date" name="fecha_clase" id="fecha_clase" class="form-input">
                </div>

                <button type="submit" class="btn btn-green" style="width:100%; justify-content: center;">Confirmar Reservación</button>
            </form>
        </div>
    </div>

    <!-- Modal Editar Alumna -->
    <div id="modalEditarAlumna" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="cerrarEditAlumna()">✕</button>
            <h3 class="modal-title">Editar Alumno</h3>
            <p class="modal-subtitle">Modifica el nombre, apellidos o teléfono del alumno</p>
            <form action="admin_agenda.php" method="POST">
                <input type="hidden" name="action" value="editar_alumna">
                <input type="hidden" name="id" id="edit_alumna_id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="edit_nombre">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-input" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="edit_apellidos">Apellidos</label>
                    <input type="text" name="apellidos" id="edit_apellidos" class="form-input" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 28px;">
                    <label class="form-label" for="edit_telefono">Teléfono</label>
                    <input type="text" name="telefono" id="edit_telefono" class="form-input" required pattern="[0-9]{10}" placeholder="10 dígitos">
                </div>
                
                <div style="display:flex; gap:12px;">
                    <button type="button" class="btn btn-outline" style="flex:1; justify-content:center;" onclick="cerrarEditAlumna()">Cancelar</button>
                    <button type="submit" class="btn btn-green" style="flex:1; justify-content:center;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Fechas de Clases -->
    <div id="modalClases" class="modal">
        <div class="modal-content" style="max-width: 520px;">
            <button class="modal-close" onclick="cerrarClases()">✕</button>
            <h3 class="modal-title">📅 Fechas de Clases</h3>
            <p class="modal-subtitle">Clases confirmadas para este alumno</p>
            <div id="clases-content" style="margin-top: 16px; max-height: 360px; overflow-y: auto;"></div>
        </div>
    </div>
</div>
    </div>

    <footer style="background: var(--black); color: var(--white); padding: 40px 0; margin-top: 60px; text-align: center;">
        <div class="app-container">
            <p style="opacity: 0.6; font-size: 0.85rem;">© 2026 Balance Studio. Panel de Coaches.</p>
        </div>
    </footer>

    <script>
        function abrirReserva() {
            document.getElementById('modalReserva').classList.add('active');
        }
        function cerrarReserva() {
            document.getElementById('modalReserva').classList.remove('active');
        }
        
        function openEditAlumna(alumna) {
            document.getElementById('edit_alumna_id').value = alumna.id;
            document.getElementById('edit_nombre').value = alumna.nombre;
            document.getElementById('edit_apellidos').value = alumna.apellidos;
            document.getElementById('edit_telefono').value = alumna.telefono;
            document.getElementById('modalEditarAlumna').classList.add('active');
        }
        function cerrarEditAlumna() {
            document.getElementById('modalEditarAlumna').classList.remove('active');
        }
        
        window.onclick = function(e) {
            if (e.target.id === 'modalReserva') cerrarReserva();
            if (e.target.id === 'modalEditarAlumna') cerrarEditAlumna();
        };

        // Filtro y búsqueda en JavaScript
        function filterReservations() {
            const search = document.getElementById('search-input').value.toLowerCase();
            const coachId = document.getElementById('filter-coach').value;
            const packageId = document.getElementById('filter-package').value;
            const scheduleId = document.getElementById('filter-schedule').value;
            
            const rows = document.querySelectorAll('.reservation-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.getAttribute('data-nombre');
                const tel = row.getAttribute('data-telefono');
                const coach = row.getAttribute('data-coach-id');
                const pkg = row.getAttribute('data-paquete-id');
                const sched = row.getAttribute('data-horario-id');
                
                const matchesSearch = name.includes(search) || tel.includes(search);
                const matchesCoach = coachId === 'todos' || coach === coachId;
                const matchesPackage = packageId === 'todos' || pkg === packageId;
                const matchesSchedule = scheduleId === 'todos' || sched === scheduleId;
                
                if (matchesSearch && matchesCoach && matchesPackage && matchesSchedule) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Mostrar fila vacía si no hay resultados
            const emptyRow = document.getElementById('empty-row');
            if (visibleCount === 0) {
                if (!emptyRow) {
                    const tbody = document.querySelector('#agenda-table tbody');
                    const tr = document.createElement('tr');
                    tr.id = 'empty-row';
                    tr.innerHTML = `<td colspan="7" style="text-align:center; color: var(--text-muted); padding: 40px;">No se encontraron resultados para los filtros seleccionados.</td>`;
                    tbody.appendChild(tr);
                } else {
                    emptyRow.style.display = '';
                    emptyRow.querySelector('td').textContent = 'No se encontraron resultados para los filtros seleccionados.';
                }
            } else {
                if (emptyRow) {
                    emptyRow.style.display = 'none';
                }
            }
        }
    function showClasesModal(content) {
        const container = document.getElementById('clases-content');
        if (!content) {
            container.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Sin clases registradas.</p>';
        } else {
            // Cada clase en su propia línea tipo chip
            const lines = content.replace(/<br\s*\/?>/gi, '\n').split('\n').filter(l => l.trim());
            container.innerHTML = lines.map(line => `
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
                ">${line.trim()}</div>
            `).join('');
        }
        document.getElementById('modalClases').classList.add('active');
    }
    function cerrarClases() {
        document.getElementById('modalClases').classList.remove('active');
    }
</script>
</body>
</html>
