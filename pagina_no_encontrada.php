<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SportReserve | Página No Encontrada - 404</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, #0a0f1c, #02060c);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* fondo deportivo decorativo */
        .sport-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.08;
            pointer-events: none;
        }

        .sport-bg i {
            position: absolute;
            font-size: 12rem;
            color: #22D3EE;
        }
        .sport-bg .fa-futbol { top: 5%; left: -2%; transform: rotate(15deg); }
        .sport-bg .fa-basketball-ball { bottom: 10%; right: -2%; transform: rotate(-10deg); }
        .sport-bg .fa-table-tennis { top: 60%; left: 85%; font-size: 8rem; }
        .sport-bg .fa-question-circle { top: 30%; right: 5%; font-size: 9rem; opacity: 0.5; }

        /* contenedor principal */
        .error-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 550px;
            background: rgba(12, 22, 32, 0.8);
            backdrop-filter: blur(18px);
            border-radius: 56px;
            padding: 2.5rem 2rem;
            border: 1px solid rgba(34, 211, 238, 0.4);
            box-shadow: 0 30px 50px -20px rgba(0,0,0,0.6);
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
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

        /* icono deportivo perdido */
        .error-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        .error-icon i {
            background: linear-gradient(135deg, #f59e0b, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .error-code {
            font-size: 5rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #f59e0b, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fde68a;
            margin-bottom: 1rem;
            font-family: 'Outfit', sans-serif;
        }

        .error-message {
            color: #cbd5e6;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .sport-tip {
            background: rgba(34, 211, 238, 0.08);
            border-left: 3px solid #22D3EE;
            padding: 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #a5f3fc;
            margin: 1.5rem 0;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-primary {
            background: linear-gradient(95deg, #10b981, #06b6d4);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 60px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px #06b6d4;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #22D3EE;
            padding: 0.8rem 2rem;
            border-radius: 60px;
            font-weight: 600;
            color: #5eead4;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: rgba(34, 211, 238, 0.1);
            transform: translateY(-2px);
        }

        .logo-small {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #A3E635, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .sugerencias {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #6b95af;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .sugerencias a {
            color: #5eead4;
            text-decoration: none;
        }

        @media (max-width: 550px) {
            .error-container {
                padding: 1.8rem;
            }
            .error-code {
                font-size: 3.5rem;
            }
            h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-table-tennis"></i>
        <i class="fas fa-question-circle"></i>
        <i class="fas fa-map-marker-alt"></i>
    </div>

    <div class="error-container">
        <div class="logo-small">
            <i class="fas fa-futbol"></i>
            <span>SportReserve</span>
        </div>
        
        <div class="error-icon">
            <i class="fas fa-ball-pile"></i>
        </div>
        <div class="error-code">404</div>
        <h1>¡Ups! Página no encontrada</h1>
        <div class="error-message">
            La cancha o página que buscas parece que se fue de calle.<br>
            Revisa la URL o explora nuestras instalaciones desde el inicio.
        </div>
        
        <div class="sport-tip">
            <i class="fas fa-flag-checkered"></i> Como un balón fuera de juego, esta página no está en nuestro campo. Pero no te preocupes, tenemos muchas otras secciones para ti.
        </div>
        
        <div class="action-buttons">
            <a href="#" class="btn-primary" onclick="alert('Redirigiendo al Dashboard principal (demo)'); return false;">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
            <a href="#" class="btn-outline" onclick="alert('Explorando instalaciones disponibles (demo)'); return false;">
                <i class="fas fa-calendar-alt"></i> Ver Reservas
            </a>
        </div>
        
        <div class="sugerencias">
            <span><i class="fas fa-search"></i> Puedes buscar:</span>
            <a href="#" onclick="alert('Búsqueda de canchas demo'); return false;">Canchas de fútbol</a>
            <a href="#" onclick="alert('Pistas de pádel demo'); return false;">Pádel</a>
            <a href="#" onclick="alert('Clases de natación demo'); return false;">Natación</a>
            <a href="#" onclick="alert('Torneos activos demo'); return false;">Torneos</a>
        </div>
        
        <div style="margin-top: 1.8rem; font-size: 0.7rem; color: #6b95af; border-top: 1px solid rgba(45,212,191,0.2); padding-top: 1rem;">
            <i class="fas fa-dumbbell"></i> SportReserve - Gestión de Reservas Deportivas
        </div>
    </div>

    <script>
        console.log("404 - Página No Encontrada | SportReserve - Sistema de reservas deportivas");
    </script>
</body>
</html>