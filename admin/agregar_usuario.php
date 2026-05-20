<?php
require_once '../autorizacion/auth.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/agregar_usuario.css">
<div class="sport-bg">
    <i class="fas fa-futbol"></i>
    <i class="fas fa-basketball-ball"></i>
    <i class="fas fa-user-plus"></i>
    <i class="fas fa-id-card"></i>
    <i class="fas fa-shield-alt"></i>
</div>

<div class="main-form-wrapper">
    <div class="form-container">
        <div class="logo-area">
            <div class="logo">
                <i class="fas fa-futbol"></i>
                <span>SportReserve</span>
            </div>
            <div class="subtitle">Sistema de gestión de reservas deportivas</div>
        </div>

        <div class="form-icon">
            <i class="fas fa-user-plus"></i>
        </div>

        <h1>Agregar Nuevo Usuario</h1>
        <div class="info-text">
            Complete los siguientes campos para crear un nuevo usuario en el sistema.
        </div>

        <!-- Formulario -->
        <form id="userForm" action="../controladores/admin/agregar_usuario.php" method="post">
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
                <i class="fas fa-user"></i>
                <input type="text" id="userName" name="nombre" placeholder="Nombre completo" autocomplete="name">
            </div>

            <div class="input-group">
                <i class="fas fa-phone-alt"></i>
                <input type="tel" id="userPhone" name="telefono" placeholder="Teléfono" autocomplete="tel">
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="userEmail" name="email" placeholder="Correo electrónico" autocomplete="email">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="userPassword" name="password" placeholder="Contraseña">
            </div>
            <div class="password-strength" id="passwordStrength"></div>

            <div class="input-group">
                <i class="fas fa-check-circle"></i>
                <input type="password" id="userConfirmPassword" name="confirmar_password" placeholder="Confirmar contraseña">
            </div>

            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select id="userRole" name="rol">
                    <option value="recepcionista">Recepcionista</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-save"></i> Crear Usuario
            </button>
        </form>

        <a href="gestion_usuarios.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Usuarios
        </a>

        <div style="text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #6b95af;">
            <i class="fas fa-shield-alt"></i> Los datos se almacenan de forma segura
        </div>
    </div>
</div>

<script src="../app/js/admin/agregar_usuario.js"></script>
<?php include_once '../templates/footer.php'; ?>