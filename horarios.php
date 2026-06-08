<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance Studio — Consulta nuestros horarios de clases">
    <title>Balance Studio — Horarios</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .schedule-class {
            cursor: default !important;
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
                <a href="registro.php" class="nav-link">Clases</a>
                <a href="horarios.php" class="nav-link active">Horarios</a>
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
            <h1 class="page-title">Horarios</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Consulta la disponibilidad semanal del estudio</p>
        </section>

        <div class="card">
            <div style="display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 30px;">
                <div class="text-center">
                    <div class="card-title" style="margin-bottom:12px; border:none;">Filtrar por Coach</div>
                    <div class="filter-bar" id="coach-filters" style="justify-content: center;">
                        <span class="filter-chip active">Cargando coaches...</span>
                    </div>
                </div>
            </div>

            <div class="schedule-wrapper" style="margin-top: 24px;">
                <div class="schedule-grid" id="schedule-grid">
                    <div style="grid-column:1/-1">
                        <div class="empty-state">
                            <div class="empty-state-icon">⏳</div>
                            <div class="empty-state-text">Cargando calendario...</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-center">
                <a href="registro.php" class="btn btn-green">Registrarme y elegir mis días</a>
            </div>
        </div>

    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
