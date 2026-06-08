<?php
/**
 * Balance Studio — Portal de Clases (Dashboard Alumna)
 * Versión 3.1
 */
require_once __DIR__ . '/config/database.php';
session_start();

// Proteger con sesión
if (!isset($_SESSION['alumna_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();
$alumnaId = $_SESSION['alumna_id'];

// Obtener datos actualizados de la alumna
$stmt = $pdo->prepare("
    SELECT a.*, p.nombre AS paquete_nombre 
    FROM alumnas a 
    LEFT JOIN paquetes p ON a.paquete_id = p.id 
    WHERE a.id = ?
");
$stmt->execute([$alumnaId]);
$alumna = $stmt->fetch();

if (!$alumna) {
    // Si no existe por alguna razón, limpiar sesión
    session_destroy();
    header('Location: login.php');
    exit;
}

// Obtener las reservaciones activas de la alumna
$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, h.dia_semana, h.hora_inicio, h.tipo_clase,
           CONCAT(c.nombre, ' ', c.apellidos) AS coach_nombre
    FROM reservaciones r
    JOIN horarios h ON r.id_clase = h.id
    JOIN coaches c ON h.coach_id = c.id
    WHERE r.id_alumna = ? AND r.estatus = 'Confirmada'
    ORDER BY r.fecha_clase ASC, h.hora_inicio ASC
");
$stmt->execute([$alumnaId]);
$reservaciones = $stmt->fetchAll();

// Si se recibe un logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance Studio — Tu panel de control y reserva de clases">
    <title>Balance Studio — Mis Clases</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .dashboard-grid-alumna {
            display: grid;
            grid-template-columns: 1fr 1.8fr;
            gap: 24px;
            margin-bottom: 30px;
        }
        @media (max-width: 900px) {
            .dashboard-grid-alumna {
                grid-template-columns: 1fr;
            }
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        .info-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        .info-value {
            font-weight: 600;
            color: var(--black);
        }
        .modo-dias-box {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin: 16px 0;
        }
        .modo-dias-box label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.9rem;
            background: var(--white);
        }
        .modo-dias-box label:has(input:checked) {
            border-color: var(--green);
            background: rgba(156, 175, 136, 0.12);
        }
        .progreso-clases {
            text-align: center;
            font-weight: 600;
            color: var(--green-dark);
            margin: 12px 0;
            font-size: 1rem;
        }
        #panel-elegir-dias {
            display: block;
        }
        #panel-elegir-dias.sin-plan .schedule-grid {
            opacity: 0.55;
            pointer-events: none;
        }
        #selected-classes-list {
            list-style: none;
            padding: 0;
            margin: 16px auto 0;
            max-width: 600px;
        }
        #selected-classes-list li {
            padding: 10px 14px;
            background: var(--bg-input);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .aviso-plan {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-style: italic;
        }
        .reserva-item {
            padding: 14px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .reserva-details {
            font-size: 0.9rem;
        }
        /* Schedule grid selectable styles */
        .schedule-class.selectable {
            cursor: pointer;
            outline: 2px dashed rgba(156, 175, 136, 0.5);
            outline-offset: -2px;
        }
        .schedule-class.selectable:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-md);
            outline-color: var(--green);
        }
        .schedule-class.selected {
            outline: 3px solid var(--green-dark) !important;
            outline-offset: -3px;
            box-shadow: 0 0 12px rgba(156, 175, 136, 0.4);
            cursor: pointer;
        }
        .schedule-class.disabled-class {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="header-inner">
            <a href="index.php" class="logo">Balance <span>Studio</span></a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="registro.php" class="nav-link active">Clases</a>
                <a href="horarios.php" class="nav-link">Horarios</a>
                <?php if (isset($_SESSION['alumna_id'])): ?>
                    <a href="registro.php" class="nav-link">Mi Panel</a>
                    <a href="logout.php" class="nav-link" style="color: #cd2c2c;">Cerrar Sesión</a>
                <?php elseif (isset($_SESSION['coach_id'])): ?>
                    <a href="admin_alumnas.php" class="nav-link">Panel Admin</a>
                    <a href="logout.php" class="nav-link" style="color: #cd2c2c;">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Iniciar Sesión</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="app-container">

        <section class="page-title-section">
            <h1 class="page-title">Panel de Alumna</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Hola, <?= htmlspecialchars($alumna['nombre']) ?>. Gestiona tus clases y agenda tus próximas sesiones.</p>
        </section>

        <!-- Grid del Dashboard -->
        <div class="dashboard-grid-alumna">
            
            <!-- Resumen de Cuenta -->
            <div class="card" style="margin-bottom:0;">
                <div class="card-title">Resumen de tu Plan</div>
                
                <div class="info-row">
                    <span class="info-label">Alumna:</span>
                    <span class="info-value"><?= htmlspecialchars($alumna['nombre'] . ' ' . $alumna['apellidos']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value"><?= htmlspecialchars($alumna['telefono']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Plan Contratado:</span>
                    <span class="info-value" style="color:var(--green-dark)"><?= htmlspecialchars($alumna['paquete_nombre'] ?? 'Ninguno (Sin plan activo)') ?></span>
                </div>
                <div class="info-row" style="border-top:1px dashed var(--border-color); padding-top:12px; margin-top:12px;">
                    <span class="info-label" style="font-size:1.1rem; color:var(--black)">Clases Restantes:</span>
                    <span class="info-value" style="font-size:1.2rem; color:var(--green-dark)"><?= (int)$alumna['clases_restantes'] ?> clases</span>
                </div>
                
                <?php if ($alumna['fecha_vencimiento']): ?>
                    <div class="info-row">
                        <span class="info-label">Vigencia hasta:</span>
                        <span class="info-value"><?= htmlspecialchars($alumna['fecha_vencimiento']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($alumna['lesion'])): ?>
                    <div class="info-row" style="margin-top:15px; flex-direction:column; gap:4px;">
                        <span class="info-label">Lesión reportada:</span>
                        <span class="info-value" style="color:#cd2c2c; font-weight:normal; font-size:0.85rem; background:#fcebeb; padding:8px; border-radius:4px;">
                            <?= htmlspecialchars($alumna['lesion']) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Próximas Clases Agendadas -->
            <div class="card" style="margin-bottom:0;">
                <div class="card-title">Tus Clases Agendadas</div>
                
                <?php if (empty($reservaciones)): ?>
                    <div class="empty-state" style="padding: 20px 0;">
                        <div class="empty-state-icon">🗓️</div>
                        <div class="empty-state-text">No tienes clases agendadas actualmente. Agrega un plan abajo para empezar.</div>
                    </div>
                <?php else: ?>
                    <div style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                        <?php foreach ($reservaciones as $r): ?>
                            <div class="reserva-item">
                                <div class="reserva-details">
                                    <strong><?= htmlspecialchars($r['tipo_clase']) ?></strong> con <?= htmlspecialchars($r['coach_nombre']) ?><br>
                                    <span style="color:var(--text-secondary); font-size:0.8rem;"><?= htmlspecialchars($r['dia_semana']) ?> a las <?= substr($r['hora_inicio'], 0, 5) ?> hrs</span>
                                </div>
                                <div style="font-weight:600; color:var(--green-dark); font-size:0.85rem; background:rgba(156,175,136,0.12); padding:6px 12px; border-radius:12px;">
                                    <?= htmlspecialchars($r['fecha_clase']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Módulo de Reserva (Solo si tiene plan o quiere contratar uno nuevo) -->
        <form id="formAlumna">
            <!-- Selector oculto con el ID de la alumna logueada para enviarlo en AJAX -->
            <input type="hidden" name="alumna_id" value="<?= $alumnaId ?>">

            <div class="card" style="max-width: 900px; margin: 0 auto 24px;">
                <div class="card-title">1. Elige tu plan</div>
                <div class="form-group">
                    <label class="form-label" for="paquete_id">Selecciona un Plan para Agendar Clases</label>
                    <select class="form-select" id="paquete_id" name="paquete_id" required>
                        <option value="">— Seleccionar plan —</option>
                    </select>
                </div>
            </div>

            <!-- Filtros de Agenda Personalizados -->
            <div class="card" id="panel-filtros" style="max-width: 900px; margin: 0 auto 24px;">
                <div class="card-title">Filtros de Horario (Opcional)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="filtro_coach">Filtrar por Coach</label>
                        <select class="form-select" id="filtro_coach" name="filtro_coach">
                            <option value="todos">Cualquier Coach (Stephanie o Fatima)</option>
                            <option value="1">Coach Stephanie</option>
                            <option value="2">Coach Fatima</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="filtro_hora">Hora de Clase</label>
                        <select class="form-select" id="filtro_hora" name="filtro_hora">
                            <option value="todos">Cualquier hora</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card sin-plan" id="panel-elegir-dias" style="margin-bottom: 24px;">
                <div class="card-title">2. Elige tus días y horarios</div>
                <p class="text-center" style="font-size:0.85rem;color:var(--green-dark);font-weight:600;margin-bottom:8px;">
                    ↑ Primero elige tu plan en el paso 1, luego toca las clases en el horario
                </p>

                <p class="text-center text-muted" id="booking-hint" style="font-size: 0.9rem;">
                    Primero selecciona un plan arriba.
                </p>
                <p class="progreso-clases" id="progreso-clases"></p>
                <ul id="selected-classes-list"></ul>

                <!-- Schedule grid (same format as horarios.php) -->
                <div class="schedule-wrapper" style="margin-top: 20px;">
                    <div id="clases-disponibles" class="schedule-grid">
                        <p class="aviso-plan" style="grid-column:1/-1;">Selecciona un plan para ver las clases disponibles.</p>
                    </div>
                </div>
            </div>

            <!-- Confirmación final -->
            <div class="text-center" style="margin-top: 30px; margin-bottom: 50px;">
                <button type="submit" class="btn btn-green btn-lg" id="btn-submit-alumna" disabled style="max-width: 360px; width: 100%;">
                    Confirmar y Agendar Clases
                </button>
                <p class="text-muted mt-1" style="font-size: 0.75rem;">Debes elegir todas las clases del plan antes de agendar.</p>
            </div>
        </form>

    </main>

    <script src="assets/js/app.js?v=8"></script>
    <script src="assets/js/clases-registro.js?v=8"></script>
</body>
</html>
