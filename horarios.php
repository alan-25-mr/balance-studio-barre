<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance Studio — Consulta nuestros horarios de clases">
    <title>Balance Studio — Horarios</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Disable clicks on schedule items as customers shouldn't edit them */
        .schedule-class {
            cursor: default !important;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <!-- ── Header ── -->
    <header class="main-header">
        <div class="header-inner">
            <a href="index.php" class="logo">Balance <span>Studio</span></a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="registro.php" class="nav-link">Clases</a>
                <a href="horarios.php" class="nav-link active">Horarios</a>
            </nav>
        </div>
    </header>

    <main class="app-container">

        <!-- ── Title ── -->
        <section class="page-title-section">
            <h1 class="page-title">Horarios</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Consulta disponibilidad y elige tu clase ideal</p>
        </section>

        <!-- ── Filter ── -->
        <div class="card">
            <div style="display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 30px;">
                <div class="text-center">
                    <div class="card-title" style="margin-bottom:12px; border:none;">Filtrar por Coach</div>
                    <div class="filter-bar" id="coach-filters" style="justify-content: center;">
                        <span class="filter-chip active">Cargando coaches...</span>
                    </div>
                </div>
            </div>

            <!-- ── Calendar Grid ── -->
            <div class="schedule-grid" id="schedule-grid">
                <div style="grid-column:1/-1">
                    <div class="empty-state">
                        <div class="empty-state-icon">⏳</div>
                        <div class="empty-state-text">Cargando calendario...</div>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-center">
                <a href="registro.php" class="btn btn-green">Registrarme a una clase</a>
            </div>
        </div>

    </main>

    <script src="assets/js/app.js"></script>
    <script>
        // Override any edit functions to do nothing for safety
        window.HorariosModule = window.HorariosModule || {};
        window.HorariosModule.editHorario = () => {};
    </script>
</body>
</html>
