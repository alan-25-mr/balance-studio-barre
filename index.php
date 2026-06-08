<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance Studio — Encuentra tu equilibrio. Clases de Barre, Pilates y Fitness.">
    <title>Balance Studio — Inicio</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .hero {
            background: linear-gradient(rgba(255,255,255,0.6), rgba(255,255,255,0.6)), url('assets/images/estudio.jpeg') !important;
            background-size: cover !important;
            background-position: center !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            min-height: 80vh !important;
        }
        .hero-title {
            font-family: 'Cormorant Garamond', serif !important;
            text-align: center !important;
            width: 100% !important;
        }
        .hero-subtitle {
            text-align: center !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .coach-card {
            background: #fff !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            text-align: center !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease !important;
            margin-bottom: 30px !important;
        }
        .coach-card:hover {
            transform: translateY(-10px) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }
        .coach-img {
            width: 100% !important;
            height: 400px !important;
            object-fit: cover !important;
        }
        .coach-info {
            padding: 25px !important;
        }
        .coach-name {
            font-family: 'Cormorant Garamond', serif !important;
            font-size: 1.8rem !important;
            margin-bottom: 10px !important;
            color: #000 !important;
        }
        .coach-specialty {
            color: #9caf88 !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            margin-bottom: 15px !important;
            font-size: 0.85rem !important;
        }
        .coach-bio {
            font-size: 0.95rem !important;
            color: #555 !important;
            line-height: 1.6 !important;
        }
        .section-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
            gap: 40px !important;
            padding: 40px 0 !important;
        }
    </style>
</head>
<body>

    <!-- ── Header ── -->
    <header class="main-header">
        <div class="header-inner">
            <a href="index.php" class="logo">Balance <span>Studio</span></a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link active">Inicio</a>
                <a href="registro.php" class="nav-link">Clases</a>
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

    <!-- ── Hero Section ── -->
    <section class="hero">
        <h1 class="hero-title">Encuentra tu <span>Equilibrio</span></h1>
        <p class="hero-subtitle">Descubre la combinación perfecta de fuerza, flexibilidad y elegancia en Balance Studio.</p>
        <div style="display: flex; gap: 20px; margin-top: 30px; justify-content: center; flex-wrap: wrap;">
            <a href="login.php?tab=register" class="btn btn-green btn-lg" style="padding: 16px 40px; font-size: 1rem;">Registrarme a Clases</a>
            <?php if (!isset($_SESSION['alumna_id']) && !isset($_SESSION['coach_id'])): ?>
                <a href="login.php" class="btn btn-outline btn-lg" style="padding: 16px 40px; font-size: 1rem;">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </section>

    <main class="app-container">

        <!-- ── Planes / Paquetes ── -->
        <section class="home-section" id="planes">
            <div class="page-title-section">
                <h2 class="page-title">Nuestros Planes</h2>
                <div class="page-title-divider"></div>
                <p class="page-subtitle">Elige el paquete que mejor se adapte a tu ritmo de vida</p>
            </div>
            
            <div class="packages-grid" id="paquetes-home-grid">
                <!-- Se carga dinámicamente con JS -->
            </div>
        </section>

        <!-- ── Coaches ── -->
        <section class="home-section" id="coaches">
            <div class="page-title-section">
                <h2 class="page-title">Nuestros Coaches</h2>
                <div class="page-title-divider"></div>
                <p class="page-subtitle">Profesionales apasionados listos para guiarte en cada movimiento</p>
            </div>

            <div class="section-grid" id="coaches-home-grid">
                <!-- Se carga dinámicamente con JS -->
            </div>
        </section>

    </main>

    <footer style="background: var(--black); color: var(--white); padding: 60px 0; margin-top: 80px; text-align: center;">
        <div class="app-container">
            <div class="logo" style="color: var(--white); margin-bottom: 20px; display: block;">Balance <span style="color: var(--green);">Studio</span></div>
            <p style="opacity: 0.6; font-size: 0.9rem;">© 2024 Balance Studio. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cargar paquetes para el Home
            const loadHomePackages = async () => {
                try {
                    const response = await fetch('api/paquetes.php');
                    let paquetes = await response.json();
                    const grid = document.getElementById('paquetes-home-grid');
                    
                    if (paquetes.length === 0) {
                        grid.innerHTML = '<p class="text-muted">No hay planes disponibles en este momento.</p>';
                        return;
                    }

                    // Ordenar de menor a mayor cantidad de clases
                    paquetes.sort((a, b) => parseInt(a.clases_incluidas) - parseInt(b.clases_incluidas));

                    grid.innerHTML = paquetes.map(p => {
                        const isPopular = parseInt(p.clases_incluidas) === 12;
                        return `
                            <div class="package-card ${isPopular ? 'popular' : ''}">
                                ${isPopular ? '<div class="popular-badge">Más Popular</div>' : ''}
                                <div class="package-name">${p.nombre}</div>
                                <div class="package-price"><span class="currency">$</span>${parseFloat(p.precio).toLocaleString('es-MX')}</div>
                                <div class="package-detail" style="font-weight: 600; color: var(--black);">${p.clases_incluidas} ${p.clases_incluidas == 1 ? 'Sesión' : 'Sesiones'}</div>
                                <div class="package-detail">Vigencia: ${p.duracion_dias} días</div>
                                <div class="package-detail text-muted" style="margin-top: 15px; min-height: 40px;">${p.descripcion || ''}</div>
                                <div style="margin-top: 25px;">
                                    <a href="registro.php?paquete=${p.id}" class="btn ${isPopular ? 'btn-green' : 'btn-outline'} btn-sm" style="width: 100%;">Elegir Plan</a>
                                </div>
                            </div>
                        `;
                    }).join('');
                } catch (e) { 
                    console.error(e);
                    document.getElementById('paquetes-home-grid').innerHTML = '<p class="text-danger">Error al cargar los planes.</p>';
                }
            };

            // Cargar coaches para el Home
            const loadHomeCoaches = async () => {
                try {
                    // Coaches reales de la empresa con sus descripciones oficiales
                    const coaches = [
                        {
                            nombre: 'Stephanie',
                            apellidos: 'Salas Ponce',
                            especialidad: 'Pilates, Barré & Funcional',
                            bio: 'Certificada en pilates, barré y funcional, disciplina de fuerza que combina ejercicios específicos y funcionales para desarrollar fuerza, resistencia muscular y control corporal.',
                            img: 'assets/images/fany.jpg'
                        },
                        {
                            nombre: 'Fatima',
                            apellidos: 'Sánchez González',
                            especialidad: 'Barré',
                            bio: 'Certificada en barré, disciplina diseñada para tonificar, moldear e incrementar masa muscular mientras mejoras tu postura, estabilidad y capacidad física general.',
                            img: 'assets/images/fati.jpeg'
                        }
                    ];

                    const grid = document.getElementById('coaches-home-grid');
                    grid.innerHTML = coaches.map(c => `
                        <div class="coach-card">
                            <img src="${c.img}" alt="${c.nombre}" class="coach-img">
                            <div class="coach-info">
                                <div class="coach-name">${c.nombre} ${c.apellidos}</div>
                                <div class="coach-specialty">${c.especialidad}</div>
                                <p class="coach-bio">${c.bio}</p>
                            </div>
                        </div>
                    `).join('');
                } catch (e) { console.error(e); }
            };

            loadHomePackages();
            loadHomeCoaches();
        });
    </script>
</body>
</html>
