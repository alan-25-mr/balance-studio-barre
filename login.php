<?php
/**
 * Balance Studio — Login & Registro Premium
 * Versión 2.0
 */
require_once __DIR__ . '/config/database.php';
session_start();

// Si ya está logueada como alumna, mandar a registro.php
if (isset($_SESSION['alumna_id'])) {
    header('Location: registro.php');
    exit;
}

// Si ya está logueado como coach, mandar a admin_alumnas.php
if (isset($_SESSION['coach_id'])) {
    header('Location: admin_alumnas.php');
    exit;
}

$pdo = getConnection();

// Cargar errores y éxitos persistidos para evitar error de reenvío de formulario
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $telefono = trim($_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($telefono) || empty($password)) {
            $_SESSION['error'] = 'Por favor, ingresa tu teléfono/nombre y contraseña.';
            header('Location: login.php');
            exit;
        } else {
            $hashedPassword = hash('sha256', $password);

        // Debug logging: record attempt
        $debugLog = "Attempt login: input={$telefono}, hashed={$hashedPassword}\n";
        file_put_contents(__DIR__ . '/debug_login.txt', $debugLog, FILE_APPEND);


            // 1. Intentar buscar en Coaches por Nombre, ID o Teléfono
        // Intentar buscar en Coaches por Nombre, ID o Teléfono y validar contraseña
        $stmt = $pdo->prepare("SELECT * FROM coaches WHERE (nombre = ? OR coach_id = ? OR telefono = ?) AND activo = 1 LIMIT 1");
        $stmt->execute([$telefono, $telefono, $telefono]);
        $coach = $stmt->fetch();

        // Verificar contraseña (hash SHA256)
        if ($coach) {
                // Log fetched password hash for debugging
                $debugLog = "Fetched coach password: {$coach['password']}\n";
                file_put_contents(__DIR__ . '/debug_login.txt', $debugLog, FILE_APPEND);
            }
            // Verificar contraseña (hash SHA256)
            if ($coach && $coach['password'] === $hashedPassword) {
            // Guardar el ID interno (autoincrementable) y nombre completo
            $_SESSION['coach_id'] = $coach['id'];
            $_SESSION['coach_id_code'] = $coach['coach_id'];
            $_SESSION['coach_nombre'] = $coach['nombre'] . ' ' . $coach['apellidos'];
            // Si es la coach Fany (ID 101) otorgar permisos de administrador
            if ($coach['coach_id'] === '101') {
                $_SESSION['coach_is_admin'] = true;
            }
            header('Location: admin_alumnas.php');
            exit;
        }

            if ($coach && $coach['password'] === $hashedPassword) {
                // Guardar el ID interno (autoincrementable) y nombre completo
                $_SESSION['coach_id'] = $coach['id'];
                $_SESSION['coach_id_code'] = $coach['coach_id'];
                $_SESSION['coach_nombre'] = $coach['nombre'] . ' ' . $coach['apellidos'];
                header('Location: admin_alumnas.php');
                exit;
            }

            // 2. Intentar buscar en Alumnas
            // Intentar buscar en Alumnas por Nombre, Teléfono o ID
            $stmt = $pdo->prepare("SELECT * FROM alumnas WHERE (nombre = ? OR telefono = ? OR alumna_id = ?) LIMIT 1");
            $stmt->execute([$telefono, $telefono, $telefono]);
            $alumna = $stmt->fetch();

            if ($alumna && $alumna['password'] === $hashedPassword) {
                // Guardar datos en sesión
                $_SESSION['alumna_id'] = $alumna['id'];
                $_SESSION['alumna_id_code'] = $alumna['alumna_id'];
                $_SESSION['alumna_nombre'] = $alumna['nombre'] . ' ' . $alumna['apellidos'];
                
                header('Location: registro.php');
                exit;
            } else {
                $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
                header('Location: login.php');
                exit;
            }
        }
    } elseif ($action === 'register') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $sexo = $_POST['sexo'] ?? 'Mujer';
        $password = $_POST['password'] ?? '';
        $lesion = trim($_POST['lesion'] ?? '');

        if (empty($nombre) || empty($apellidos) || empty($telefono) || empty($fecha_nacimiento) || empty($password)) {
            $_SESSION['error'] = 'Todos los campos excepto la lesión son obligatorios.';
            header('Location: login.php?tab=register');
            exit;
        } else {
            // Validar que nombre y apellidos solo tengan letras
            if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $nombre)) {
                $_SESSION['error'] = 'El nombre sólo debe contener letras y espacios.';
                header('Location: login.php?tab=register');
                exit;
            }
            if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $apellidos)) {
                $_SESSION['error'] = 'Los apellidos sólo deben contener letras y espacios.';
                header('Location: login.php?tab=register');
                exit;
            }
            // Validar formato del teléfono
            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                $_SESSION['error'] = 'El número de teléfono debe tener exactamente 10 dígitos.';
                header('Location: login.php?tab=register');
                exit;
            }

            // Verificar si el teléfono ya está registrado
            $stmt = $pdo->prepare("SELECT id FROM alumnas WHERE telefono = ? UNION SELECT id FROM coaches WHERE telefono = ? LIMIT 1");
            $stmt->execute([$telefono, $telefono]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'El número de teléfono ya se encuentra registrado.';
                header('Location: login.php?tab=register');
                exit;
            } else {
                $hashedPassword = hash('sha256', $password);

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO alumnas (
                            nombre, apellidos, fecha_nacimiento, telefono, password,
                            paquete_id, clases_restantes, lesion, fecha_registro, estatus, sexo
                        ) VALUES (?, ?, ?, ?, ?, NULL, 0, ?, CURDATE(), 'Activa', ?)
                    ");
                    $stmt->execute([
                        $nombre, $apellidos, $fecha_nacimiento, $telefono, $hashedPassword,
                        !empty($lesion) ? $lesion : null, $sexo
                    ]);

                    $alumnaId = $pdo->lastInsertId();
                    
                    // Generar alumna_id secuencialmente
                    $alumna_id_code = '20' . $alumnaId;
                    $stmt_id = $pdo->prepare("UPDATE alumnas SET alumna_id = ? WHERE id = ?");
                    $stmt_id->execute([$alumna_id_code, $alumnaId]);

                    $_SESSION['success'] = "¡Registro Exitoso! Tu ID asignado es: <strong>$alumna_id_code</strong>. Ya puedes ingresar con tu ID o tu número de teléfono.";
                    header('Location: login.php');
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al registrar: ' . $e->getMessage();
                    header('Location: login.php?tab=register');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Studio — Login Premium</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=20260601">
    <style>
        .split-screen {
            display: flex;
            min-height: 100vh;
            background: var(--white);
        }
        .left-side {
            flex: 1.2;
            background: linear-gradient(135deg, rgba(109,95,81,0.85), rgba(156,175,136,0.85)), url('assets/images/fany.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            color: var(--white);
            text-align: center;
            position: relative;
        }
        @media (max-width: 900px) {
            .left-side {
                display: none;
            }
        }
        .slogan-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 1s ease;
        }
        .slogan-title {
            font-family: var(--font-title);
            font-size: 3rem;
            font-weight: 300;
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .slogan-text {
            font-size: 1.05rem;
            font-weight: 300;
            opacity: 0.95;
            letter-spacing: 0.05em;
        }
        .right-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 80px;
            background: var(--bg-primary);
        }
        @media (max-width: 600px) {
            .right-side {
                padding: 40px 24px;
            }
        }
        .form-container {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }
        .auth-logo {
            font-family: var(--font-title);
            font-size: 2.2rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 30px;
            color: var(--black);
            text-decoration: none;
        }
        .auth-logo span {
            color: var(--green);
            font-style: italic;
        }
        .tabs-header {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        .tab-btn.active {
            color: var(--black);
            border-bottom: 2px solid var(--green);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
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
        .form-group-login {
            margin-bottom: 20px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Bordes rojos para campos inválidos */
        .invalid-field {
            border-color: #cd2c2c !important;
            background-color: #fcebeb !important;
            box-shadow: 0 0 0 3px rgba(205, 44, 44, 0.15) !important;
        }
    </style>
</head>
<body>

    <div class="split-screen">
        <!-- Lado Izquierdo: Studio Image + Slogan -->
        <div class="left-side">
            <div class="slogan-box">
                <h2 class="slogan-title">Balance <span style="color:var(--green-light)">Studio</span></h2>
                <p class="slogan-text">Encuentra tu equilibrio, esculpe tu fuerza.</p>
                <div style="width:50px; height:2px; background:var(--white); margin: 25px auto 15px; opacity:0.5;"></div>
                <p style="font-size:0.85rem; font-style:italic; opacity:0.8;">Barre · Pilates · Sculpt</p>
            </div>
        </div>

        <!-- Lado Derecho: Formularios -->
        <div class="right-side">
            <div class="form-container">
                <a href="index.php" class="auth-logo">Balance <span>Studio</span></a>

                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab('login')">Ingresar</button>
                    <button class="tab-btn" onclick="switchTab('register')">Registrarme</button>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">✕ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">✓ <?= $success ?></div>
                <?php endif; ?>

                <!-- Formulario de Login -->
                <div id="tab-login" class="tab-content active">
                    <form action="login.php" method="POST">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group-login">
                            <label class="form-label" for="login-telefono">ID, Nombre o Teléfono</label>
                            <input type="text" class="form-input" id="login-telefono" name="telefono" required placeholder="Ingresa tu ID, nombre o teléfono">
                        </div>

                        <div class="form-group-login">
                            <label class="form-label" for="login-password">Contraseña</label>
                            <input type="password" class="form-input" id="login-password" name="password" required placeholder="Tu contraseña">
                        </div>

                        <button type="submit" class="btn btn-green" style="width: 100%; justify-content: center; margin-top: 10px;">
                            Entrar
                        </button>
                    </form>
                </div>

                <!-- Formulario de Registro -->
                <div id="tab-register" class="tab-content">
                    <form action="login.php" method="POST">
                        <input type="hidden" name="action" value="register">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="reg-nombre">Nombre</label>
                                <input type="text" class="form-input" id="reg-nombre" name="nombre" required placeholder="Ej. Ana">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="reg-apellidos">Apellidos</label>
                                <input type="text" class="form-input" id="reg-apellidos" name="apellidos" required placeholder="Ej. Pérez">
                            </div>
                        </div>

                        <div class="form-row mt-2" style="margin-top:15px;">
                            <div class="form-group">
                                <label class="form-label" for="reg-telefono">Teléfono</label>
                                <input type="tel" class="form-input" id="reg-telefono" name="telefono" required placeholder="10 dígitos">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="reg-nacimiento">Fecha de Nacimiento</label>
                                <input type="date" class="form-input" id="reg-nacimiento" name="fecha_nacimiento" required>
                            </div>
                        </div>

                         <div class="form-row mt-2" style="margin-top:15px;">
                            <div class="form-group">
                                <label class="form-label" for="reg-password">Contraseña</label>
                                <input type="password" class="form-input" id="reg-password" name="password" required placeholder="Crea tu contraseña">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="reg-sexo">Sexo</label>
                                <select class="form-select" id="reg-sexo" name="sexo" required style="height: 44px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; width:100%; box-sizing: border-box;">
                                    <option value="Mujer" selected>Mujer</option>
                                    <option value="Hombre">Hombre</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group-login" style="margin-top:15px;">
                            <label class="form-label" for="reg-lesion">Lesiones o condición médica (Opcional)</label>
                            <textarea class="form-textarea" id="reg-lesion" name="lesion" placeholder="Ej. Dolor de rodilla, embarazo, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                            Completar Registro
                        </button>
                    </form>
                </div>
                
                <div style="margin-top: 25px; text-align: center;">
                    <a href="index.php" style="
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        width: 100%;
                        background: none;
                        border: 1px solid var(--border-color);
                        color: var(--text-muted);
                        padding: 12px;
                        border-radius: var(--radius-sm);
                        font-size: 0.9rem;
                        font-weight: 500;
                        text-decoration: none;
                        transition: var(--transition);
                        box-sizing: border-box;
                    " onmouseover="this.style.borderColor='var(--green)'; this.style.color='var(--black)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.color='var(--text-muted)';">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Regresar al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tab === 'login') {
                document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
                document.getElementById('tab-login').classList.add('active');
            } else {
                document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
                document.getElementById('tab-register').classList.add('active');
            }
        }

        // Detectar tab por parámetro o hash en URL
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'register' || window.location.hash === '#register') {
                switchTab('register');
            }

            // Validación robusta y visual en submit
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    let hasError = false;
                    form.querySelectorAll('input, select, textarea').forEach(input => {
                        // Limpiar errores previos
                        input.classList.remove('invalid-field');

                        // Validar campos requeridos vacíos
                        if (input.hasAttribute('required') && !input.value.trim()) {
                            input.classList.add('invalid-field');
                            hasError = true;
                        }

                        // Validar tipo teléfono (10 dígitos exactos)
                        if (input.type === 'tel' && input.value.trim()) {
                            const telPattern = /^[0-9]{10}$/;
                            if (!telPattern.test(input.value.trim())) {
                                input.classList.add('invalid-field');
                                hasError = true;
                            }
                        }

                        // Validar que nombre y apellidos solo tengan letras
                        if ((input.id === 'reg-nombre' || input.id === 'reg-apellidos') && input.value.trim()) {
                            const namePattern = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;
                            if (!namePattern.test(input.value.trim())) {
                                input.classList.add('invalid-field');
                                hasError = true;
                            }
                        }
                    });

                    if (hasError) {
                        e.preventDefault();
                        alert('Por favor, corrige los campos resaltados en rojo. Asegúrate de rellenar todos los campos obligatorios, que el teléfono contenga 10 dígitos numéricos y que los nombres y apellidos solo contengan letras.');
                    }
                });

                // Quitar error al corregir valor (input o change)
                form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.addEventListener('input', () => input.classList.remove('invalid-field'));
                });
            });
        });

        // Si hay un error persistente, activar la pestaña correspondiente o mantener la actual
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'register'): ?>
            switchTab('register');
        <?php endif; ?>
    </script>
</body>
</html>
