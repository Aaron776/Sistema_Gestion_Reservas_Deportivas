<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SportReserve | Acceso Denegado - 403</title>
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
        .sport-bg .fa-shield-alt { top: 50%; left: 80%; font-size: 10rem; opacity: 0.6; }

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
            border: 1px solid rgba(239, 68, 68, 0.5);
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

        /* icono de advertencia / deportivo */
        .error-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        .error-icon i {
            background: linear-gradient(135deg, #f97316, #ef4444);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .error-code {
            font-size: 5rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #f97316, #ef4444);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fecaca;
            margin-bottom: 1rem;
            font-family: 'Outfit', sans-serif;
        }

        .error-message {
            color: #cbd5e6;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .sport-tip {
            background: rgba(239, 68, 68, 0.1);
            border-left: 3px solid #ef4444;
            padding: 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #fca5a5;
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
        <i class="fas fa-shield-alt"></i>
        <i class="fas fa-ban"></i>
    </div>

    <div class="error-container">
        <div class="logo-small">
            <i class="fas fa-futbol"></i>
            <span>SportReserve</span>
        </div>
        
        <div class="error-icon">
            <i class="fas fa-shield-haltered"></i>
        </div>
        <div class="error-code">403</div>
        <h1>Acceso Denegado</h1>
        <div class="error-message">
            No tienes permisos suficientes para acceder a esta área del sistema.<br>
            Por favor, contacta con un administrador si crees que esto es un error.
        </div>
        
        <div class="sport-tip">
            <i class="fas fa-whistle"></i> ¿Árbitro fuera de juego? Al igual que en el deporte, cada rol tiene sus límites. Verifica tus credenciales.
        </div>
        
        <div class="action-buttons">
            <a href="#" class="btn-primary" onclick="alert('Redirigiendo al Dashboard principal (demo)'); return false;">
                <i class="fas fa-home"></i> Ir al Inicio
            </a>
            <a href="#" class="btn-outline" onclick="alert('Comunicate con soporte@sportreserve.com (demo)'); return false;">
                <i class="fas fa-headset"></i> Contactar Soporte
            </a>
        </div>
        
        <div style="margin-top: 2rem; font-size: 0.7rem; color: #6b95af;">
            <i class="fas fa-lock"></i> Si necesitas acceso adicional, solicita permisos al administrador.
        </div>
    </div>

    <script>
        // Simular redirección o mensaje adicional
        console.log("403 - Acceso Denegado | SportReserve - Sistema de reservas deportivas");
    </script>
</body>
</html>