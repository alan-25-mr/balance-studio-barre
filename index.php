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
            background: linear-gradient(rgba(255,255,255,0.6), rgba(255,255,255,0.6)), url('assets/img/hero-bg.png') !important;
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
            </nav>
        </div>
    </header>

    <!-- ── Hero Section ── -->
    <section class="hero">
        <h1 class="hero-title">Encuentra tu <span>Equilibrio</span></h1>
        <p class="hero-subtitle">Descubre la combinación perfecta de fuerza, flexibilidad y elegancia en Balance Studio.</p>
        <a href="registro.php" class="btn btn-green btn-lg" style="padding: 16px 40px; font-size: 1rem;">Registrarme a Clases</a>
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
                    const paquetes = await response.json();
                    const grid = document.getElementById('paquetes-home-grid');
                    grid.innerHTML = paquetes.map(p => `
                        <div class="package-card">
                            <div class="package-name">${p.nombre}</div>
                            <div class="package-price"><span class="currency">$</span>${parseFloat(p.precio).toLocaleString('es-MX')}</div>
                            <div class="package-detail">${p.duracion_dias} días</div>
                            <div class="package-detail text-muted" style="margin-top: 15px;">${p.descripcion || ''}</div>
                            <div style="margin-top: 20px;">
                                <a href="registro.php?paquete=${p.id}" class="btn btn-outline btn-sm">Elegir Plan</a>
                            </div>
                        </div>
                    `).join('');
                } catch (e) { console.error(e); }
            };

            // Cargar coaches para el Home
            const loadHomeCoaches = async () => {
                try {
                    const response = await fetch('api/coaches.php');
                    const coaches = await response.json();
                    const grid = document.getElementById('coaches-home-grid');
                    
                    // Imágenes de ejemplo para los coaches
                    const coachImages = [
                        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=800',
                        'https://images.unsplash.com/photo-1594381898411-846e7d193883?auto=format&fit=crop&q=80&w=800',
                        'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&q=80&w=800'
                    ];

                    const bios = [
                        'Especialista en técnica de Barre y alineación postural. Con más de 5 años guiando a alumnas hacia su mejor versión física y mental.',
                        'Experta en Pilates Reformer y movimiento funcional. Su enfoque se centra en fortalecer el core y mejorar la flexibilidad profunda.',
                        'Apasionada del fitness integral y el bienestar. Combina ritmos dinámicos con ejercicios de alta intensidad para un entrenamiento completo.'
                    ];

                    grid.innerHTML = coaches.map((c, i) => `
                        <div class="coach-card">
                            <img src="${coachImages[i % coachImages.length]}" alt="${c.nombre}" class="coach-img">
                            <div class="coach-info">
                                <div class="coach-name">${c.nombre} ${c.apellidos}</div>
                                <div class="coach-specialty">${c.especialidad || 'Coach de Balance'}</div>
                                <p class="coach-bio">${bios[i % bios.length]}</p>
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
