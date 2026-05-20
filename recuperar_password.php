<?php
require_once 'conexion/session.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SportReserve | Recuperar Contraseña</title>
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

        .sport-bg .fa-futbol {
            top: 5%;
            left: -2%;
            transform: rotate(15deg);
        }

        .sport-bg .fa-basketball-ball {
            bottom: 10%;
            right: -2%;
            transform: rotate(-10deg);
        }

        .sport-bg .fa-envelope {
            top: 50%;
            left: 85%;
            font-size: 8rem;
            opacity: 0.5;
        }

        .sport-bg .fa-key {
            bottom: 20%;
            left: 5%;
            font-size: 7rem;
        }

        /* contenedor principal */
        .recover-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px;
            background: rgba(12, 22, 32, 0.8);
            backdrop-filter: blur(18px);
            border-radius: 56px;
            padding: 2.5rem 2rem;
            border: 1px solid rgba(34, 211, 238, 0.4);
            box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.6);
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

        .logo-area {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #A3E635, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .logo i {
            background: none;
            color: #2dd4bf;
            font-size: 2rem;
        }

        .subtitle {
            text-align: center;
            color: #9fc3d4;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .recover-icon {
            text-align: center;
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
        }

        .recover-icon i {
            background: linear-gradient(135deg, #f59e0b, #22D3EE);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        h1 {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-family: 'Outfit', sans-serif;
            color: #eef5ff;
        }

        .info-text {
            text-align: center;
            color: #cbd5e6;
            font-size: 0.85rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .input-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #5eead4;
            font-size: 1rem;
            z-index: 1;
        }

        .input-group input {
            width: 100%;
            background: rgba(20, 35, 45, 0.7);
            border: 1px solid rgba(34, 211, 238, 0.3);
            border-radius: 60px;
            padding: 0.9rem 1rem 0.9rem 3rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: white;
            outline: none;
            transition: all 0.2s;
        }

        .input-group input:focus {
            border-color: #22D3EE;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
        }

        .input-group input::placeholder {
            color: #8aaec0;
        }

        .btn-recover {
            width: 100%;
            background: linear-gradient(95deg, #10b981, #06b6d4);
            border: none;
            border-radius: 60px;
            padding: 0.9rem;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 1.5rem;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-recover:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -10px #06b6d4;
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #5eead4;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .back-link a:hover {
            color: #a3e635;
            transform: translateX(-3px);
        }

        .success-message {
            background: rgba(16, 185, 129, 0.15);
            border-left: 3px solid #10b981;
            padding: 1rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #a3e635;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border-left: 3px solid #ef4444;
            padding: 1rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #fca5a5;
        }

        .alert {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #f87171;
            color: #fecaca;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #34d399;
            color: #a7f3d0;
        }

        .alert ul {
            margin-left: 20px;
        }

        .sport-tip {
            background: rgba(34, 211, 238, 0.08);
            border-radius: 20px;
            padding: 0.8rem;
            font-size: 0.75rem;
            color: #8aaec0;
            text-align: center;
            margin-top: 1.5rem;
        }

        @media (max-width: 550px) {
            .recover-container {
                padding: 1.8rem;
            }

            h1 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-envelope"></i>
        <i class="fas fa-key"></i>
        <i class="fas fa-shield-alt"></i>
    </div>

    <div class="recover-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-futbol"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Gestión de reservas deportivas</div>
        </div>

        <div class="recover-icon">
            <i class="fas fa-key"></i>
        </div>

        <h1>¿Olvidaste tu contraseña?</h1>
        <div class="info-text">
            No te preocupes, te enviaremos un enlace para restablecer tu contraseña.<br>
            Ingresa el correo con el que te registraste en el sistema.
        </div>

        <!-- Formulario -->
        <form action="controladores/recuperar_password.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <?php if (isset($_SESSION['errores'])) : ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($_SESSION['errores'] as $error) : ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errores']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['exito'])) : ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
                </div>
                <?php unset($_SESSION['exito']); ?>
            <?php endif; ?>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="recoverEmail" name="email" placeholder="correo@ejemplo.com" autocomplete="email" required>
            </div>
            <button type="submit" class="btn-recover" id="btnRecover">
                <i class="fas fa-paper-plane"></i> Enviar enlace de recuperación
            </button>
        </form>

        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
            </a>
        </div>

        <div class="sport-tip">
            <i class="fas fa-dumbbell"></i> ¿Recuerdas? En SportReserve cuidamos tu seguridad. Revisa también tu bandeja de spam.
        </div>
    </div>
</body>

</html>