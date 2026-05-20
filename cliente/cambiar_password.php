<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_cliente = $_SESSION['id_usuario']; // obtenemos el id del usuario logclienteueado


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/cliente/cambiar_password.css">
<div class="main-form-wrapper">
    <div class="sport-bg">
        <i class="fas fa-futbol"></i>
        <i class="fas fa-basketball-ball"></i>
        <i class="fas fa-key"></i>
        <i class="fas fa-lock"></i>
        <i class="fas fa-shield-alt"></i>
    </div>

    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-key"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-lock"></i>
        </div>

        <h1>Cambiar Contraseña</h1>
        <div class="info-text">
            Para tu seguridad, asegúrate de usar una contraseña segura que no hayas usado antes.
        </div>

        <!-- Badge con información del usuario -->
        <div style="text-align: center;">
            <div class="user-badge" id="userBadge">
                <i class="fas fa-user-circle"></i> <span id="userName"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            </div>
        </div>

        <!-- Alerts for success/error messages (Dinámico) -->
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

        <!-- Formulario -->
        <form id="passwordForm" action="../controladores/cliente/cambiar_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cliente" value="<?= urlencode(Crypto::encrypt($id_cliente)) ?>">

            <!-- Campo: Contraseña Actual -->
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password_actual" id="currentPassword" placeholder="Contraseña actual" autocomplete="current-password" required>
                <button type="button" class="toggle-password" data-target="currentPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <!-- Campo: Contraseña Nueva -->
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" name="password_nueva" id="newPassword" placeholder="Nueva contraseña" autocomplete="new-password" required>
                <button type="button" class="toggle-password" data-target="newPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="password-strength" id="passwordStrength"></div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Actualizar Contraseña
            </button>
        </form>

        <a href="dash_cliente.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Mi Perfil
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Recomendamos usar una contraseña de al menos 8 caracteres
        </div>
    </div>
</div>

<script src="../app/js/cliente/cambiar_password.js"></script>
<?php include_once '../templates/footer.php'; ?>