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
    <title>SportReserve | Acceso al Sistema</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; min-height: 100vh; background: radial-gradient(circle at 10% 20%, #0a0f1c, #02060c); display: flex; align-items: center; justify-content: center; padding: 1.5rem; position: relative; overflow-x: hidden; }
        .sport-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; opacity: 0.1; pointer-events: none; }
        .sport-bg i { position: absolute; font-size: 12rem; color: #22D3EE; }
        .sport-bg .fa-futbol { top: 5%; left: -3%; transform: rotate(15deg); }
        .sport-bg .fa-basketball-ball { bottom: 10%; right: -2%; transform: rotate(-10deg); }
        .sport-bg .fa-table-tennis { top: 60%; left: 80%; font-size: 8rem; }
        .auth-container { position: relative; z-index: 10; width: 100%; max-width: 480px; background: rgba(12, 22, 32, 0.75); backdrop-filter: blur(18px); border-radius: 48px; padding: 2rem 2rem 2.5rem; border: 1px solid rgba(34, 211, 238, 0.4); box-shadow: 0 30px 50px -20px rgba(0,0,0,0.6); transition: all 0.3s ease; }
        .logo-area { text-align: center; margin-bottom: 2rem; }
        .logo { display: inline-flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.8rem; background: linear-gradient(135deg, #A3E635, #22D3EE); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 0.5rem; }
        .logo i { background: none; color: #2dd4bf; font-size: 2rem; }
        .subtitle { text-align: center; color: #9fc3d4; font-size: 0.9rem; margin-top: 0.25rem; }
        
        .form-card { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .input-group { margin-bottom: 1.5rem; position: relative; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #5eead4; font-size: 1rem; }
        .input-group input { width: 100%; background: rgba(20, 35, 45, 0.7); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 60px; padding: 0.9rem 1rem 0.9rem 2.8rem; font-family: 'Inter', sans-serif; font-size: 0.95rem; color: white; outline: none; transition: all 0.2s; }
        .input-group input:focus { border-color: #22D3EE; box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2); }
        .input-group input::placeholder { color: #8aaec0; }
        .forgot-link { text-align: right; margin-bottom: 1.5rem; }
        .forgot-link a { color: #5eead4; font-size: 0.75rem; text-decoration: none; }
        .btn-auth { width: 100%; background: linear-gradient(95deg, #10b981, #06b6d4); border: none; border-radius: 60px; padding: 0.9rem; font-weight: 700; font-size: 1rem; color: white; cursor: pointer; transition: 0.3s; margin-top: 0.5rem; margin-bottom: 1.5rem; font-family: 'Inter', sans-serif; }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 12px 25px -10px #06b6d4; }
        
        .social-login { display: flex; gap: 1rem; justify-content: center; margin-top: 1rem; }
        .social-icon { background: rgba(255,255,255,0.05); border: 1px solid rgba(34,211,238,0.3); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; color: #cbd5e6; }
        .social-icon:hover { background: rgba(34,211,238,0.2); transform: translateY(-3px); }
        .divider { display: flex; align-items: center; text-align: center; color: #6b95af; font-size: 0.75rem; margin: 1.2rem 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid rgba(34,211,238,0.25); }
        .divider span { margin: 0 0.8rem; }
        .extra-links { text-align: center; font-size: 0.8rem; color: #9fc3d4; margin-top: 1rem;}
        .extra-links a { color: #5eead4; text-decoration: none; font-weight: 600; }
        
        .alert { padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.85rem; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #f87171; color: #fecaca; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #34d399; color: #a7f3d0; }
        .alert ul { margin-left: 20px; }

        @media (max-width: 500px) { .auth-container { padding: 1.5rem; } .logo { font-size: 1.5rem; } }
    </style>
</head>
<body>
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-table-tennis"></i>
        <i class="fas fa-swimmer"></i>
    </div>

    <div class="auth-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-futbol"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Gestión de reservas deportivas</div>
        </div>

        <div id="loginForm" class="form-card">
            <form action="controladores/login.php" method="POST">
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

                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="loginEmail" name="email" placeholder="Correo electrónico" autocomplete="email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="loginPassword" name="password" placeholder="Contraseña" autocomplete="current-password" required>
                </div>
                <div class="forgot-link">
                    <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn-auth" id="btnLogin">Acceder al Dashboard <i class="fas fa-arrow-right"></i></button>
            </form>
            
            <div class="extra-links">
                ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
            </div>

            <div class="divider"><span>O continúa con</span></div>
            <div class="social-login">
                <a href="controladores/facebook_login.php" class="social-icon"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>
    </div>
</body>
</html>