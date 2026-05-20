<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SportReserve | Bienvenida al Sistema de Reservas Deportivas</title>
    <!-- Google Fonts: Inter + Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 0% 0%, #0a0f1c, #03060c);
            min-height: 100vh;
            color: #eef5ff;
            overflow-x: hidden;
        }

        /* Fondo con elementos deportivos decorativos sutiles */
        .sport-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            opacity: 0.12;
            pointer-events: none;
        }

        .sport-bg i {
            position: absolute;
            font-size: 12rem;
            color: #22D3EE;
        }

        .sport-bg .fa-futbol { top: 10%; left: -5%; transform: rotate(15deg); }
        .sport-bg .fa-basketball-ball { bottom: 15%; right: -3%; transform: rotate(-20deg); }
        .sport-bg .fa-table-tennis { top: 40%; right: 8%; font-size: 8rem; opacity: 0.5; }
        .sport-bg .fa-swimmer { bottom: 30%; left: 5%; font-size: 9rem; }

        /* Contenedor principal */
        .welcome-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 2.5rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Header / Nav simple */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 4rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(34, 211, 238, 0.25);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #A3E635, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo i {
            background: none;
            -webkit-background-clip: unset;
            color: #2dd4bf;
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #cbd5e6;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #2dd4bf;
        }

        .btn-outline {
            border: 1px solid #22D3EE;
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            background: transparent;
            transition: 0.2s;
        }

        .btn-outline:hover {
            background: rgba(34, 211, 238, 0.1);
            transform: translateY(-2px);
        }

        /* Hero principal - Bienvenida impactante */
        .hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
            margin-bottom: 5rem;
        }

        .hero-content {
            flex: 1;
            min-width: 280px;
        }

        .badge-welcome {
            display: inline-block;
            background: rgba(34, 211, 238, 0.15);
            padding: 0.3rem 1rem;
            border-radius: 60px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #5eead4;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(4px);
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff, #5eead4, #a3e635);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-content p {
            font-size: 1.1rem;
            color: #b0cfdf;
            line-height: 1.5;
            margin-bottom: 2rem;
            max-width: 550px;
        }

        .cta-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn-primary {
            background: linear-gradient(95deg, #10b981, #06b6d4);
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 10px 20px -8px rgba(6, 182, 212, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 25px -12px #06b6d4;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(34, 211, 238, 0.5);
            padding: 0.9rem 2rem;
            border-radius: 60px;
            font-weight: 600;
            color: #e2eef9;
            transition: 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero-visual {
            flex: 1;
            min-width: 280px;
            background: rgba(15, 35, 45, 0.4);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(34, 211, 238, 0.3);
            box-shadow: 0 30px 40px -20px black;
        }

        .hero-visual i {
            font-size: 5rem;
            color: #2dd4bf;
            margin: 0 10px;
        }

        .visual-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.8rem;
            flex-wrap: wrap;
        }

        .visual-stats div {
            font-weight: 700;
        }

        .visual-stats span {
            font-size: 1.8rem;
            font-weight: 800;
            color: #a3e635;
        }

        /* Sección de características (deporte y gestión) */
        .features {
            text-align: center;
            margin: 5rem 0 4rem;
        }

        .section-tag {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #5eead4;
            margin-bottom: 1rem;
        }

        .features h2 {
            font-size: 2.2rem;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 2.5rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
            margin-top: 1rem;
        }

        .feature-card {
            background: rgba(18, 30, 40, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 32px;
            padding: 2rem 1.5rem;
            transition: 0.25s;
            border: 1px solid rgba(72, 187, 120, 0.2);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: #22D3EE;
            background: rgba(25, 40, 52, 0.7);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: #2dd4bf;
            margin-bottom: 1.2rem;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
        }

        .feature-card p {
            color: #b4cfdf;
            font-size: 0.9rem;
        }

        /* Testimonial / CTA final */
        .testimonial {
            background: linear-gradient(115deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.08));
            border-radius: 56px;
            padding: 3rem;
            margin: 3rem 0 3rem;
            text-align: center;
            border: 1px solid rgba(34, 211, 238, 0.3);
        }

        .quote {
            font-size: 1.3rem;
            font-style: italic;
            max-width: 700px;
            margin: 0 auto 1rem;
            color: #e2f0f5;
        }

        .author {
            font-weight: 600;
            color: #5eead4;
        }

        .footer {
            padding: 2rem 0 1rem;
            border-top: 1px solid rgba(72, 187, 120, 0.2);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.8rem;
            color: #7f9eb5;
        }

        /* Animaciones y responsividad */
        @media (max-width: 768px) {
            .welcome-container {
                padding: 1.5rem;
            }
            .hero-content h1 {
                font-size: 2.2rem;
            }
            .features h2 {
                font-size: 1.8rem;
            }
            .testimonial {
                padding: 1.8rem;
            }
            .navbar {
                flex-direction: column;
                text-align: center;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero, .features, .testimonial {
            animation: fadeInUp 0.8s ease-out forwards;
        }
    </style>
</head>
<body>
    <!-- fondo decorativo -->
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-table-tennis"></i>
        <i class="fas fa-swimmer"></i>
        <i class="fas fa-running"></i>
    </div>

    <div class="welcome-container">
        <!-- Navegación simple (sin dashboard) -->
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-futbol"></i>
                <span>SportReserve</span>
            </div>
            <div class="nav-links">
                <a href="#">Inicio</a>
                <a href="#">Instalaciones</a>
                <a href="#">Precios</a>
                <a href="login.php" class="btn-outline">Iniciar Sesión</a>
                <a href="login.php" class="btn-primary" style="padding: 0.5rem 1.5rem; background: linear-gradient(95deg, #10b981, #06b6d4);">Registrarse</a>
            </div>
        </nav>

        <!-- HERO principal: Bienvenida con mensaje central -->
        <div class="hero">
            <div class="hero-content">
                <div class="badge-welcome">
                    <i class="fas fa-calendar-alt"></i> Sistema de gestión · Lanzamiento 2026
                </div>
                <h1>Reserva tu deporte<br>en segundos, <span style="color:#a3e635;">jugá sin límites</span></h1>
                <p>SportReserve es la plataforma ultramoderna para clubes, centros deportivos y complejos de canchas. Organiza turnos, controla disponibilidad y potencia tu comunidad deportiva.</p>
                <div class="cta-group">
                    <button class="btn-primary" onclick="alert('🚀 Redirigiendo al sistema de reservas (demo)')"><i class="fas fa-arrow-right"></i> Explorar el sistema</button>
                    <button class="btn-secondary" onclick="alert('📞 Contáctanos: +34 900 123 456')"><i class="fas fa-play-circle"></i> Ver tour</button>
                </div>
                <div style="margin-top: 2rem; display: flex; gap: 20px; font-size: 0.8rem;">
                    <span><i class="fas fa-check-circle" style="color:#2dd4bf;"></i> Sin comisiones ocultas</span>
                    <span><i class="fas fa-shield-alt" style="color:#2dd4bf;"></i> Seguridad avanzada</span>
                </div>
            </div>
            <div class="hero-visual">
                <div>
                    <i class="fas fa-futbol"></i>
                    <i class="fas fa-basketball-ball"></i>
                    <i class="fas fa-volleyball-ball"></i>
                </div>
                <div class="visual-stats">
                    <div><span>+1,200</span><br>Reservas activas</div>
                    <div><span>24</span><br>Canchas/Complejos</div>
                    <div><span>98%</span><br>Satisfacción</div>
                </div>
                <p style="margin-top: 20px; font-size:0.8rem;">⚡ Gestión en tiempo real para más de 15 deportes</p>
            </div>
        </div>

        <!-- Features: qué ofrece el sistema de bienvenida/informativo -->
        <div class="features">
            <div class="section-tag">Todo lo que necesitas</div>
            <h2>Diseñado para deportistas y administradores</h2>
            <div class="cards-grid">
                <div class="feature-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Reservas instantáneas</h3>
                    <p>Calendario interactivo, bloqueo de horarios y confirmación automática. Disponibilidad al segundo.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Estadísticas en vivo</h3>
                    <p>Ocupación, ingresos y reportes dinámicos para optimizar tu centro deportivo.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h3>Gestión de socios</h3>
                    <p>Perfiles personalizados, membresías y notificaciones inteligentes vía email/SMS.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Diseño responsive</h3>
                    <p>Desde el móvil o escritorio, reserva o administra con la mejor experiencia ultramoderna.</p>
                </div>
            </div>
        </div>

        <!-- Bloque "Cómo funciona" simple -->
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; margin: 2rem 0;">
            <div style="text-align: center; flex:1; min-width: 180px;">
                <div style="background: rgba(34,211,238,0.2); width: 60px; height: 60px; border-radius: 60px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;"><i class="fas fa-1" style="font-size:1.8rem; font-weight: bold;"></i></div>
                <h3>Elige tu deporte</h3>
                <p style="font-size:0.85rem;">Fútbol, pádel, tenis, natación y más.</p>
            </div>
            <div style="text-align: center; flex:1; min-width: 180px;">
                <div style="background: rgba(34,211,238,0.2); width: 60px; height: 60px; border-radius: 60px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;"><i class="fas fa-2" style="font-size:1.8rem;"></i></div>
                <h3>Selecciona horario</h3>
                <p style="font-size:0.85rem;">Disponibilidad en tiempo real.</p>
            </div>
            <div style="text-align: center; flex:1; min-width: 180px;">
                <div style="background: rgba(34,211,238,0.2); width: 60px; height: 60px; border-radius: 60px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;"><i class="fas fa-3" style="font-size:1.8rem;"></i></div>
                <h3>Confirma y juega</h3>
                <p style="font-size:0.85rem;">Recibe comprobante y accede al complejo.</p>
            </div>
        </div>

        <!-- Testimonial (muestra comunidad) -->
        <div class="testimonial">
            <i class="fas fa-quote-left" style="font-size:2rem; color:#2dd4bf; opacity:0.6;"></i>
            <div class="quote">“Desde que implementamos SportReserve, la gestión de reservas mejoró un 70%. Nuestros socios adoran la fluidez y la interfaz moderna.”</div>
            <div class="author">— Laura Méndez, Directora Deportiva Arena Sport</div>
            <div style="margin-top: 20px;">
                <button class="btn-primary" style="background: transparent; border: 1px solid #22D3EE; box-shadow: none;" onclick="alert('🎉 Solicita una demostración gratuita')"><i class="fas fa-chalkboard-user"></i> Demo para tu club</button>
            </div>
        </div>

        <!-- Footer: info de bienvenida y acceso -->
        <div class="footer">
            <div>© 2026 SportReserve — Gestión de Reservas Deportivas</div>
            <div style="display: flex; gap: 20px;">
                <span><i class="fab fa-instagram"></i> Instagram</span>
                <span><i class="fab fa-twitter"></i> Twitter</span>
                <span><i class="fas fa-envelope"></i> soporte@sportreserve.com</span>
            </div>
        </div>
    </div>
</body>
</html>