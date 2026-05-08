<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance Studio — Gestión de paquetes y planes">
    <title>Balance Studio — Paquetes</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

    <!-- ── Header ── -->
    <header class="main-header">
        <div class="header-inner">
            <a href="index.php" class="logo">Balance <span>Studio</span></a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Alumnas</a>
                <a href="paquetes.php" class="nav-link active">Paquetes</a>
                <a href="horarios.php" class="nav-link">Horarios</a>
            </nav>
        </div>
    </header>

    <main class="app-container">

        <!-- ── Title ── -->
        <section class="page-title-section">
            <h1 class="page-title">Paquetes</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Administra los planes y paquetes disponibles para las alumnas</p>
        </section>

        <!-- ── Packages Cards ── -->
        <div class="packages-grid" id="paquetes-grid">
            <div class="empty-state" style="grid-column:1/-1">
                <div class="empty-state-icon">⏳</div>
                <div class="empty-state-text">Cargando paquetes...</div>
            </div>
        </div>

        <!-- ── Create / Edit Form ── -->
        <div class="card">
            <div class="card-title">Crear Paquete</div>

            <form id="formPaquete">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="paq_nombre">Nombre del paquete</label>
                        <input type="text" class="form-input" id="paq_nombre" name="nombre" placeholder="Ej: Premium" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="paq_precio">Precio</label>
                        <input type="number" class="form-input" id="paq_precio" name="precio" placeholder="$0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="paq_clases">Clases incluidas</label>
                        <input type="number" class="form-input" id="paq_clases" name="clases_incluidas" placeholder="0 = ilimitadas" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="paq_duracion">Duración (días)</label>
                        <input type="number" class="form-input" id="paq_duracion" name="duracion_dias" placeholder="30" min="1" value="30">
                    </div>
                </div>

                <div class="form-row mt-2">
                    <div class="form-group full-width">
                        <label class="form-label" for="paq_descripcion">Descripción</label>
                        <textarea class="form-textarea" id="paq_descripcion" name="descripcion" placeholder="Describe lo que incluye este paquete..."></textarea>
                    </div>
                </div>

                <div class="mt-2">
                    <button type="submit" class="btn btn-green" id="btn-submit-paquete">Crear Paquete</button>
                </div>
            </form>
        </div>

        <!-- ── Paquetes Table ── -->
        <div class="card">
            <div class="card-title">Todos los Paquetes</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Clases</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="paquetes-tbody">
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">⏳</div>
                                <div class="empty-state-text">Cargando...</div>
                            </div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
