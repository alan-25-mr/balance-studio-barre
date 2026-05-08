<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance Studio — Regístrate a nuestras clases">
    <title>Balance Studio — Registro de Clases</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

    <!-- ── Header ── -->
    <header class="main-header">
        <div class="header-inner">
            <a href="index.php" class="logo">Balance <span>Studio</span></a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="registro.php" class="nav-link active">Clases</a>
                <a href="horarios.php" class="nav-link">Horarios</a>
            </nav>
        </div>
    </header>

    <main class="app-container">

        <!-- ── Title ── -->
        <section class="page-title-section">
            <h1 class="page-title">Registro de Clases</h1>
            <div class="page-title-divider"></div>
            <p class="page-subtitle">Únete a nuestra comunidad y comienza tu transformación</p>
        </section>

        <!-- ── Stats (Simple version for social proof) ── -->
        <div class="stats-row" style="max-width: 400px; margin: 0 auto 30px;">
            <div class="stat-card">
                <div class="stat-value" id="stat-total">0</div>
                <div class="stat-label">Alumnas ya registradas</div>
            </div>
        </div>

        <!-- ── Registration Form ── -->
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-title">Tus Datos Personales</div>

            <form id="formAlumna">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-input" id="nombre" name="nombre" placeholder="Tu nombre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="apellidos">Apellidos</label>
                        <input type="text" class="form-input" id="apellidos" name="apellidos" placeholder="Tus apellidos" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input type="text" class="form-input" id="telefono" name="telefono" placeholder="10 dígitos" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" class="form-input" id="fecha_nacimiento" name="fecha_nacimiento" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="paquete_id">Plan de Interés</label>
                        <select class="form-select" id="paquete_id" name="paquete_id" required>
                            <option value="">Seleccionar plan...</option>
                        </select>
                    </div>
                </div>

                <div class="form-row mt-2">
                    <div class="form-group full-width">
                        <label class="form-label" for="lesion">¿Tienes alguna lesión o condición médica?</label>
                        <textarea class="form-textarea" id="lesion" name="lesion" placeholder="Cuéntanos para cuidar de ti durante la clase..."></textarea>
                    </div>
                </div>

                <!-- Hidden fields for defaults as this is a customer-facing registration -->
                <input type="hidden" name="estatus" value="Pendiente">
                <input type="hidden" name="monto" value="0">
                <input type="hidden" name="fecha_vencimiento" value="">

                <div class="mt-3 text-center">
                    <button type="submit" class="btn btn-green btn-lg" id="btn-submit-alumna" style="width: 100%; max-width: 300px;">Completar Registro</button>
                    <p class="text-muted mt-1" style="font-size: 0.75rem;">Al registrarte, una de nuestras coordinadoras se pondrá en contacto contigo.</p>
                </div>
            </form>
        </div>

    </main>

    <script src="assets/js/app.js"></script>
    <script>
        // Pre-seleccionar paquete si viene por URL
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const packageId = urlParams.get('paquete');
            if (packageId) {
                // Esperar un poco a que el JS principal cargue los paquetes en el select
                setTimeout(() => {
                    const select = document.getElementById('paquete_id');
                    if (select) select.value = packageId;
                }, 500);
            }
        });
    </script>
</body>
</html>
